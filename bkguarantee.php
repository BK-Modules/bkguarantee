<?php
/**
 * BK EU Guarantee — aviso armonizado sobre la garantía legal de conformidad.
 *
 * Desde el 27 de septiembre de 2026, los artículos 5.1.e) y 6.1.l) de la Directiva 2011/83/UE
 * —en la redacción que les da la Directiva (UE) 2024/825— obligan al comerciante a recordar al
 * consumidor la garantía legal antes de que quede vinculado por el contrato o la oferta, usando
 * el aviso armonizado que fija el Reglamento de Ejecución (UE) 2025/1960.
 *
 * El módulo sirve ese aviso donde nace la oferta —la ficha de producto— y de nuevo en el resumen
 * del pedido. En línea el aviso debe mostrarse en color y entero: el anexo I reserva la
 * visualización anidada para la etiqueta GARAN, no para el aviso.
 *
 * @author    BK Modules
 * @copyright Cumsa
 * @license   MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class BkGuarantee extends Module
{
    /** Controlador de la pantalla de configuración, y nombre de su pestaña en el back office */
    const TAB_CLASS = 'AdminBkGuaranteeTab';

    /** Ancho máximo de la figura anidada: por encima deja de leerse como una insignia */
    const GARAN_BADGE_MAX_WIDTH = 240;

    public function __construct()
    {
        $this->name = 'bkguarantee';
        $this->tab = 'front_office_features';
        $this->version = '1.3.0';
        $this->author = 'BK Modules';
        $this->need_instance = 0;
        $this->bootstrap = true;
        // Un solo código de 1.6.1 a 9: lo que cambia entre versiones son los hooks de cada punto
        // (BkGuaranteeConfig), los textos (t()) y la forma de pintar una plantilla (render()).
        $this->ps_versions_compliancy = ['min' => '1.6.1.0', 'max' => '9.99.99'];

        parent::__construct();

        // Hasta 1.7.5 el traductor no carga los XLF de los módulos: sin esto la tienda y el back
        // office salen en inglés. Donde el núcleo ya los carga no hace nada. 1.6 no tiene traductor
        // de Symfony: t() lee el XLF directamente.
        if (!BkGuaranteeConfig::isPs16()) {
            \BkModules\I18n\V1\Translations::ensure($this->name);
        }

        $this->displayName = $this->t('BK EU Guarantee', [], 'Modules.Bkguarantee.Admin');
        $this->description = $this->t(
            'Shows the EU harmonised notice on the legal guarantee of conformity on your product pages and order summary.',
            [],
            'Modules.Bkguarantee.Admin'
        );
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHooks();

        if (!BkGuaranteeRule::installTable()) {
            return false;
        }

        BkGuaranteeConfig::installDefaults();

        if (!BkGuaranteeTabsInstaller::install($this->name)) {
            BkGuaranteeLogger::error('No se pudo crear la pestaña ' . self::TAB_CLASS);
        }

        $missing = BkGuaranteeNotice::missingLanguages();
        if (!empty($missing)) {
            BkGuaranteeLogger::warning(
                'Instalado sin arte oficial para: ' . implode(', ', $missing)
            );
        }
        BkGuaranteeLogger::confirmation('Módulo instalado, versión ' . $this->version);

        \BkModules\Registry\V2\InstallReporter::report($this->name, $this->version, 'install');

        return true;
    }

    public function uninstall()
    {
        \BkModules\Registry\V2\InstallReporter::report($this->name, $this->version, 'uninstall');

        BkGuaranteeTabsInstaller::uninstall();
        BkGuaranteeRule::uninstallTable();
        BkGuaranteeConfig::uninstallKeys();
        BkGuaranteeLogger::confirmation('Módulo desinstalado');

        return parent::uninstall();
    }

    /**
     * Registra los hooks del módulo en esta versión (BkGuaranteeConfig::hooks()): los de los puntos
     * de la ficha y del checkout, los del correo y el de los recursos del front. Es idempotente: la
     * instalación y cada actualización que traiga un hook nuevo pasan por aquí, y un hook ya
     * registrado se deja como está.
     *
     * @return bool
     */
    public function registerHooks()
    {
        $ok = true;
        foreach (BkGuaranteeConfig::hooks() as $hook) {
            if (!$this->registerHook($hook)) {
                BkGuaranteeLogger::error('No se pudo registrar el hook ' . $hook);
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * La pantalla de configuración del módulo es el controlador propio: así el panel tiene el
     * ancho del back office y no el de la caja de configuración de la lista de módulos.
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminBkGuaranteeConfig')
        );
    }

    /**
     * Los dos hooks del correo reciben sus parámetros por referencia, así que se declaran por
     * referencia también aquí: escribir sobre $params es lo que modifica el correo de verdad.
     */
    public function hookActionEmailAddAfterContent(&$params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_EMAIL)
            || !BkGuaranteeConfig::isOn(BkGuaranteeConfig::ENABLED)
            || !BkGuaranteeEmail::isOrderConfirmation(isset($params['template']) ? $params['template'] : '')
        ) {
            return;
        }

        // El correo se redacta en el idioma del cliente, no en el del lado desde el que se envía.
        $idLang = isset($params['id_lang']) ? (int) $params['id_lang'] : (int) $this->context->language->id;
        $title = BkGuaranteeDurability::text($idLang, 'Your rights on this purchase');
        $alt = BkGuaranteeDurability::text($idLang, 'EU harmonised notice on the legal guarantee of conformity');

        // Actualizaciones y reparación van delante del aviso, como marcadores: el contenido depende
        // del pedido, que este hook no conoce, y lo rellena hookActionGetExtraMailTemplateVars.
        $durability = BkGuaranteeConfig::isOn(BkGuaranteeConfig::DURABILITY);

        if (isset($params['template_html'])) {
            $params['template_html'] = BkGuaranteeEmail::insertIntoBody(
                $params['template_html'],
                ($durability ? BkGuaranteeEmail::DURABILITY_HTML : '') . BkGuaranteeEmail::htmlBlock($idLang, $title, $alt)
            );
        }
        if (isset($params['template_txt'])) {
            $params['template_txt'] .= ($durability ? BkGuaranteeEmail::DURABILITY_TXT : '')
                . BkGuaranteeEmail::textBlock($idLang, $title);
        }
    }

    /**
     * Rellena los marcadores de actualizaciones y reparación con los productos del pedido, en el
     * idioma del correo. INVARIANTE: en un order_conf los dos marcadores reciben siempre valor —vacío
     * si no hay nada que contar—, porque un marcador sin valor sale literal en el correo del cliente.
     */
    public function hookActionGetExtraMailTemplateVars(&$params)
    {
        if (!BkGuaranteeEmail::isOrderConfirmation(isset($params['template']) ? $params['template'] : '')) {
            return;
        }

        $vars = isset($params['template_vars']) && is_array($params['template_vars']) ? $params['template_vars'] : [];
        $idLang = isset($params['id_lang']) ? (int) $params['id_lang'] : (int) $this->context->language->id;
        $idOrder = BkGuaranteeEmail::orderId($vars);
        $products = [];
        if ($idOrder > 0
            && BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_EMAIL)
            && BkGuaranteeConfig::isOn(BkGuaranteeConfig::ENABLED)
            && BkGuaranteeConfig::isOn(BkGuaranteeConfig::DURABILITY)
        ) {
            $products = BkGuaranteeDurability::forOrder($idOrder, $idLang);
        }

        $params['extra_template_vars'][BkGuaranteeEmail::DURABILITY_HTML] = BkGuaranteeEmail::durabilityHtml($idLang, $products);
        $params['extra_template_vars'][BkGuaranteeEmail::DURABILITY_TXT] = BkGuaranteeEmail::durabilityText($idLang, $products);
    }

    public function hookActionEmailSendBefore(&$params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::EMAIL_ATTACH)
            || !BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_EMAIL)
            || !BkGuaranteeConfig::isOn(BkGuaranteeConfig::ENABLED)
            || !BkGuaranteeEmail::isOrderConfirmation(isset($params['template']) ? $params['template'] : '')
        ) {
            return true;
        }

        $idLang = isset($params['idLang']) ? (int) $params['idLang'] : (int) $this->context->language->id;
        $attachment = BkGuaranteeEmail::attachment($idLang);
        if ($attachment === null) {
            return true;
        }

        $params['fileAttachment'] = BkGuaranteeEmail::mergeAttachment(
            isset($params['fileAttachment']) ? $params['fileAttachment'] : null,
            $attachment
        );

        // Las etiquetas GARAN de los productos del pedido viajan también: en el correo no hay
        // navegador que monte el SVG, así que se componen con GD sobre el mismo arte oficial.
        if (BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_ON)) {
            $garan = BkGuaranteeEmail::garanAttachments(
                isset($params['templateVars']) && is_array($params['templateVars']) ? $params['templateVars'] : []
            );
            $params['fileAttachment'] = BkGuaranteeEmail::mergeAttachments($params['fileAttachment'], $garan);
        }

        // Deja constancia de lo que se adjuntó: cuando un comerciante dice que el aviso no le
        // llega, esta línea distingue entre "no se adjuntó" y "el correo no salió".
        $names = [];
        foreach ((array) $params['fileAttachment'] as $file) {
            if (isset($file['name'])) {
                $names[] = $file['name'];
            }
        }
        BkGuaranteeLogger::debug('Correo order_conf con adjuntos: ' . implode(', ', $names));

        return true;
    }

    /**
     * El CSS y el JS del front llevan la fecha del fichero como versión, igual que en el back
     * office: tras actualizar el módulo, ni el navegador del cliente ni una CDN siguen sirviendo la
     * copia vieja. PrestaShop la añade a la URL desde 8.0; 1.7 no tiene ese parámetro y los sirve
     * sin firmar (una URL firmada como `remote` rompería allí las tiendas con servidor de medios).
     */
    public function hookActionFrontControllerSetMedia()
    {
        if (!$this->shouldRender()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'bkguarantee-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150, 'version' => $this->assetVersion('views/css/front.css')]
        );

        if (BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_NESTED)) {
            $this->context->controller->registerJavascript(
                'bkguarantee-garan',
                'modules/' . $this->name . '/views/js/garan.js',
                ['position' => 'bottom', 'priority' => 150, 'version' => $this->assetVersion('views/js/garan.js')]
            );
        }
    }

    /**
     * Los mismos ficheros en 1.6, que los añade desde displayHeader: con la fecha en la URL y sin
     * comprobar la ruta, que con el interrogante no encontraría el fichero. Más front-ps16.css, que
     * fija la unidad de medida del módulo para la raíz de 10px de los temas de 1.6.
     */
    public function hookDisplayHeader()
    {
        if (!$this->shouldRender()) {
            return;
        }

        $this->context->controller->addCSS($this->assetUrl('views/css/front.css'), 'all', null, false);
        $this->context->controller->addCSS($this->assetUrl('views/css/front-ps16.css'), 'all', null, false);
        if (BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_NESTED)) {
            $this->context->controller->addJS($this->assetUrl('views/js/garan.js'), false);
        }
    }

    /**
     * Los puntos de la ficha de esta versión se registran todos y solo pinta el elegido: así cambiar de
     * sitio es un desplegable y no una reinstalación, y el comerciante puede además moverlo desde
     * Posiciones sin que el módulo se entere.
     */
    public function hookDisplayProductAdditionalInfo(array $params)
    {
        return $this->renderProduct('info', $params) . $this->renderGaran('info', $params)
            . $this->renderDurability('info', $params);
    }

    /**
     * `info` en 1.6 y 1.7.0, dentro del bloque de compra tras el botón: el mismo hueco que 1.7.1
     * renombró a displayProductAdditionalInfo. Solo se registra en esas versiones.
     */
    public function hookDisplayProductButtons(array $params)
    {
        return $this->hookDisplayProductAdditionalInfo($params);
    }

    public function hookDisplayAfterProductThumbs(array $params)
    {
        return $this->renderProduct('thumbs', $params) . $this->renderGaran('thumbs', $params)
            . $this->renderDurability('thumbs', $params);
    }

    public function hookDisplayFooterProduct(array $params)
    {
        return $this->renderProduct('footer', $params) . $this->renderGaran('footer', $params)
            . $this->renderDurability('footer', $params);
    }

    /**
     * Actualizaciones de software y reparación, con lo que el fabricante haya dado. No depende del
     * alcance del aviso: el periodo de actualizaciones vale también para contenido digital.
     *
     * @param string $placement info|thumbs|footer
     * @param array  $params
     *
     * @return string
     */
    private function renderDurability($placement, array $params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::DURABILITY)
            || BkGuaranteeConfig::getDurabilityPlacement() !== $placement
            || !$this->shouldRender()
        ) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $rows = BkGuaranteeDurability::linesFor($this->productIdFrom($params), $idLang);
        if (empty($rows)) {
            return '';
        }

        $this->smarty->assign([
            'bkdura_title' => BkGuaranteeDurability::text($idLang, 'Software updates and repair'),
            'bkdura_rows' => $rows,
            'bkdura_note' => BkGuaranteeDurability::text($idLang, 'Information provided by the manufacturer or provider.'),
        ]);

        return $this->render('durability.tpl');
    }

    /**
     * @param string $placement info|thumbs|footer
     * @param array  $params
     *
     * @return string
     */
    private function renderProduct($placement, array $params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_PRODUCT)) {
            return '';
        }

        if (BkGuaranteeConfig::getPlacement() !== $placement) {
            return '';
        }

        if (!BkGuaranteeScope::appliesTo($this->productIdFrom($params))) {
            return '';
        }

        return $this->renderNotice('product');
    }

    /**
     * La etiqueta GARAN tiene su propia posición: el aviso acompaña a la oferta y la etiqueta va
     * junto a la imagen del producto, que es donde la sitúa el reglamento.
     *
     * @param string $placement
     * @param array  $params
     *
     * @return string
     */
    private function renderGaran($placement, array $params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_ON)
            || BkGuaranteeConfig::getGaranPlacement() !== $placement
            || !$this->shouldRender()
        ) {
            return '';
        }

        $idProduct = $this->productIdFrom($params);
        $label = BkGuaranteeRule::labelFor($idProduct);
        if ($label === null) {
            return '';
        }

        $this->smarty->assign($this->garanVars() + [
            'bkgaran_years' => (int) $label['years'],
            'bkgaran_brand' => $label['brand'],
            'bkgaran_model' => $label['model'],
            'bkgaran_uid' => $idProduct . '-' . $placement,
        ]);

        return $this->render('garan.tpl');
    }

    /**
     * La etiqueta GARAN de cada producto del carrito que la tenga, inmediatamente antes del botón
     * de compra: de la información de durabilidad es la única pieza que § 312j Abs. 2 BGB, § 8
     * Abs. 1 FAGG y el art. 51 c.2 del Codice del consumo exigen ahí, destacada.
     *
     * Una etiqueta por modelo: los productos con la misma marca, el mismo modelo y los mismos años
     * comparten una sola, con el nombre de todos ellos encima. La etiqueta es la misma garan.tpl de
     * la ficha, no un resumen.
     *
     * @param string $placement confirmation|payment
     *
     * @return string
     */
    private function renderGaranCheckout($placement)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_ON)
            || !BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_CHECKOUT)
            || BkGuaranteeConfig::getGaranCheckoutPlacement() !== $placement
            || !$this->shouldRender()
            || !Validate::isLoadedObject($this->context->cart)
        ) {
            return '';
        }

        $labels = [];
        $seen = [];
        foreach ($this->context->cart->getProducts() as $product) {
            $idProduct = (int) $product['id_product'];
            if (isset($seen[$idProduct])) {
                continue;
            }
            $seen[$idProduct] = true;

            $label = BkGuaranteeRule::labelFor($idProduct);
            if ($label === null) {
                continue;
            }

            $key = $label['years'] . '|' . $label['brand'] . '|' . $label['model'];
            if (!isset($labels[$key])) {
                $labels[$key] = $label + [
                    'uid' => 'checkout-' . $placement . '-' . count($labels),
                    'products' => [],
                ];
            }
            $labels[$key]['products'][] = $product['name'];
        }

        if (empty($labels)) {
            return '';
        }

        $this->smarty->assign($this->garanVars() + [
            'bkgaran_co_title' => $this->t('Producer durability guarantee', [], 'Modules.Bkguarantee.Shop'),
            'bkgaran_co_labels' => array_values($labels),
            'bkgaran_co_placement' => $placement,
            'bkgaran_tpl' => $this->templateRef('garan.tpl'),
        ]);

        return $this->render('garan-checkout.tpl');
    }

    /**
     * Lo que garan.tpl necesita y no depende del producto: el arte, los anchos y los textos.
     *
     * @return array
     */
    private function garanVars()
    {
        $width = BkGuaranteeConfig::getGaranWidth();

        return [
            'bkgaran_art' => $this->assetUrl('views/img/garan-blank.png'),
            'bkgaran_nested_art' => $this->assetUrl('views/img/garan-nested.png'),
            'bkgaran_width' => $width,
            // La figura anidada es apaisada: al ancho de la etiqueta entera se vuelve un cartel.
            'bkgaran_badge_width' => min($width, self::GARAN_BADGE_MAX_WIDTH),
            'bkgaran_nested' => BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_NESTED),
            'bkgaran_alt' => $this->t(
                'GARAN label: producer durability guarantee in years',
                [],
                'Modules.Bkguarantee.Shop'
            ),
            'bkgaran_toggle' => $this->t('Producer guarantee in years', [], 'Modules.Bkguarantee.Shop'),
            'bkgaran_more' => $this->t('See the full label', [], 'Modules.Bkguarantee.Shop'),
        ];
    }

    /**
     * El identificador llega de forma distinta según el hook y la versión: como array presentado,
     * como ObjectModel o como nada. Se leen las tres y, si no hay ninguna, se cae a la petición.
     *
     * @param array $params
     *
     * @return int
     */
    private function productIdFrom(array $params)
    {
        if (isset($params['product'])) {
            $product = $params['product'];
            if (is_array($product) && isset($product['id_product'])) {
                return (int) $product['id_product'];
            }
            if (is_object($product) && isset($product->id)) {
                return (int) $product->id;
            }
        }

        if (isset($params['id_product'])) {
            return (int) $params['id_product'];
        }

        return (int) Tools::getValue('id_product');
    }

    /**
     * Arriba del paso de pago van el aviso y la etiqueta GARAN, cada uno si su posición es esta.
     */
    public function hookDisplayPaymentTop(array $params)
    {
        return $this->renderCheckout('payment') . $this->renderGaranCheckout('payment');
    }

    public function hookDisplayCheckoutBeforeConfirmation(array $params)
    {
        return $this->renderGaranCheckout('confirmation');
    }

    public function hookDisplayCheckoutSummaryTop(array $params)
    {
        return $this->renderCheckout('summary');
    }

    /**
     * `summary` en 1.6: bajo la tabla del carrito, en el primer paso y en el checkout de una página.
     */
    public function hookDisplayShoppingCartFooter(array $params)
    {
        return $this->renderCheckout('summary');
    }

    /**
     * @param string $placement payment|summary
     *
     * @return string
     */
    private function renderCheckout($placement)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_CHECKOUT)) {
            return '';
        }

        if (BkGuaranteeConfig::getCheckoutPlacement() !== $placement) {
            return '';
        }

        return $this->renderNotice('checkout');
    }

    /**
     * Única puerta de los textos del módulo, sus controladores y sus plantillas
     * (`{$bkguar_module->t('…')}`).
     *
     * `Module::trans()` es protected y los controladores no la alcanzan; y `AdminController` no
     * tiene `trans()` propio hasta 1.7.6, así que llamarlo desde allí revienta en 1.7.5. En 1.6 no
     * hay traductor de Symfony y se lee el XLF del módulo en el locale del contexto. Nada de `{l}`
     * en las plantillas: en 1.6 no conoce el dominio del módulo.
     *
     * @param string $id
     * @param array  $parameters
     * @param string $domain
     *
     * @return string
     */
    public function t($id, array $parameters = [], $domain = null)
    {
        if (BkGuaranteeConfig::isPs16()) {
            return \BkModules\I18n\V1\Translations::trans(
                $this->name,
                (string) $domain,
                BkGuaranteeDurability::locale((int) $this->context->language->id),
                $id,
                $parameters
            );
        }

        return $this->trans($id, $parameters, $domain);
    }

    /**
     * URL de un asset del módulo firmada con la fecha del fichero: la versión del módulo no
     * cambia al reemplazar una imagen o retocar el CSS, y el navegador serviría la copia vieja.
     *
     * @param string $relative
     *
     * @return string
     */
    public function assetUrl($relative)
    {
        return $this->_path . $relative . '?v=' . $this->assetVersion($relative);
    }

    /**
     * Fecha del fichero como versión de un asset del módulo.
     *
     * @param string $relative
     *
     * @return string
     */
    public function assetVersion($relative)
    {
        $path = _PS_MODULE_DIR_ . $this->name . '/' . $relative;

        return (string) (file_exists($path) ? filemtime($path) : $this->version);
    }

    /**
     * Pinta una plantilla de views/templates/hook/, con la sustitución del tema si la trae: por su
     * recurso `module:` desde 1.7 y con display() en 1.6, que no lo conoce.
     *
     * @param string $template
     *
     * @return string
     */
    private function render($template)
    {
        if (BkGuaranteeConfig::isPs16()) {
            return $this->display(__FILE__, 'views/templates/hook/' . $template);
        }

        return $this->fetch($this->templateRef($template));
    }

    /**
     * Cómo se nombra una plantilla de hook para fetch() o para un {include} desde otra.
     *
     * @param string $template
     *
     * @return string
     */
    private function templateRef($template)
    {
        if (BkGuaranteeConfig::isPs16()) {
            return $this->getTemplatePath('views/templates/hook/' . $template);
        }

        return 'module:bkguarantee/views/templates/hook/' . $template;
    }

    /**
     * @param string $place product|checkout
     *
     * @return string
     */
    private function renderNotice($place)
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $iso = $this->context->language->iso_code;
        $url = BkGuaranteeNotice::urlFor($iso);
        if ($url === null) {
            BkGuaranteeLogger::debug('Sin aviso para el idioma ' . $iso . ' en ' . $place);

            return '';
        }

        $this->smarty->assign([
            'bkguar_url' => $url,
            'bkguar_width' => BkGuaranteeConfig::getWidth(),
            'bkguar_place' => $place,
            'bkguar_style' => BkGuaranteeConfig::getStyle(),
            'bkguar_align' => BkGuaranteeConfig::getAlign(),
            'bkguar_alt' => $this->t(
                'EU harmonised notice on the legal guarantee of conformity',
                [],
                'Modules.Bkguarantee.Shop'
            ),
            'bkguar_full' => $this->t('View full size', [], 'Modules.Bkguarantee.Shop'),
            'bkguar_title' => $this->t('EU legal guarantee', [], 'Modules.Bkguarantee.Shop'),
            'bkguar_band_title' => $this->t('Your rights on this purchase', [], 'Modules.Bkguarantee.Shop'),
            'bkguar_band_text' => $this->t(
                'This is the official European Union notice on the legal guarantee of conformity, reproduced exactly as the Commission publishes it.',
                [],
                'Modules.Bkguarantee.Shop'
            ),
        ]);

        return $this->render('notice.tpl');
    }

    /**
     * El aviso es información precontractual dirigida al consumidor. Una tienda solo B2B queda
     * fuera de la obligación, pero eso lo decide el comerciante en la configuración: aquí no se
     * deduce de que el visitante tenga un grupo u otro.
     *
     * @return bool
     */
    private function shouldRender()
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::ENABLED)) {
            return false;
        }

        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::HIDE_FOR_B2B)) {
            return true;
        }

        $groups = BkGuaranteeConfig::getB2bGroups();
        if (empty($groups) || !Validate::isLoadedObject($this->context->customer)) {
            return true;
        }

        $customerGroups = $this->context->customer->getGroups();

        return empty(array_intersect($groups, array_map('intval', $customerGroups)));
    }
}
