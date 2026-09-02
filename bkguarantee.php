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
    const TAB_CLASS = 'AdminBkGuaranteeConfig';
    /** Pestaña padre: la pantalla vive bajo Configurar, junto al resto de ajustes de la tienda */
    const TAB_PARENT = 'AdminParentPreferences';

    /**
     * displayProductAdditionalInfo cae bajo el bloque de compra, que es donde la oferta queda a
     * la vista; displayCheckoutSubtotalDetails repite el aviso en el resumen del pedido, el
     * último punto antes de que el contrato se cierre.
     *
     * @var array
     */
    private $hooks = [
        'displayProductAdditionalInfo',
        'displayCheckoutSubtotalDetails',
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
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => '9.99.99'];

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

        BkGuaranteeConfig::installDefaults();

        if (!$this->installTab()) {
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

        $this->uninstallTab();
        BkGuaranteeConfig::uninstallKeys();
        BkGuaranteeLogger::confirmation('Módulo desinstalado');

        return parent::uninstall();
    }

    /**
     * La pestaña se monta a mano y con nombre por idioma: `Tab::$name` es multiidioma, así que
     * sin rellenar todos los idiomas activos la entrada del menú sale vacía para el empleado que
     * trabaje en cualquiera de los otros.
     *
     * @return bool
     */
    private function installTab()
    {
        if (Tab::getIdFromClassName(self::TAB_CLASS)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = self::TAB_CLASS;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName(self::TAB_PARENT);
        $tab->active = 1;
        $tab->icon = 'verified_user';

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'BK EU Guarantee';
        }

        return (bool) $tab->add();
    }

    private function uninstallTab()
    {
        $id = (int) Tab::getIdFromClassName(self::TAB_CLASS);
        if (!$id) {
            return;
        }

        $tab = new Tab($id);
        if (Validate::isLoadedObject($tab)) {
            $tab->delete();
        }
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
    }

    public function hookDisplayProductAdditionalInfo(array $params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_PRODUCT)) {
            return '';
        }

        return $this->renderNotice('product');
    }

    public function hookDisplayCheckoutSubtotalDetails(array $params)
    {
        if (!BkGuaranteeConfig::isOn(BkGuaranteeConfig::ON_CHECKOUT)) {
            return '';
        }

        return $this->renderNotice('checkout');
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
            'bkguar_alt' => $this->trans(
                'EU harmonised notice on the legal guarantee of conformity',
                [],
                'Modules.Bkguarantee.Shop'
            ),
            'bkguar_full' => $this->trans('View full size', [], 'Modules.Bkguarantee.Shop'),
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
