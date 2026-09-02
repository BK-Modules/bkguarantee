<?php
/**
 * Pantalla de configuración del módulo.
 *
 * Tres bloques: cobertura del arte oficial por idioma, comportamiento, y el panel
 * "Más de BK Modules". La cobertura va primero a propósito — un idioma sin arte es una tienda
 * que no cumple, y eso tiene que verse antes que ningún ajuste.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBkGuaranteeConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->assetUrl('views/css/admin.css'), 'all', null, false);
        // El CSS del front se carga también aquí para que la comparativa de presentaciones se vea
        // exactamente como se verá en la tienda. Todo cuelga de .bkguar, así que no toca el BO.
        $this->addCSS($this->module->assetUrl('views/css/front.css'), 'all', null, false);
    }

    public function initContent()
    {
        if (Tools::isSubmit('submitBkGuaranteeConfig')) {
            $this->processForm();
        }

        $this->content .= $this->renderCoverage();
        $this->content .= $this->renderConfigForm();
        $this->content .= $this->renderInfoPanel();

        parent::initContent();
    }

    private function processForm()
    {
        $width = (int) Tools::getValue(BkGuaranteeConfig::WIDTH);
        $width = max(BkGuaranteeConfig::WIDTH_MIN, min(BkGuaranteeConfig::WIDTH_MAX, $width ?: 380));

        $groups = Tools::getValue(BkGuaranteeConfig::B2B_GROUPS);
        $groups = is_array($groups) ? implode(',', array_map('intval', $groups)) : '';

        Configuration::updateValue(BkGuaranteeConfig::ENABLED, (int) Tools::getValue(BkGuaranteeConfig::ENABLED));
        Configuration::updateValue(BkGuaranteeConfig::ON_PRODUCT, (int) Tools::getValue(BkGuaranteeConfig::ON_PRODUCT));
        Configuration::updateValue(BkGuaranteeConfig::ON_CHECKOUT, (int) Tools::getValue(BkGuaranteeConfig::ON_CHECKOUT));
        Configuration::updateValue(BkGuaranteeConfig::ON_EMAIL, (int) Tools::getValue(BkGuaranteeConfig::ON_EMAIL));
        Configuration::updateValue(BkGuaranteeConfig::GARAN_ON, (int) Tools::getValue(BkGuaranteeConfig::GARAN_ON));
        Configuration::updateValue(BkGuaranteeConfig::GARAN_NESTED, (int) Tools::getValue(BkGuaranteeConfig::GARAN_NESTED));
        $garanPlace = Tools::getValue(BkGuaranteeConfig::GARAN_PLACEMENT);
        Configuration::updateValue(
            BkGuaranteeConfig::GARAN_PLACEMENT,
            isset(BkGuaranteeConfig::PLACEMENTS[$garanPlace]) ? $garanPlace : 'thumbs'
        );
        $garanWidth = (int) Tools::getValue(BkGuaranteeConfig::GARAN_WIDTH);
        Configuration::updateValue(BkGuaranteeConfig::GARAN_WIDTH, max(180, min(520, $garanWidth ?: 300)));
        Configuration::updateValue(BkGuaranteeConfig::EMAIL_ATTACH, (int) Tools::getValue(BkGuaranteeConfig::EMAIL_ATTACH));
        Configuration::updateValue(BkGuaranteeConfig::WIDTH, $width);
        Configuration::updateValue(BkGuaranteeConfig::HIDE_FOR_B2B, (int) Tools::getValue(BkGuaranteeConfig::HIDE_FOR_B2B));
        Configuration::updateValue(BkGuaranteeConfig::B2B_GROUPS, $groups);

        $style = Tools::getValue(BkGuaranteeConfig::STYLE);
        $align = Tools::getValue(BkGuaranteeConfig::ALIGN);
        $placement = Tools::getValue(BkGuaranteeConfig::PLACEMENT);
        Configuration::updateValue(
            BkGuaranteeConfig::STYLE,
            in_array($style, BkGuaranteeConfig::STYLES, true) ? $style : 'card'
        );
        Configuration::updateValue(
            BkGuaranteeConfig::ALIGN,
            in_array($align, BkGuaranteeConfig::ALIGNS, true) ? $align : 'left'
        );
        Configuration::updateValue(
            BkGuaranteeConfig::PLACEMENT,
            isset(BkGuaranteeConfig::PLACEMENTS[$placement]) ? $placement : 'info'
        );

        $coPlacement = Tools::getValue(BkGuaranteeConfig::CHECKOUT_PLACEMENT);
        Configuration::updateValue(
            BkGuaranteeConfig::CHECKOUT_PLACEMENT,
            isset(BkGuaranteeConfig::CHECKOUT_PLACEMENTS[$coPlacement]) ? $coPlacement : 'payment'
        );

        $mode = Tools::getValue(BkGuaranteeConfig::SCOPE_MODE);
        Configuration::updateValue(
            BkGuaranteeConfig::SCOPE_MODE,
            in_array($mode, BkGuaranteeConfig::SCOPE_MODES, true) ? $mode : 'all'
        );
        Configuration::updateValue(BkGuaranteeConfig::INCLUDED_CATEGORIES, $this->idListFrom(BkGuaranteeConfig::INCLUDED_CATEGORIES));
        Configuration::updateValue(BkGuaranteeConfig::EXCLUDED_CATEGORIES, $this->idListFrom(BkGuaranteeConfig::EXCLUDED_CATEGORIES));
        Configuration::updateValue(BkGuaranteeConfig::EXCLUDED_PRODUCTS, $this->idListFrom(BkGuaranteeConfig::EXCLUDED_PRODUCTS));
        Configuration::updateValue(BkGuaranteeConfig::SKIP_VIRTUAL, (int) Tools::getValue(BkGuaranteeConfig::SKIP_VIRTUAL));
        Configuration::updateValue(BkGuaranteeConfig::DEBUG, (int) Tools::getValue(BkGuaranteeConfig::DEBUG));

        BkGuaranteeLogger::confirmation('Configuración guardada');
        $this->confirmations[] = $this->trans('Settings updated.', [], 'Modules.Bkguarantee.Admin');
    }

    /**
     * Normaliza a lista de enteros separados por comas, venga de un multiselect o de un campo de
     * texto donde el comerciante haya pegado los identificadores como le haya parecido.
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

    /**
     * Cobertura del arte oficial y vista previa. La vista previa se pinta al ancho configurado,
     * que es como lo verá el cliente: comprobar el aviso a tamaño completo no dice nada sobre si
     * se lee en la ficha.
     *
     * @return string
     */
    private function renderCoverage()
    {
        $rows = BkGuaranteeNotice::coverage();
        $missing = BkGuaranteeNotice::missingLanguages();

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

        $this->context->smarty->assign([
            'bkguar_rows' => $rows,
            'bkguar_missing' => $missing,
            'bkguar_preview' => $preview,
            'bkguar_width' => BkGuaranteeConfig::getWidth(),
            'bkguar_dir' => 'modules/bkguarantee/' . BkGuaranteeNotice::DIR,
            'bkguar_qr' => BkGuaranteeNotice::qrCheck(BkGuaranteeConfig::getWidth()),
            'bkguar_qr_garan' => BkGuaranteeNotice::qrCheck(
                BkGuaranteeConfig::getGaranWidth(),
                BkGuaranteeNotice::QR_RATIO_LABEL
            ),
            'bkguar_qr_ideal' => BkGuaranteeNotice::widthForComfortableQr(),
            'bkguar_qr_ideal_garan' => BkGuaranteeNotice::widthForComfortableQr(BkGuaranteeNotice::QR_RATIO_LABEL),
            'bkguar_garan_width' => BkGuaranteeConfig::getGaranWidth(),
            'bkguar_styles' => BkGuaranteeConfig::STYLES,
            'bkguar_style' => BkGuaranteeConfig::getStyle(),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/coverage.tpl'
        );
    }

    private function renderConfigForm()
    {
        $categories = [];
        foreach (Category::getSimpleCategories((int) $this->context->language->id) as $category) {
            $categories[] = ['id' => (int) $category['id_category'], 'name' => $category['name']];
        }

        $groups = [];
        foreach (Group::getGroups($this->context->language->id) as $group) {
            $groups[] = ['id' => (int) $group['id_group'], 'name' => $group['name']];
        }

        $fields = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Behaviour', [], 'Modules.Bkguarantee.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    $this->buildSwitch(
                        BkGuaranteeConfig::ENABLED,
                        $this->trans('Show the notice', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Master switch. Turn it off only if this shop sells exclusively to businesses.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    $this->buildSwitch(
                        BkGuaranteeConfig::ON_PRODUCT,
                        $this->trans('On the product page', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Below the add-to-cart block, where the offer is presented.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    $this->buildSwitch(
                        BkGuaranteeConfig::ON_CHECKOUT,
                        $this->trans('On the order summary', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('The last screen before the contract is concluded.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    [
                        'type' => 'select',
                        'label' => $this->trans('Presentation', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::STYLE,
                        'desc' => $this->trans('The frame around the notice. The notice itself never changes. The wide band is meant for the bottom of the product page, where it has room.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => [
                            'query' => [
                                ['id' => 'card', 'name' => $this->trans('Card with blue header', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'framed', 'name' => $this->trans('Thin frame only', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'plain', 'name' => $this->trans('No frame', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'band', 'name' => $this->trans('Wide band with a heading beside it', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Position on the product page', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::PLACEMENT,
                        'desc' => $this->trans('Not every theme renders every position. If one of them shows nothing, try another or move the module from Design > Positions.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => [
                            'query' => [
                                ['id' => 'info', 'name' => $this->trans('Below the add-to-cart block', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'thumbs', 'name' => $this->trans('Under the product gallery', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'footer', 'name' => $this->trans('At the bottom of the product page', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Alignment', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::ALIGN,
                        'options' => [
                            'query' => [
                                ['id' => 'left', 'name' => $this->trans('Left', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'center', 'name' => $this->trans('Centred', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Width in pixels', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::WIDTH,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->trans('Between 240 and 720. On phones the notice always uses the full width available.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    $this->buildSwitch(
                        BkGuaranteeConfig::GARAN_ON,
                        $this->trans('Show the GARAN label', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Only appears on products covered by a durability guarantee rule with all three fields resolved.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    [
                        'type' => 'select',
                        'label' => $this->trans('Position of the GARAN label', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::GARAN_PLACEMENT,
                        'desc' => $this->trans('The regulation places it next to the image of the goods.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => [
                            'query' => [
                                ['id' => 'thumbs', 'name' => $this->trans('Under the product gallery', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'info', 'name' => $this->trans('Below the add-to-cart block', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'footer', 'name' => $this->trans('At the bottom of the product page', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('GARAN width in pixels', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::GARAN_WIDTH,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->trans('Between 180 and 520.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    $this->buildSwitch(
                        BkGuaranteeConfig::GARAN_NESTED,
                        $this->trans('Nested display for GARAN', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('A compact badge that opens the full label on the first click, hover or touch. The regulation allows this for the label only, never for the notice.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    $this->buildSwitch(
                        BkGuaranteeConfig::ON_EMAIL,
                        $this->trans('In the order confirmation email', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('The notice has to stay available to the customer after the purchase, and the confirmation email is the durable medium that already reaches everyone.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    $this->buildSwitch(
                        BkGuaranteeConfig::EMAIL_ATTACH,
                        $this->trans('Attach it to the email as well', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Half the inboxes block remote images. The attachment is what guarantees the notice actually arrives.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    [
                        'type' => 'select',
                        'label' => $this->trans('Position in the checkout', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::CHECKOUT_PLACEMENT,
                        'desc' => $this->trans('The order summary is visible from the first step. Above the payment methods the notice only appears once the customer has finished the address and shipping steps.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => [
                            'query' => [
                                ['id' => 'summary', 'name' => $this->trans('Top of the order summary', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'payment', 'name' => $this->trans('Above the payment methods', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Catalogue covered', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::SCOPE_MODE,
                        'desc' => $this->trans('The notice is mandatory on the sale of goods. Services and pure digital content are not goods, and that is the reason to leave part of the catalogue out.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => [
                            'query' => [
                                ['id' => 'all', 'name' => $this->trans('Every product', [], 'Modules.Bkguarantee.Admin')],
                                ['id' => 'categories', 'name' => $this->trans('Only the categories I choose', [], 'Modules.Bkguarantee.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'class' => 'chosen',
                        'label' => $this->trans('Categories covered', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::INCLUDED_CATEGORIES . '[]',
                        'desc' => $this->trans('Only used when the catalogue is limited to chosen categories.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => ['query' => $categories, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'class' => 'chosen',
                        'label' => $this->trans('Categories left out', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::EXCLUDED_CATEGORIES . '[]',
                        'desc' => $this->trans('Wins over anything else: a product in one of these never shows the notice.', [], 'Modules.Bkguarantee.Admin'),
                        'options' => ['query' => $categories, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Products left out', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::EXCLUDED_PRODUCTS,
                        'desc' => $this->trans('Product IDs separated by commas.', [], 'Modules.Bkguarantee.Admin'),
                    ],
                    $this->buildSwitch(
                        BkGuaranteeConfig::SKIP_VIRTUAL,
                        $this->trans('Leave virtual products out', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Downloads and services are not goods under the sale of goods directive. Check your own catalogue before turning this on: a physical product flagged as virtual would lose the notice too.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    $this->buildSwitch(
                        BkGuaranteeConfig::HIDE_FOR_B2B,
                        $this->trans('Hide it from business customers', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Only a shop selling exclusively to businesses falls outside the obligation. In a mixed shop this is your call, not a recommendation.', [], 'Modules.Bkguarantee.Admin')
                    ),
                    [
                        'type' => 'checkbox',
                        'label' => $this->trans('Business customer groups', [], 'Modules.Bkguarantee.Admin'),
                        'name' => BkGuaranteeConfig::B2B_GROUPS,
                        'values' => ['query' => $groups, 'id' => 'id', 'name' => 'name'],
                    ],
                    $this->buildSwitch(
                        BkGuaranteeConfig::DEBUG,
                        $this->trans('Debug log', [], 'Modules.Bkguarantee.Admin'),
                        $this->trans('Writes to log/bkguarantee.log inside the module.', [], 'Modules.Bkguarantee.Admin')
                    ),
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Modules.Bkguarantee.Admin')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->identifier = $this->module->name;
        $helper->submit_action = 'submitBkGuaranteeConfig';
        $helper->currentIndex = self::$currentIndex;
        $helper->token = Tools::getAdminTokenLite('AdminBkGuaranteeConfig');
        $helper->tpl_vars = ['fields_value' => $this->buildFieldsValue($groups)];

        return $helper->generateForm([$fields]);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $desc
     *
     * @return array
     */
    private function buildSwitch($name, $label, $desc)
    {
        return [
            'type' => 'switch',
            'label' => $label,
            'name' => $name,
            'desc' => $desc,
            'is_bool' => true,
            'values' => [
                ['id' => $name . '_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.Bkguarantee.Admin')],
                ['id' => $name . '_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Bkguarantee.Admin')],
            ],
        ];
    }

    /**
     * @param array $groups
     *
     * @return array
     */
    private function buildFieldsValue(array $groups)
    {
        $values = [
            BkGuaranteeConfig::ENABLED => (int) Configuration::get(BkGuaranteeConfig::ENABLED),
            BkGuaranteeConfig::ON_PRODUCT => (int) Configuration::get(BkGuaranteeConfig::ON_PRODUCT),
            BkGuaranteeConfig::ON_CHECKOUT => (int) Configuration::get(BkGuaranteeConfig::ON_CHECKOUT),
            BkGuaranteeConfig::ON_EMAIL => (int) Configuration::get(BkGuaranteeConfig::ON_EMAIL),
            BkGuaranteeConfig::GARAN_ON => (int) Configuration::get(BkGuaranteeConfig::GARAN_ON),
            BkGuaranteeConfig::GARAN_PLACEMENT => BkGuaranteeConfig::getGaranPlacement(),
            BkGuaranteeConfig::GARAN_WIDTH => BkGuaranteeConfig::getGaranWidth(),
            BkGuaranteeConfig::GARAN_NESTED => (int) Configuration::get(BkGuaranteeConfig::GARAN_NESTED),
            BkGuaranteeConfig::EMAIL_ATTACH => (int) Configuration::get(BkGuaranteeConfig::EMAIL_ATTACH),
            BkGuaranteeConfig::WIDTH => BkGuaranteeConfig::getWidth(),
            BkGuaranteeConfig::STYLE => BkGuaranteeConfig::getStyle(),
            BkGuaranteeConfig::ALIGN => BkGuaranteeConfig::getAlign(),
            BkGuaranteeConfig::PLACEMENT => BkGuaranteeConfig::getPlacement(),
            BkGuaranteeConfig::CHECKOUT_PLACEMENT => BkGuaranteeConfig::getCheckoutPlacement(),
            BkGuaranteeConfig::SCOPE_MODE => BkGuaranteeConfig::getScopeMode(),
            BkGuaranteeConfig::INCLUDED_CATEGORIES . '[]' => BkGuaranteeConfig::getIncludedCategories(),
            BkGuaranteeConfig::EXCLUDED_CATEGORIES . '[]' => BkGuaranteeConfig::getExcludedCategories(),
            BkGuaranteeConfig::EXCLUDED_PRODUCTS => implode(', ', BkGuaranteeConfig::getExcludedProducts()),
            BkGuaranteeConfig::SKIP_VIRTUAL => (int) Configuration::get(BkGuaranteeConfig::SKIP_VIRTUAL),
            BkGuaranteeConfig::HIDE_FOR_B2B => (int) Configuration::get(BkGuaranteeConfig::HIDE_FOR_B2B),
            BkGuaranteeConfig::DEBUG => (int) Configuration::get(BkGuaranteeConfig::DEBUG),
        ];

        $selected = BkGuaranteeConfig::getB2bGroups();
        foreach ($groups as $group) {
            $values[BkGuaranteeConfig::B2B_GROUPS . '_' . $group['id']] = in_array($group['id'], $selected, true);
        }

        return $values;
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
