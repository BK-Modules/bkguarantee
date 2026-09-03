<?php
/**
 * Pantalla de configuración del módulo.
 *
 * La plantilla es propia y no un HelperForm: los ajustes son demasiados para una lista plana, y
 * hacían falta cosas que el helper no da — el selector de presentación por imagen, los campos que
 * se pliegan hasta que hacen falta y el buscador de productos por AJAX.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBkGuaranteeConfigController extends ModuleAdminController
{
    /** Resultados que devuelve el buscador de productos */
    const SEARCH_LIMIT = 12;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->assetUrl('views/css/admin.css'), 'all', null, false);
        // El CSS del front se carga también aquí: el selector de presentación enseña el aviso tal
        // como se verá en la tienda, y todo cuelga de .bkguar, así que no toca el back office.
        $this->addCSS($this->module->assetUrl('views/css/front.css'), 'all', null, false);
        $this->addJqueryPlugin('chosen');
        $this->addJS($this->module->assetUrl('views/js/config.js'), false);
    }

    public function initContent()
    {
        if (Tools::isSubmit('submitBkGuaranteeConfig')) {
            $this->processForm();
        }

        $this->content .= $this->renderConfig();
        $this->content .= $this->renderInfoPanel();

        parent::initContent();
    }

    /**
     * Buscador del campo de productos excluidos. Devuelve JSON y termina aquí: la pantalla entera
     * no hace falta para resolver una búsqueda.
     */
    public function ajaxProcessBkSearchProduct()
    {
        $query = trim((string) Tools::getValue('bkguar_q'));
        $out = [];

        if (Tools::strlen($query) >= 2) {
            $escaped = pSQL($query);
            $rows = Db::getInstance()->executeS(
                'SELECT p.`id_product`, pl.`name`, p.`reference`
                 FROM `' . _DB_PREFIX_ . 'product` p
                 INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . (int) $this->context->language->id . '
                 WHERE pl.`name` LIKE "%' . $escaped . '%"
                    OR p.`reference` LIKE "%' . $escaped . '%"
                    OR p.`id_product` = ' . (int) $query . '
                 GROUP BY p.`id_product`
                 ORDER BY pl.`name` ASC
                 LIMIT ' . (int) self::SEARCH_LIMIT
            );

            foreach ((array) $rows as $row) {
                $out[] = [
                    'id' => (int) $row['id_product'],
                    'name' => $row['name'],
                    'reference' => $row['reference'],
                ];
            }
        }

        header('Content-Type: application/json');
        exit(json_encode($out));
    }

    private function processForm()
    {
        $width = (int) Tools::getValue(BkGuaranteeConfig::WIDTH);
        $garanWidth = (int) Tools::getValue(BkGuaranteeConfig::GARAN_WIDTH);

        $bools = [
            BkGuaranteeConfig::ENABLED, BkGuaranteeConfig::ON_PRODUCT, BkGuaranteeConfig::ON_CHECKOUT,
            BkGuaranteeConfig::ON_EMAIL, BkGuaranteeConfig::EMAIL_ATTACH, BkGuaranteeConfig::HIDE_FOR_B2B,
            BkGuaranteeConfig::SKIP_VIRTUAL, BkGuaranteeConfig::GARAN_ON, BkGuaranteeConfig::GARAN_NESTED,
            BkGuaranteeConfig::DEBUG,
        ];
        foreach ($bools as $key) {
            Configuration::updateValue($key, (int) Tools::getValue($key));
        }

        Configuration::updateValue(
            BkGuaranteeConfig::WIDTH,
            max(BkGuaranteeConfig::WIDTH_MIN, min(BkGuaranteeConfig::WIDTH_MAX, $width ?: 620))
        );
        Configuration::updateValue(BkGuaranteeConfig::GARAN_WIDTH, max(180, min(520, $garanWidth ?: 420)));

        $this->saveFromList(BkGuaranteeConfig::STYLE, BkGuaranteeConfig::STYLES, 'band');
        $this->saveFromList(BkGuaranteeConfig::ALIGN, BkGuaranteeConfig::ALIGNS, 'left');
        $this->saveFromList(BkGuaranteeConfig::SCOPE_MODE, BkGuaranteeConfig::SCOPE_MODES, 'all');
        $this->saveFromKeys(BkGuaranteeConfig::PLACEMENT, BkGuaranteeConfig::PLACEMENTS, 'footer');
        $this->saveFromKeys(BkGuaranteeConfig::GARAN_PLACEMENT, BkGuaranteeConfig::PLACEMENTS, 'thumbs');
        $this->saveFromKeys(BkGuaranteeConfig::CHECKOUT_PLACEMENT, BkGuaranteeConfig::CHECKOUT_PLACEMENTS, 'summary');

        foreach ([BkGuaranteeConfig::INCLUDED_CATEGORIES, BkGuaranteeConfig::EXCLUDED_CATEGORIES,
                  BkGuaranteeConfig::EXCLUDED_PRODUCTS, BkGuaranteeConfig::B2B_GROUPS] as $key) {
            Configuration::updateValue($key, $this->idListFrom($key));
        }

        BkGuaranteeLogger::confirmation('Configuración guardada');
        $this->confirmations[] = $this->module->t('Settings updated.', [], 'Modules.Bkguarantee.Admin');
    }

    /**
     * @param string $key
     * @param array  $allowed
     * @param string $fallback
     */
    private function saveFromList($key, array $allowed, $fallback)
    {
        $value = Tools::getValue($key);
        Configuration::updateValue($key, in_array($value, $allowed, true) ? $value : $fallback);
    }

    /**
     * @param string $key
     * @param array  $allowed Mapa clave => hook
     * @param string $fallback
     */
    private function saveFromKeys($key, array $allowed, $fallback)
    {
        $value = Tools::getValue($key);
        Configuration::updateValue($key, isset($allowed[$value]) ? $value : $fallback);
    }

    /**
     * Normaliza a lista de enteros separados por comas, venga de un multiselect o del campo oculto
     * del buscador de productos.
     *
     * @param string $field
     *
     * @return string
     */
    private function idListFrom($field)
    {
        $raw = Tools::getValue($field);
        $ids = is_array($raw) ? $raw : preg_split('/[^0-9]+/', (string) $raw);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return implode(',', $ids);
    }

    private function renderConfig()
    {
        $categories = [];
        foreach (Category::getSimpleCategories((int) $this->context->language->id) as $category) {
            $categories[] = ['id' => (int) $category['id_category'], 'name' => $category['name']];
        }

        $groups = [];
        foreach (Group::getGroups((int) $this->context->language->id) as $group) {
            $groups[] = ['id' => (int) $group['id_group'], 'name' => $group['name']];
        }

        $rows = BkGuaranteeNotice::coverage();
        $preview = null;
        $iso = $this->context->language->iso_code;
        if (BkGuaranteeNotice::pathFor($iso) !== null) {
            $preview = BkGuaranteeNotice::urlFor($iso);
        } else {
            foreach ($rows as $row) {
                if ($row['ready']) {
                    $preview = BkGuaranteeNotice::urlFor($row['iso']);
                    break;
                }
            }
        }

        $values = [];
        foreach (array_keys(BkGuaranteeConfig::getDefaults()) as $key) {
            $values[$key] = Configuration::get($key);
        }
        $values[BkGuaranteeConfig::WIDTH] = BkGuaranteeConfig::getWidth();
        $values[BkGuaranteeConfig::GARAN_WIDTH] = BkGuaranteeConfig::getGaranWidth();
        $values[BkGuaranteeConfig::STYLE] = BkGuaranteeConfig::getStyle();
        $values[BkGuaranteeConfig::ALIGN] = BkGuaranteeConfig::getAlign();
        $values[BkGuaranteeConfig::PLACEMENT] = BkGuaranteeConfig::getPlacement();
        $values[BkGuaranteeConfig::GARAN_PLACEMENT] = BkGuaranteeConfig::getGaranPlacement();
        $values[BkGuaranteeConfig::CHECKOUT_PLACEMENT] = BkGuaranteeConfig::getCheckoutPlacement();
        $values[BkGuaranteeConfig::SCOPE_MODE] = BkGuaranteeConfig::getScopeMode();

        $dir = _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/';
        $searchUrl = self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=BkSearchProduct';

        $this->context->smarty->assign([
            'bkguar_rows' => $rows,
            'bkguar_missing' => BkGuaranteeNotice::missingLanguages(),
            'bkguar_preview' => $preview,
            'bkguar_dir' => 'modules/bkguarantee/' . BkGuaranteeNotice::DIR,
            'bkguar_styles' => BkGuaranteeConfig::STYLES,
            'bkguar_style' => BkGuaranteeConfig::getStyle(),
            'bkguar_qr' => BkGuaranteeNotice::qrCheck(BkGuaranteeConfig::getWidth()),
            'bkguar_qr_garan' => BkGuaranteeNotice::qrCheck(
                BkGuaranteeConfig::getGaranWidth(),
                BkGuaranteeNotice::QR_RATIO_LABEL
            ),
            'bkguar_qr_ideal' => BkGuaranteeNotice::widthForComfortableQr(),
            'bkguar_garan_width' => BkGuaranteeConfig::getGaranWidth(),
            'bkguar_categories' => $categories,
            'bkguar_groups' => $groups,
            'bkguar_cat_in' => BkGuaranteeConfig::getIncludedCategories(),
            'bkguar_cat_out' => BkGuaranteeConfig::getExcludedCategories(),
            'bkguar_b2b' => BkGuaranteeConfig::getB2bGroups(),
            'bkguar_prod_out' => $this->productChips(BkGuaranteeConfig::getExcludedProducts()),
            'bkguar_v' => $values,
            'bkguar_switch' => $dir . '_switch.tpl',
            'bkguar_finder' => $dir . '_finder.tpl',
            'bkguar_action' => self::$currentIndex . '&token=' . $this->token,
            'bkguar_rules_url' => $this->context->link->getAdminLink('AdminBkGuaranteeRules'),
        ]);

        return '<script>var bkguarSearchUrl = ' . json_encode($searchUrl) . ';</script>'
            . $this->context->smarty->fetch($dir . 'config.tpl');
    }

    /**
     * Nombre de cada producto excluido, para que las fichas digan algo más que un número.
     *
     * @param array $ids
     *
     * @return array
     */
    private function productChips(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT p.`id_product`, pl.`name` FROM `' . _DB_PREFIX_ . 'product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . (int) $this->context->language->id . '
             WHERE p.`id_product` IN (' . implode(',', array_map('intval', $ids)) . ')
             GROUP BY p.`id_product`'
        );

        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = ['id' => (int) $row['id_product'], 'name' => $row['name']];
        }

        return $out;
    }

    private function renderInfoPanel()
    {
        $remote = \BkModules\Registry\V1\Catalog::fetch(
            _PS_CACHE_DIR_ . 'bkguarantee_catalog.json',
            $this->context->language->iso_code,
            $this->module->name
        );

        if (empty($remote['catalog'])) {
            $remote['catalog'] = $this->getFallbackCatalog();
        }

        // Las imágenes se sirven desde la propia tienda: enlazadas a bkmodules.com son, para el
        // navegador, peticiones a otro sitio, y cualquier bloqueador del lado del cliente las
        // retiene sin cerrarlas —la pantalla se queda cargando sin fin.
        $remote = BkGuaranteeRemoteImage::localize($remote);

        $remote['links'] = array_merge(
            [
                'licenses' => 'https://bkmodules.com',
                'contact' => 'https://bkmodules.com',
                'blog' => 'https://bkmodules.com',
            ],
            array_filter(isset($remote['links']) ? $remote['links'] : [])
        );

        $this->context->smarty->assign('bk_remote', $remote);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/info-panel.tpl'
        );
    }

    /**
     * Lista mínima para cuando bkmodules.com no contesta.
     *
     * @return array
     */
    private function getFallbackCatalog()
    {
        return [
            [
                'name' => 'B2B & VIES Validation',
                'description' => 'Professional registration validated against the EU VIES database.',
                'url' => 'https://bkmodules.com',
                'image' => '',
            ],
            [
                'name' => 'Right of Withdrawal',
                'description' => 'The online withdrawal function required by EU Directive 2023/2673.',
                'url' => 'https://bkmodules.com',
                'image' => '',
            ],
            [
                'name' => 'ALTCHA Anti-Spam',
                'description' => 'A captcha that runs on your own server, with no third-party keys.',
                'url' => 'https://bkmodules.com',
                'image' => '',
            ],
        ];
    }
}
