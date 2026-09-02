<?php
/**
 * Reglas de garantía comercial de durabilidad: listado y formulario.
 *
 * Listado y CRUD estándar de PrestaShop sobre el ObjectModel, para que el comerciante ordene,
 * pagine, filtre y borre con lo que ya conoce del back office.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBkGuaranteeRulesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'bk_guarantee_rule';
        $this->identifier = 'id_guarantee_rule';
        $this->className = 'BkGuaranteeRule';
        $this->lang = false;
        $this->allow_export = true;
        $this->_defaultOrderBy = 'priority';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_guarantee_rule' => [
                'title' => $this->trans('ID', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Rule', [], 'Modules.Bkguarantee.Admin'),
            ],
            'filter_type' => [
                'title' => $this->trans('Applies by', [], 'Modules.Bkguarantee.Admin'),
                'type' => 'select',
                'list' => $this->translatedFilterTypes(),
                'filter_key' => 'a!filter_type',
                'callback' => 'renderFilterType',
            ],
            'filter_values' => [
                'title' => $this->trans('Targets', [], 'Modules.Bkguarantee.Admin'),
                'search' => false,
                'orderby' => false,
                'callback' => 'renderTargets',
            ],
            'years' => [
                'title' => $this->trans('Years', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'brand' => [
                'title' => $this->trans('Producer', [], 'Modules.Bkguarantee.Admin'),
                'callback' => 'renderInherited',
            ],
            'model' => [
                'title' => $this->trans('Model identifier', [], 'Modules.Bkguarantee.Admin'),
                'callback' => 'renderInherited',
            ],
            'id_shop' => [
                'title' => $this->trans('Shop', [], 'Modules.Bkguarantee.Admin'),
                'callback' => 'renderShop',
                'search' => false,
                'orderby' => true,
            ],
            'priority' => [
                'title' => $this->trans('Priority', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'active' => [
                'title' => $this->trans('Enabled', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Modules.Bkguarantee.Admin'),
                'confirm' => $this->trans('Delete the selected rules?', [], 'Modules.Bkguarantee.Admin'),
                'icon' => 'icon-trash',
            ],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->assetUrl('views/css/admin.css'), 'all', null, false);
        $this->addJS($this->module->assetUrl('views/js/rules.js'), false);
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->trans('Add a rule', [], 'Modules.Bkguarantee.Admin'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return array
     */
    private function translatedFilterTypes()
    {
        return [
            BkGuaranteeRule::FILTER_CATEGORY => $this->trans('Category', [], 'Modules.Bkguarantee.Admin'),
            BkGuaranteeRule::FILTER_MANUFACTURER => $this->trans('Brand', [], 'Modules.Bkguarantee.Admin'),
            BkGuaranteeRule::FILTER_PRODUCTS => $this->trans('Specific products', [], 'Modules.Bkguarantee.Admin'),
        ];
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function renderFilterType($value)
    {
        $types = $this->translatedFilterTypes();

        return isset($types[$value]) ? $types[$value] : $value;
    }

    /**
     * En el listado se enseña el recuento, no la lista entera: una regla de marca puede tener
     * cientos de identificadores y reventaría la columna.
     *
     * @param string $value
     *
     * @return string
     */
    public function renderTargets($value)
    {
        $ids = array_filter(array_map('intval', preg_split('/[^0-9]+/', (string) $value)));
        $count = count(array_unique($ids));

        if ($count === 0) {
            return '<span class="badge badge-danger">' . $this->trans('Empty', [], 'Modules.Bkguarantee.Admin') . '</span>';
        }

        return $count . ' &middot; <span class="text-muted">' . Tools::substr(implode(', ', $ids), 0, 40) . '</span>';
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function renderInherited($value)
    {
        if (trim((string) $value) === '') {
            return '<span class="text-muted"><em>' . $this->trans('From the product', [], 'Modules.Bkguarantee.Admin') . '</em></span>';
        }

        return $value;
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function renderShop($value)
    {
        if (!(int) $value) {
            return '<span class="text-muted"><em>' . $this->trans('All shops', [], 'Modules.Bkguarantee.Admin') . '</em></span>';
        }

        $shop = new Shop((int) $value);

        return Validate::isLoadedObject($shop) ? $shop->name : (int) $value;
    }

    public function renderForm()
    {
        $shops = [['id' => 0, 'name' => $this->trans('All shops', [], 'Modules.Bkguarantee.Admin')]];
        foreach (Shop::getShops(false) as $shop) {
            $shops[] = ['id' => (int) $shop['id_shop'], 'name' => $shop['name']];
        }

        $categories = [];
        foreach (Category::getSimpleCategories((int) $this->context->language->id) as $category) {
            $categories[] = ['id' => (int) $category['id_category'], 'name' => $category['name']];
        }

        $manufacturers = [];
        foreach (Manufacturer::getManufacturers() as $manufacturer) {
            $manufacturers[] = ['id' => (int) $manufacturer['id_manufacturer'], 'name' => $manufacturer['name']];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Durability guarantee rule', [], 'Modules.Bkguarantee.Admin'),
                'icon' => 'icon-certificate',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Rule', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'name',
                    'required' => true,
                    'desc' => $this->trans('Only a name for you, so you can find it in the list.', [], 'Modules.Bkguarantee.Admin'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->trans('Applies by', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'filter_type',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => BkGuaranteeRule::FILTER_CATEGORY, 'name' => $this->trans('Category', [], 'Modules.Bkguarantee.Admin')],
                            ['id' => BkGuaranteeRule::FILTER_MANUFACTURER, 'name' => $this->trans('Brand', [], 'Modules.Bkguarantee.Admin')],
                            ['id' => BkGuaranteeRule::FILTER_PRODUCTS, 'name' => $this->trans('Specific products', [], 'Modules.Bkguarantee.Admin')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'multiple' => true,
                    'class' => 'chosen bkguar-target bkguar-target--category',
                    'label' => $this->trans('Categories', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'bkguar_categories[]',
                    'options' => ['query' => $categories, 'id' => 'id', 'name' => 'name'],
                    'form_group_class' => 'bkguar-row bkguar-row--category',
                ],
                [
                    'type' => 'select',
                    'multiple' => true,
                    'class' => 'chosen bkguar-target bkguar-target--manufacturer',
                    'label' => $this->trans('Brands', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'bkguar_manufacturers[]',
                    'options' => ['query' => $manufacturers, 'id' => 'id', 'name' => 'name'],
                    'form_group_class' => 'bkguar-row bkguar-row--manufacturer',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Products', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'bkguar_products',
                    'desc' => $this->trans('Product IDs separated by commas.', [], 'Modules.Bkguarantee.Admin'),
                    'form_group_class' => 'bkguar-row bkguar-row--products',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Years', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'years',
                    'required' => true,
                    'class' => 'fixed-width-xs',
                    'desc' => $this->trans('The producer guarantee only earns a label above two years, because two is what the legal guarantee already covers.', [], 'Modules.Bkguarantee.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Producer', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'brand',
                    'desc' => $this->trans('Leave it empty to use the brand of each product.', [], 'Modules.Bkguarantee.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Model identifier', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'model',
                    'desc' => $this->trans('Leave it empty to use the MPN of each product. A rule covering several models needs it empty, or they would all claim the same one.', [], 'Modules.Bkguarantee.Admin'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->trans('Shop', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'id_shop',
                    'desc' => $this->trans('A rule for one shop wins over an equivalent rule for all of them.', [], 'Modules.Bkguarantee.Admin'),
                    'options' => ['query' => $shops, 'id' => 'id', 'name' => 'name'],
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Priority', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'priority',
                    'class' => 'fixed-width-xs',
                    'desc' => $this->trans('When two rules match the same product, the higher priority wins.', [], 'Modules.Bkguarantee.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Enabled', [], 'Modules.Bkguarantee.Admin'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.Bkguarantee.Admin')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Bkguarantee.Admin')],
                    ],
                ],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Modules.Bkguarantee.Admin')],
        ];

        $rule = $this->loadObject(true);
        $values = ($rule instanceof BkGuaranteeRule && $rule->id) ? $rule->values() : [];
        $type = ($rule instanceof BkGuaranteeRule && $rule->id) ? $rule->filter_type : BkGuaranteeRule::FILTER_CATEGORY;

        $this->fields_value['bkguar_categories[]'] = $type === BkGuaranteeRule::FILTER_CATEGORY ? $values : [];
        $this->fields_value['bkguar_manufacturers[]'] = $type === BkGuaranteeRule::FILTER_MANUFACTURER ? $values : [];
        $this->fields_value['bkguar_products'] = $type === BkGuaranteeRule::FILTER_PRODUCTS ? implode(', ', $values) : '';

        // Una regla recién creada nace activa: nadie da de alta una regla para dejarla apagada, y
        // el interruptor por defecto en 'No' hacía que la primera se guardara sin efecto.
        if (!($rule instanceof BkGuaranteeRule) || !$rule->id) {
            $this->fields_value['active'] = 1;
            $this->fields_value['priority'] = 0;
            $this->fields_value['id_shop'] = Shop::isFeatureActive() ? (int) $this->context->shop->id : 0;
        }

        return parent::renderForm();
    }

    /**
     * Los tres campos de destino son uno solo en la tabla: se vuelca el que corresponda al filtro
     * elegido antes de que el ObjectModel copie el POST.
     */
    public function processSave()
    {
        $type = Tools::getValue('filter_type');
        switch ($type) {
            case BkGuaranteeRule::FILTER_MANUFACTURER:
                $raw = Tools::getValue('bkguar_manufacturers');
                break;
            case BkGuaranteeRule::FILTER_PRODUCTS:
                $raw = Tools::getValue('bkguar_products');
                break;
            default:
                $raw = Tools::getValue('bkguar_categories');
        }

        $ids = is_array($raw) ? $raw : preg_split('/[^0-9]+/', (string) $raw);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        $_POST['filter_values'] = implode(',', $ids);

        if (empty($ids)) {
            $this->errors[] = $this->trans('Choose at least one target for the rule.', [], 'Modules.Bkguarantee.Admin');
        }
        if ((int) Tools::getValue('years') < BkGuaranteeRule::MIN_YEARS) {
            $this->errors[] = $this->trans('A durability guarantee earns a label only above two years.', [], 'Modules.Bkguarantee.Admin');
        }

        if (!empty($this->errors)) {
            $this->display = Tools::getValue('id_guarantee_rule') ? 'edit' : 'add';

            return false;
        }

        return parent::processSave();
    }
}
