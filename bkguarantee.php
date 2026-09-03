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
    /**
     * displayProductAdditionalInfo cae bajo el bloque de compra, que es donde la oferta queda a
     * la vista; displayPaymentTop repite el aviso justo encima de las formas de pago, que es la
     * última pantalla antes de que el contrato se cierre.
     *
     * @var array
     */
    private $hooks = [
        'displayProductAdditionalInfo',
        'displayAfterProductThumbs',
        'displayFooterProduct',
        'displayPaymentTop',
        'displayCheckoutSummaryTop',
        'actionEmailAddAfterContent',
        'actionEmailSendBefore',
        'actionFrontControllerSetMedia',
    ];

    public function __construct()
    {
        $this->name = 'bkguarantee';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'BK Modules';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->trans('BK EU Guarantee', [], 'Modules.Bkguarantee.Admin');
        $this->description = $this->trans(
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

        foreach ($this->hooks as $hook) {
            $this->registerHook($hook);
        }

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

        \BkModules\Registry\V1\InstallReporter::report($this->name, $this->version, 'install');

        return true;
    }

    public function uninstall()
    {
        \BkModules\Registry\V1\InstallReporter::report($this->name, $this->version, 'uninstall');

        BkGuaranteeTabsInstaller::uninstall();
        BkGuaranteeRule::uninstallTable();
        BkGuaranteeConfig::uninstallKeys();
        BkGuaranteeLogger::confirmation('Módulo desinstalado');

        return parent::uninstall();
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

        $idLang = isset($params['id_lang']) ? (int) $params['id_lang'] : (int) $this->context->language->id;
        $title = $this->trans('Your rights on this purchase', [], 'Modules.Bkguarantee.Shop');
        $alt = $this->trans(
            'EU harmonised notice on the legal guarantee of conformity',
            [],
            'Modules.Bkguarantee.Shop'
        );

        if (isset($params['template_html'])) {
            $params['template_html'] = BkGuaranteeEmail::insertIntoBody(
                $params['template_html'],
                BkGuaranteeEmail::htmlBlock($idLang, $title, $alt)
            );
        }
        if (isset($params['template_txt'])) {
            $params['template_txt'] .= BkGuaranteeEmail::textBlock($idLang, $title);
        }
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

    public function hookActionFrontControllerSetMedia()
    {
        if (!$this->shouldRender()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'bkguarantee-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );

        if (BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_NESTED)) {
            $this->context->controller->registerJavascript(
                'bkguarantee-garan',
                'modules/' . $this->name . '/views/js/garan.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    /**
     * Los tres puntos de la ficha se registran siempre y solo pinta el elegido: así cambiar de
     * sitio es un desplegable y no una reinstalación, y el comerciante puede además moverlo desde
     * Posiciones sin que el módulo se entere.
     */
    public function hookDisplayProductAdditionalInfo(array $params)
    {
        return $this->renderProduct('info', $params) . $this->renderGaran('info', $params);
    }

    public function hookDisplayAfterProductThumbs(array $params)
    {
        return $this->renderProduct('thumbs', $params) . $this->renderGaran('thumbs', $params);
    }

    public function hookDisplayFooterProduct(array $params)
    {
        return $this->renderProduct('footer', $params) . $this->renderGaran('footer', $params);
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
            || !BkGuaranteeConfig::isOn(BkGuaranteeConfig::ENABLED)
            || BkGuaranteeConfig::getGaranPlacement() !== $placement
        ) {
            return '';
        }

        $idProduct = $this->productIdFrom($params);
        $label = BkGuaranteeRule::labelFor($idProduct);
        if ($label === null) {
            return '';
        }

        $width = BkGuaranteeConfig::getGaranWidth();

        $this->smarty->assign([
            'bkgaran_art' => $this->assetUrl('views/img/garan-blank.png'),
            'bkgaran_nested_art' => $this->assetUrl('views/img/garan-nested.png'),
            'bkgaran_width' => $width,
            // La figura anidada es apaisada: al ancho de la etiqueta entera se vuelve un cartel.
            'bkgaran_badge_width' => min($width, self::GARAN_BADGE_MAX_WIDTH),
            'bkgaran_nested' => BkGuaranteeConfig::isOn(BkGuaranteeConfig::GARAN_NESTED),
            'bkgaran_years' => (int) $label['years'],
            'bkgaran_brand' => $label['brand'],
            'bkgaran_model' => $label['model'],
            'bkgaran_uid' => $idProduct . '-' . $placement,
            'bkgaran_alt' => $this->trans(
                'GARAN label: producer durability guarantee in years',
                [],
                'Modules.Bkguarantee.Shop'
            ),
            'bkgaran_toggle' => $this->trans('Producer guarantee in years', [], 'Modules.Bkguarantee.Shop'),
            'bkgaran_more' => $this->trans('See the full label', [], 'Modules.Bkguarantee.Shop'),
        ]);

        return $this->fetch('module:bkguarantee/views/templates/hook/garan.tpl');
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

    public function hookDisplayPaymentTop(array $params)
    {
        return $this->renderCheckout('payment');
    }

    public function hookDisplayCheckoutSummaryTop(array $params)
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
     * Traducción accesible desde los controladores del módulo.
     *
     * `Module::trans()` es protected y los controladores no la alcanzan; y `AdminController` no
     * tiene `trans()` propio hasta 1.7.6, así que llamarlo desde allí revienta en 1.7.5. Este
     * envoltorio, que sí vive dentro de la clase del módulo, funciona igual de 1.7.5 a 9.
     *
     * @param string $id
     * @param array  $parameters
     * @param string $domain
     *
     * @return string
     */
    public function t($id, array $parameters = [], $domain = null)
    {
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
        $path = _PS_MODULE_DIR_ . $this->name . '/' . $relative;
        $stamp = file_exists($path) ? filemtime($path) : $this->version;

        return $this->_path . $relative . '?v=' . $stamp;
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
            'bkguar_alt' => $this->trans(
                'EU harmonised notice on the legal guarantee of conformity',
                [],
                'Modules.Bkguarantee.Shop'
            ),
            'bkguar_full' => $this->trans('View full size', [], 'Modules.Bkguarantee.Shop'),
            'bkguar_title' => $this->trans('EU legal guarantee', [], 'Modules.Bkguarantee.Shop'),
            'bkguar_band_title' => $this->trans('Your rights on this purchase', [], 'Modules.Bkguarantee.Shop'),
            'bkguar_band_text' => $this->trans(
                'This is the official European Union notice on the legal guarantee of conformity, reproduced exactly as the Commission publishes it.',
                [],
                'Modules.Bkguarantee.Shop'
            ),
        ]);

        return $this->fetch('module:bkguarantee/views/templates/hook/notice.tpl');
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
