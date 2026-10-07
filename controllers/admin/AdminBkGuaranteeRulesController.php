<?php
/**
 * Datos del fabricante: listado y formulario de las reglas.
 *
 * Listado y CRUD estándar de PrestaShop sobre el ObjectModel, para que el comerciante ordene,
 * pagine, filtre y borre con lo que ya conoce del back office. Una regla dice qué afirma el
 * fabricante de un conjunto de productos en tres bloques —garantía GARAN, actualizaciones de
 * software y reparación— y el formulario tiene una sección por bloque.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBkGuaranteeRulesController extends ModuleAdminController
{
    /** Vista previa de la importación en curso; vacía mientras no se sube ningún fichero */
    private $preview = [];

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
                'title' => $this->module->t('ID', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->module->t('Rule', [], 'Modules.Bkguarantee.Admin'),
            ],
            'filter_type' => [
                'title' => $this->module->t('Applies by', [], 'Modules.Bkguarantee.Admin'),
                'type' => 'select',
                'list' => $this->translatedFilterTypes(),
                'filter_key' => 'a!filter_type',
                'callback' => 'renderFilterType',
            ],
            'filter_values' => [
                'title' => $this->module->t('Targets', [], 'Modules.Bkguarantee.Admin'),
                'search' => false,
                'orderby' => false,
                'callback' => 'renderTargets',
            ],
            'years' => [
                'title' => 'GARAN',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'callback' => 'renderYears',
            ],
            'brand' => [
                'title' => $this->module->t('Producer', [], 'Modules.Bkguarantee.Admin'),
                'callback' => 'renderInherited',
            ],
            'model' => [
                'title' => $this->module->t('Model identifier', [], 'Modules.Bkguarantee.Admin'),
                'callback' => 'renderInherited',
            ],
            'updates_mode' => [
                'title' => $this->module->t('Software updates', [], 'Modules.Bkguarantee.Admin'),
                'search' => false,
                'orderby' => false,
                'callback' => 'renderUpdates',
            ],
            'repair_mode' => [
                'title' => $this->module->t('Repair', [], 'Modules.Bkguarantee.Admin'),
                'search' => false,
                'orderby' => false,
                'callback' => 'renderRepair',
            ],
            'id_shop' => [
                'title' => $this->module->t('Shop', [], 'Modules.Bkguarantee.Admin'),
                'callback' => 'renderShop',
                'search' => false,
                'orderby' => true,
            ],
            'priority' => [
                'title' => $this->module->t('Priority', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'active' => [
                'title' => $this->module->t('Enabled', [], 'Modules.Bkguarantee.Admin'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->module->t('Delete selected', [], 'Modules.Bkguarantee.Admin'),
                'confirm' => $this->module->t('Delete the selected rules?', [], 'Modules.Bkguarantee.Admin'),
                'icon' => 'icon-trash',
            ],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->assetUrl('views/css/admin.css'), 'all', null, false);
        $this->addJqueryPlugin('chosen');
        $this->addJS($this->module->assetUrl('views/js/config.js'), false);
        $this->addJS($this->module->assetUrl('views/js/rules.js'), false);
    }

    /**
     * Fichero de trabajo de la importación del empleado que la está haciendo: se conserva entre la
     * vista previa y la confirmación, y se borra en cuanto se aplica.
     *
     * @return string
     */
    private function importPath()
    {
        return _PS_CACHE_DIR_ . 'bkguarantee-import-' . (int) $this->context->employee->id . '.csv';
    }

    /**
     * @param string $name
     * @param string $content
     */
    private function download($name, $content)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitBkGuarTemplate')) {
            $this->download('bkguarantee-rules-template.csv', BkGuaranteeRuleCsv::template());
        }

        if (Tools::isSubmit('submitBkGuarExport')) {
            $this->download('bkguarantee-rules.csv', BkGuaranteeRuleCsv::exportAll());
        }

        if (Tools::isSubmit('submitBkGuarImport')) {
            $this->processUpload();

            return true;
        }

        if (Tools::isSubmit('submitBkGuarImportConfirm')) {
            $this->processImport();

            return true;
        }

        return parent::postProcess();
    }

    /**
     * Subir no importa nada: deja el fichero listo y enseña lo que se haría con él.
     */
    private function processUpload()
    {
        if (empty($_FILES['bkguar_csv']['tmp_name']) || !is_uploaded_file($_FILES['bkguar_csv']['tmp_name'])) {
            $this->errors[] = $this->module->t('Choose a CSV file to import.', [], 'Modules.Bkguarantee.Admin');

            return;
        }

        if (!move_uploaded_file($_FILES['bkguar_csv']['tmp_name'], $this->importPath())) {
            $this->errors[] = $this->module->t('The file could not be stored for review.', [], 'Modules.Bkguarantee.Admin');

            return;
        }

        $parsed = BkGuaranteeRuleCsv::parse($this->importPath());
        if ($parsed['fatal'] !== null) {
            @unlink($this->importPath());
            $this->errors[] = trim(
                $this->module->t($parsed['fatal'], [], 'Modules.Bkguarantee.Admin') . ' ' . $parsed['detail']
            );

            return;
        }

        if (empty($parsed['rows'])) {
            @unlink($this->importPath());
            $this->errors[] = $this->module->t('The file has no rows.', [], 'Modules.Bkguarantee.Admin');

            return;
        }

        $this->preview = $parsed['rows'];
    }

    /**
     * Aplica exactamente lo que se enseñó en la vista previa: se vuelve a leer el mismo fichero.
     */
    private function processImport()
    {
        $parsed = BkGuaranteeRuleCsv::parse($this->importPath());
        if ($parsed['fatal'] !== null || empty($parsed['rows'])) {
            $this->errors[] = $this->module->t('The file to import is no longer available. Upload it again.', [], 'Modules.Bkguarantee.Admin');

            return;
        }

        $result = BkGuaranteeRuleCsv::apply($parsed['rows']);
        @unlink($this->importPath());

        $this->confirmations[] = sprintf(
            $this->module->t('Import finished: %1$d rules created, %2$d updated, %3$d skipped.', [], 'Modules.Bkguarantee.Admin'),
            $result['created'],
            $result['updated'],
            $result['skipped']
        );
    }

    /**
     * El panel de CSV va sobre el listado: es la herramienta de quien tiene el catálogo en una hoja
     * de cálculo, y ahí es donde la busca.
     *
     * @return string
     */
    public function renderList()
    {
        return $this->renderCsvPanel() . parent::renderList();
    }

    /**
     * @return string
     */
    private function renderCsvPanel()
    {
        $rows = [];
        foreach ($this->preview as $row) {
            $errors = [];
            foreach ($row['errors'] as $error) {
                $errors[] = $this->module->t($error, [], 'Modules.Bkguarantee.Admin');
            }
            $row['errors'] = $errors;
            $row['filter_label'] = $this->renderFilterType($row['data']['filter_type']);
            // Una columna que el fichero no trae deja la regla como está; una regla nueva, heredando.
            $blocks = $row['data'] + ['updates_mode' => '', 'repair_mode' => ''];
            $row['updates_label'] = array_key_exists('updates_mode', $row['data']) || $row['action'] === 'new'
                ? $this->updatesStatus($blocks)
                : $this->unchanged();
            $row['repair_label'] = array_key_exists('repair_mode', $row['data']) || $row['action'] === 'new'
                ? $this->repairStatus($blocks, [])
                : $this->unchanged();
            $rows[] = $row;
        }

        $counts = ['new' => 0, 'update' => 0, 'skip' => 0];
        foreach ($this->preview as $row) {
            ++$counts[$row['action']];
        }

        $this->context->smarty->assign([
            'bkguar_csv_action' => self::$currentIndex . '&token=' . $this->token,
            'bkguar_preview' => $rows,
            'bkguar_counts' => $counts,
            'bkguar_max_rows' => BkGuaranteeRuleCsv::MAX_ROWS,
            'bkguar_columns' => implode(', ', BkGuaranteeRuleCsv::columns()),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/import.tpl'
        );
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->module->t('Add a rule', [], 'Modules.Bkguarantee.Admin'),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['bkguar_export'] = [
                'href' => self::$currentIndex . '&submitBkGuarExport=1&token=' . $this->token,
                'desc' => $this->module->t('Export to CSV', [], 'Modules.Bkguarantee.Admin'),
                'icon' => 'process-icon-export',
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
            BkGuaranteeRule::FILTER_CATEGORY => $this->module->t('Category', [], 'Modules.Bkguarantee.Admin'),
            BkGuaranteeRule::FILTER_MANUFACTURER => $this->module->t('Brand', [], 'Modules.Bkguarantee.Admin'),
            BkGuaranteeRule::FILTER_PRODUCTS => $this->module->t('Specific products', [], 'Modules.Bkguarantee.Admin'),
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
            return '<span class="badge badge-danger">' . $this->module->t('Empty', [], 'Modules.Bkguarantee.Admin') . '</span>';
        }

        return $count . ' &middot; <span class="text-muted">' . Tools::substr(implode(', ', $ids), 0, 40) . '</span>';
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function renderYears($value)
    {
        return (int) $value > 0 ? (string) (int) $value : '<span class="text-muted">&mdash;</span>';
    }

    /**
     * @param string $value
     * @param array  $row
     *
     * @return string
     */
    public function renderUpdates($value, $row)
    {
        return $this->updatesStatus($row);
    }

    /**
     * La reparación avisa en el propio listado de los idiomas a los que les falta un texto: en ellos
     * esa línea no sale, y es aquí donde el comerciante lo ve sin abrir regla por regla.
     *
     * @param string $value
     * @param array  $row
     *
     * @return string
     */
    public function renderRepair($value, $row)
    {
        $missing = (string) $value === BkGuaranteeRule::REPAIR_PARTS
            ? BkGuaranteeRule::missingTextLanguages((int) $row['id_guarantee_rule'])
            : [];

        return $this->repairStatus($row, $missing);
    }

    /**
     * @param array $data Fila de la regla
     *
     * @return string HTML corto para el listado y la vista previa del CSV
     */
    private function updatesStatus(array $data)
    {
        switch ((string) $data['updates_mode']) {
            case BkGuaranteeRule::UPDATES_NONE:
                return $this->muted($this->module->t('Not provided', [], 'Modules.Bkguarantee.Admin'));
            case BkGuaranteeRule::UPDATES_DATE:
                return sprintf(
                    $this->module->t('Until %s', [], 'Modules.Bkguarantee.Admin'),
                    Tools::displayDate((string) $data['updates_until'])
                );
            case BkGuaranteeRule::UPDATES_YEARS:
                return sprintf(
                    $this->module->t('%d years', [], 'Modules.Bkguarantee.Admin'),
                    (int) $data['updates_years']
                );
        }

        return $this->muted($this->module->t('Inherits', [], 'Modules.Bkguarantee.Admin'));
    }

    /**
     * @param array $data    Fila de la regla
     * @param array $missing Idiomas sin alguno de los textos
     *
     * @return string
     */
    private function repairStatus(array $data, array $missing)
    {
        switch ((string) $data['repair_mode']) {
            case BkGuaranteeRule::REPAIR_NONE:
                return $this->muted($this->module->t('Not provided', [], 'Modules.Bkguarantee.Admin'));
            case BkGuaranteeRule::REPAIR_SCORE:
                return sprintf(
                    $this->module->t('Score %s', [], 'Modules.Bkguarantee.Admin'),
                    Tools::safeOutput((string) $data['repair_score'])
                );
            case BkGuaranteeRule::REPAIR_PARTS:
                $out = $this->module->t('Spare parts', [], 'Modules.Bkguarantee.Admin');
                if (!empty($missing)) {
                    $out .= ' <span class="badge badge-warning">' . Tools::safeOutput(sprintf(
                        $this->module->t('missing in %s', [], 'Modules.Bkguarantee.Admin'),
                        implode(', ', $missing)
                    )) . '</span>';
                }

                return $out;
        }

        return $this->muted($this->module->t('Inherits', [], 'Modules.Bkguarantee.Admin'));
    }

    /**
     * @return string
     */
    private function unchanged()
    {
        return $this->muted($this->module->t('Unchanged', [], 'Modules.Bkguarantee.Admin'));
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function muted($text)
    {
        return '<span class="text-muted"><em>' . Tools::safeOutput($text) . '</em></span>';
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function renderInherited($value)
    {
        if (trim((string) $value) === '') {
            return '<span class="text-muted"><em>' . $this->module->t('From the product', [], 'Modules.Bkguarantee.Admin') . '</em></span>';
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
            return '<span class="text-muted"><em>' . $this->module->t('All shops', [], 'Modules.Bkguarantee.Admin') . '</em></span>';
        }

        $shop = new Shop((int) $value);

        return Validate::isLoadedObject($shop) ? $shop->name : (int) $value;
    }

    public function renderForm()
    {
        $shops = [['id' => 0, 'name' => $this->module->t('All shops', [], 'Modules.Bkguarantee.Admin')]];
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

        $rule = $this->loadObject(true);
        $isNew = !($rule instanceof BkGuaranteeRule) || !$rule->id;

        // Lo que se escribe aquí obliga a la tienda: el aviso va junto a los campos que lo causan.
        $contract = Tools::safeOutput($this->module->t('What you enter here is shown to your customers before they buy and again in the order confirmation, and becomes part of the contract (§ 312d BGB, § 4 Abs. 4 FAGG, art. 49 c.5 Codice del consumo). Enter only what the manufacturer or provider states.', [], 'Modules.Bkguarantee.Admin'));
        $repairWarning = $contract;
        $missing = $isNew ? [] : BkGuaranteeRule::missingTextLanguages((int) $rule->id);
        if (!empty($missing)) {
            $repairWarning .= '<br><strong>' . Tools::safeOutput(sprintf(
                $this->module->t('Some texts are missing in %s: customers browsing in those languages do not see those lines.', [], 'Modules.Bkguarantee.Admin'),
                implode(', ', $missing)
            )) . '</strong>';
        }

        $inherit = $this->module->t('Inherit: what another matching rule says, for example the brand rule', [], 'Modules.Bkguarantee.Admin');
        $none = $this->module->t('Not applicable, or the manufacturer does not provide it: nothing is shown', [], 'Modules.Bkguarantee.Admin');

        $scores = [['id' => '', 'name' => '—']];
        foreach (BkGuaranteeRule::REPAIR_SCORES as $score) {
            $scores[] = ['id' => $score, 'name' => $score];
        }

        $texts = [];
        foreach (BkGuaranteeDurability::PARTS_LABELS as $field => $label) {
            $texts[] = [
                'type' => 'textarea',
                'lang' => true,
                'label' => $this->module->t($label, [], 'Modules.Bkguarantee.Admin'),
                'name' => $field,
                'maxlength' => BkGuaranteeRule::TEXT_MAX,
                'form_group_class' => 'bkguar-when--repair_mode--parts',
            ];
        }
        $texts[0]['desc'] = $this->module->t('An empty text is not shown, in that language only. Addresses starting with https:// become links.', [], 'Modules.Bkguarantee.Admin');

        $this->multiple_fieldsets = true;
        $this->fields_form = [
            ['form' => [
                'legend' => [
                    'title' => $this->module->t('Producer data rule', [], 'Modules.Bkguarantee.Admin'),
                    'icon' => 'icon-certificate',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->module->t('Rule', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'name',
                        'required' => true,
                        'desc' => $this->module->t('Only a name for you, so you can find it in the list.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->t('Applies by', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'filter_type',
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id' => BkGuaranteeRule::FILTER_CATEGORY, 'name' => $this->module->t('Category', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => BkGuaranteeRule::FILTER_MANUFACTURER, 'name' => $this->module->t('Brand', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => BkGuaranteeRule::FILTER_PRODUCTS, 'name' => $this->module->t('Specific products', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'class' => 'chosen bkguar-target bkguar-target--category',
                        'label' => $this->module->t('Categories', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'bkguar_categories[]',
                        'options' => ['query' => $categories, 'id' => 'id', 'name' => 'name'],
                        'form_group_class' => 'bkguar-row bkguar-row--category',
                    ],
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'class' => 'chosen bkguar-target bkguar-target--manufacturer',
                        'label' => $this->module->t('Brands', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'bkguar_manufacturers[]',
                        'options' => ['query' => $manufacturers, 'id' => 'id', 'name' => 'name'],
                        'form_group_class' => 'bkguar-row bkguar-row--manufacturer',
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->module->t('Products', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'bkguar_products_finder',
                        'html_content' => $this->renderFinder(),
                        'form_group_class' => 'bkguar-row bkguar-row--products',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->t('Shop', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'id_shop',
                        'desc' => $this->module->t('Between two rules of the same kind, the one for this shop wins over the one for all shops.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => ['query' => $shops, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->t('Priority', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'priority',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->module->t('The most specific rule wins on its own: a product rule over a brand rule, and a brand rule over a category rule, block by block, so a product rule with only an update date still takes the repair from its brand rule. Priority only decides between two rules of the same kind; on a tie, the oldest.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->t('Enabled', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'active',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->module->t('Yes', [], 'Modules.Bkguarantee.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->module->t('No', [], 'Modules.Bkguarantee.Admin')],
                        ],
                    ],
                ],
            ]],
            ['form' => [
                'legend' => [
                    'title' => $this->module->t('Producer durability guarantee (GARAN)', [], 'Modules.Bkguarantee.Admin'),
                    'icon' => 'icon-certificate',
                ],
                'description' => Tools::safeOutput($this->module->t('Only a durability guarantee the producer gives free of charge, on the whole product and for more than two years. Art. 246a § 1 Abs. 1 Nr. 11a EGBGB (DE), § 4 Abs. 1 Z 12a FAGG (AT), art. 49 c.1 lett. n-bis Codice del consumo (IT).', [], 'Modules.Bkguarantee.Admin')),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->module->t('Years', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'years',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->module->t('Leave it empty if this rule says nothing about the GARAN label. Otherwise 3 or more: the label only exists above the two years the legal guarantee already covers.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->t('Producer', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'brand',
                        'desc' => $this->module->t('Leave it empty to use the brand of each product.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->t('Model identifier', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'model',
                        'desc' => $this->module->t('Leave it empty to use the MPN of each product. A rule covering several models needs it empty, or they would all claim the same one.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                ],
            ]],
            ['form' => [
                'legend' => [
                    'title' => $this->module->t('Software updates', [], 'Modules.Bkguarantee.Admin'),
                    'icon' => 'icon-refresh',
                ],
                'description' => Tools::safeOutput($this->module->t('The minimum period during which the producer or provider supplies software updates, as a date or as a number of years, when they make it available. Art. 246a § 1 Abs. 1 Nr. 11c EGBGB (DE), § 4 Abs. 1 Z 12d FAGG (AT), art. 49 c.1 lett. n-quater Codice del consumo (IT).', [], 'Modules.Bkguarantee.Admin')),
                'warning' => $contract,
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->module->t('Software updates', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'updates_mode',
                        'values' => [
                            ['id' => 'updates_mode_inherit', 'value' => '', 'label' => $inherit],
                            ['id' => 'updates_mode_none', 'value' => BkGuaranteeRule::UPDATES_NONE, 'label' => $none],
                            ['id' => 'updates_mode_date', 'value' => BkGuaranteeRule::UPDATES_DATE, 'label' => $this->module->t('Until a date', [], 'Modules.Bkguarantee.Admin')],
                            ['id' => 'updates_mode_years', 'value' => BkGuaranteeRule::UPDATES_YEARS, 'label' => $this->module->t('For a number of years', [], 'Modules.Bkguarantee.Admin')],
                        ],
                    ],
                    [
                        'type' => 'date',
                        'label' => $this->module->t('Updates at least until', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'updates_until',
                        'form_group_class' => 'bkguar-when--updates_mode--date',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->t('Updates for at least, in years', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'updates_years',
                        'class' => 'fixed-width-xs',
                        'desc' => sprintf($this->module->t('Between 1 and %d.', [], 'Modules.Bkguarantee.Admin'), BkGuaranteeRule::UPDATES_MAX_YEARS),
                        'form_group_class' => 'bkguar-when--updates_mode--years',
                    ],
                ],
            ]],
            ['form' => [
                'legend' => [
                    'title' => $this->module->t('Repair', [], 'Modules.Bkguarantee.Admin'),
                    'icon' => 'icon-wrench',
                ],
                'description' => Tools::safeOutput($this->module->t('The EU repairability score where one applies; otherwise, when the producer makes it available, spare parts and repair information. Art. 246a § 1 Abs. 1 Nr. 20 and 21 EGBGB (DE), § 4 Abs. 1 Z 20 and 21 FAGG (AT), art. 49 c.1 lett. v-bis and v-ter Codice del consumo (IT).', [], 'Modules.Bkguarantee.Admin')),
                'warning' => $repairWarning,
                'input' => array_merge([
                    [
                        'type' => 'radio',
                        'label' => $this->module->t('Repair', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'repair_mode',
                        'values' => [
                            ['id' => 'repair_mode_inherit', 'value' => '', 'label' => $inherit],
                            ['id' => 'repair_mode_none', 'value' => BkGuaranteeRule::REPAIR_NONE, 'label' => $none],
                            ['id' => 'repair_mode_score', 'value' => BkGuaranteeRule::REPAIR_SCORE, 'label' => $this->module->t('EU repairability score', [], 'Modules.Bkguarantee.Admin')],
                            ['id' => 'repair_mode_parts', 'value' => BkGuaranteeRule::REPAIR_PARTS, 'label' => $this->module->t('Spare parts and repair information, when there is no EU score', [], 'Modules.Bkguarantee.Admin')],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->t('EU repairability score', [], 'Modules.Bkguarantee.Admin'),
                        'name' => 'repair_score',
                        'class' => 'fixed-width-sm',
                        'options' => ['query' => $scores, 'id' => 'id', 'name' => 'name'],
                        'desc' => $this->module->t('The A to E class of the EU energy label of smartphones and tablets (Regulation (EU) 2023/1669). National scores, such as the French index, do not count.', [], 'Modules.Bkguarantee.Admin'),
                        'form_group_class' => 'bkguar-when--repair_mode--score',
                    ],
                ], $texts),
                'submit' => ['title' => $this->module->t('Save', [], 'Modules.Bkguarantee.Admin')],
            ]],
        ];

        $values = $isNew ? [] : $rule->values();
        $type = $isNew ? BkGuaranteeRule::FILTER_CATEGORY : $rule->filter_type;

        $this->fields_value['bkguar_categories[]'] = $type === BkGuaranteeRule::FILTER_CATEGORY ? $values : [];
        $this->fields_value['bkguar_manufacturers[]'] = $type === BkGuaranteeRule::FILTER_MANUFACTURER ? $values : [];
        $this->fields_value['bkguar_products'] = $type === BkGuaranteeRule::FILTER_PRODUCTS ? implode(', ', $values) : '';

        // Una regla recién creada nace activa: nadie da de alta una regla para dejarla apagada, y
        // el interruptor por defecto en 'No' hacía que la primera se guardara sin efecto.
        if ($isNew) {
            $this->fields_value['active'] = 1;
            $this->fields_value['priority'] = 0;
            $this->fields_value['id_shop'] = Shop::isFeatureActive() ? (int) $this->context->shop->id : 0;
        }

        return parent::renderForm();
    }

    /**
     * Buscador de productos del formulario, el mismo que usa la pantalla de configuración: se
     * escribe el nombre o la referencia y se van añadiendo fichas, y lo que viaja al servidor sigue
     * siendo la lista de identificadores.
     *
     * @return string
     */
    private function renderFinder()
    {
        $rule = $this->loadObject(true);
        $ids = ($rule instanceof BkGuaranteeRule && $rule->id && $rule->filter_type === BkGuaranteeRule::FILTER_PRODUCTS)
            ? $rule->values()
            : [];

        $items = [];
        if (!empty($ids)) {
            $rows = Db::getInstance()->executeS(
                'SELECT p.`id_product`, pl.`name` FROM `' . _DB_PREFIX_ . 'product` p
                 INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . (int) $this->context->language->id . '
                 WHERE p.`id_product` IN (' . implode(',', array_map('intval', $ids)) . ')
                 GROUP BY p.`id_product`'
            );
            foreach ((array) $rows as $row) {
                $items[] = ['id' => (int) $row['id_product'], 'name' => $row['name']];
            }
        }

        $this->context->smarty->assign([
            'name' => 'bkguar_products',
            'value' => implode(',', $ids),
            'items' => $items,
        ]);

        return '<script>var bkguarSearchUrl = ' . json_encode(
            $this->context->link->getAdminLink('AdminBkGuaranteeConfig') . '&ajax=1&action=BkSearchProduct'
        ) . ';</script>' . $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/_finder.tpl'
        );
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
            $this->errors[] = $this->module->t('Choose at least one target for the rule.', [], 'Modules.Bkguarantee.Admin');
        }

        $updatesMode = (string) Tools::getValue('updates_mode');
        $repairMode = (string) Tools::getValue('repair_mode');
        $date = BkGuaranteeRule::normaliseDate(Tools::getValue('updates_until'));

        $hasTexts = false;
        foreach (BkGuaranteeRule::PARTS_FIELDS as $field) {
            foreach (Language::getIDs(false) as $idLang) {
                $hasTexts = $hasTexts || trim((string) Tools::getValue($field . '_' . (int) $idLang)) !== '';
            }
        }

        $errors = BkGuaranteeRule::blockErrors([
            'years' => Tools::getValue('years'),
            'updates_mode' => $updatesMode,
            'updates_until' => (string) $date,
            'updates_years' => Tools::getValue('updates_years'),
            'repair_mode' => $repairMode,
            'repair_score' => (string) Tools::getValue('repair_score'),
            'has_texts' => $hasTexts,
        ]);
        foreach ($errors as $error) {
            $this->errors[] = $this->module->t($error, [], 'Modules.Bkguarantee.Admin');
        }

        // Lo que el modo elegido no usa se vacía: una fecha olvidada no reaparece al cambiar de
        // modo. Los textos de reparación se guardan siempre, para no perderlos si se vuelve a ellos.
        $_POST['years'] = (int) Tools::getValue('years');
        $_POST['updates_until'] = $updatesMode === BkGuaranteeRule::UPDATES_DATE ? (string) $date : '';
        $_POST['updates_years'] = $updatesMode === BkGuaranteeRule::UPDATES_YEARS ? (int) Tools::getValue('updates_years') : 0;
        $_POST['repair_score'] = $repairMode === BkGuaranteeRule::REPAIR_SCORE ? (string) Tools::getValue('repair_score') : '';

        if (!empty($this->errors)) {
            $this->display = Tools::getValue('id_guarantee_rule') ? 'edit' : 'add';

            return false;
        }

        return parent::processSave();
    }
}
