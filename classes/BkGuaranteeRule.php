<?php
/**
 * Regla de datos del fabricante: lo que el productor dice de un conjunto de productos.
 *
 * Una regla dice a qué productos se les aplica un mismo dato del fabricante —por categoría, por
 * marca o producto a producto—, que es el trabajo real en un catálogo grande. Puede decir tres
 * cosas, y cada una se resuelve por separado (resolve()):
 *
 *   - garan:   la garantía comercial de durabilidad del productor, con la que se compone la
 *              etiqueta GARAN. Solo la que ofrece el **productor**, sin coste adicional, sobre la
 *              totalidad del bien y por más de dos años (considerando 8 del Reglamento (UE)
 *              2025/1960); una garantía de la propia tienda no es GARAN. `years` 0 = la regla no
 *              dice nada de esto.
 *   - updates: el periodo mínimo de actualizaciones de software, como fecha o como años.
 *   - repair:  el índice de reparabilidad UE o, si no lo hay, repuestos y reparación (los cinco
 *              textos por idioma de la tabla `_lang`).
 *
 * En actualizaciones y reparación, `none` («no aplica o el fabricante no lo facilita») es un dato:
 * corta la herencia y no pinta nada. Vacío es heredar de la siguiente regla que case.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeRule extends ObjectModel
{
    /** Filtros admitidos */
    const FILTER_CATEGORY = 'category';
    const FILTER_MANUFACTURER = 'manufacturer';
    const FILTER_PRODUCTS = 'products';
    /**
     * Alcances de lo más concreto a lo más general: la regla de un modelo manda sobre la de su marca
     * y la de la marca sobre la de su categoría, sin tocar prioridades.
     */
    const SCOPES = [self::FILTER_PRODUCTS, self::FILTER_MANUFACTURER, self::FILTER_CATEGORY];

    /** Duración mínima que la norma exige para que exista etiqueta */
    const MIN_YEARS = 3;

    /** Bloques que una regla puede definir, en el orden en que se resuelven */
    const BLOCKS = ['garan', 'updates', 'repair'];

    /** Actualizaciones de software: '' hereda */
    const UPDATES_NONE = 'none';
    const UPDATES_DATE = 'date';
    const UPDATES_YEARS = 'years';
    const UPDATES_MODES = ['', self::UPDATES_NONE, self::UPDATES_DATE, self::UPDATES_YEARS];
    /** Años máximos de un periodo de actualizaciones */
    const UPDATES_MAX_YEARS = 30;

    /** Reparación: '' hereda */
    const REPAIR_NONE = 'none';
    const REPAIR_SCORE = 'score';
    const REPAIR_PARTS = 'parts';
    const REPAIR_MODES = ['', self::REPAIR_NONE, self::REPAIR_SCORE, self::REPAIR_PARTS];
    /** Clases del índice de reparabilidad de la etiqueta energética UE (Reglamento Delegado (UE) 2023/1669) */
    const REPAIR_SCORES = ['A', 'B', 'C', 'D', 'E'];

    /** Textos de repuestos y reparación, por idioma, en el orden en que se muestran */
    const PARTS_FIELDS = ['parts_availability', 'parts_cost', 'parts_order', 'repair_manuals', 'repair_restrictions'];
    /** Longitud máxima de cada texto */
    const TEXT_MAX = 512;

    /**
     * Columnas de actualizaciones y reparación. La tabla nace con las de la etiqueta GARAN y estas se
     * añaden con la misma lista en una instalación nueva y en una actualización: una sola
     * definición, y las dos tablas quedan idénticas.
     */
    const DURABILITY_COLUMNS = [
        'updates_mode' => "varchar(8) NOT NULL DEFAULT '' AFTER `model`",
        'updates_until' => "varchar(10) NOT NULL DEFAULT '' AFTER `updates_mode`",
        'updates_years' => 'tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER `updates_until`',
        'repair_mode' => "varchar(8) NOT NULL DEFAULT '' AFTER `updates_years`",
        'repair_score' => "varchar(8) NOT NULL DEFAULT '' AFTER `repair_mode`",
    ];

    /** @var string */
    public $name;
    /** @var string category|manufacturer|products */
    public $filter_type;
    /** @var string Identificadores separados por comas */
    public $filter_values;
    /** @var int Años de la garantía del productor; 0 = la regla no dice nada de GARAN */
    public $years;
    /** @var string Marca del productor; vacío = la marca del producto */
    public $brand;
    /** @var string Identificador del modelo; vacío = el MPN del producto */
    public $model;
    /** @var string '' | none | date | years */
    public $updates_mode;
    /** @var string Fecha YYYY-MM-DD hasta la que hay actualizaciones */
    public $updates_until;
    /** @var int Años de actualizaciones */
    public $updates_years;
    /** @var string '' | none | score | parts */
    public $repair_mode;
    /** @var string Clase A–E del índice de reparabilidad UE */
    public $repair_score;
    /** @var string|array Disponibilidad de las piezas de recambio, por idioma */
    public $parts_availability;
    /** @var string|array Coste estimado de las piezas */
    public $parts_cost;
    /** @var string|array Cómo se piden las piezas */
    public $parts_order;
    /** @var string|array Instrucciones de reparación y mantenimiento */
    public $repair_manuals;
    /** @var string|array Restricciones a la reparación */
    public $repair_restrictions;
    /** @var int A mayor prioridad, antes se evalúa */
    public $priority;
    /** @var int Tienda a la que se aplica; 0 = todas */
    public $id_shop;
    /** @var bool */
    public $active;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    public static $definition = [
        'table' => 'bk_guarantee_rule',
        'primary' => 'id_guarantee_rule',
        'multilang' => true,
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'filter_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'filter_values' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 2048],
            'years' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'brand' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'model' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'updates_mode' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8],
            'updates_until' => ['type' => self::TYPE_STRING, 'validate' => 'isDateFormat', 'size' => 10],
            'updates_years' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'repair_mode' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8],
            'repair_score' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            // Las URL llevan `=` y `&`, que isGenericName rechaza: se validan como texto y se
            // escapan al pintarlas.
            'parts_availability' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => self::TEXT_MAX],
            'parts_cost' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => self::TEXT_MAX],
            'parts_order' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => self::TEXT_MAX],
            'repair_manuals' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => self::TEXT_MAX],
            'repair_restrictions' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => self::TEXT_MAX],
        ],
    ];

    /** @var array Reglas activas por tienda, leídas una vez por petición */
    private static $rules = [];

    /** @var array Resolución por tienda y producto: la ficha la pide desde varios hooks */
    private static $resolved = [];

    /**
     * @return array Filtros con su etiqueta sin traducir; el controlador las traduce
     */
    public static function filterTypes()
    {
        return [
            self::FILTER_CATEGORY => 'Category',
            self::FILTER_MANUFACTURER => 'Brand',
            self::FILTER_PRODUCTS => 'Specific products',
        ];
    }

    /**
     * @return array Identificadores del filtro, limpios
     */
    public function values()
    {
        $ids = array_filter(array_map('intval', preg_split('/[^0-9]+/', (string) $this->filter_values)));

        return array_values(array_unique($ids));
    }

    /**
     * Lo que dice el fabricante de un producto, bloque a bloque: para cada uno, la primera regla
     * activa que case **y defina ese bloque**. Manda la más concreta —productos, luego marca, luego
     * categoría—; dentro del mismo alcance, la de la tienda concreta antes que la de todas, y después
     * la prioridad y la antigüedad, que solo desempatan. Así una regla de marca pone la reparación
     * de toda la gama y una regla de producto añade solo la fecha de actualizaciones de su modelo.
     *
     * @param int $idProduct
     *
     * @return array garan, updates, repair => BkGuaranteeRule|null
     */
    public static function resolve($idProduct)
    {
        $out = array_fill_keys(self::BLOCKS, null);
        $idProduct = (int) $idProduct;
        if ($idProduct <= 0) {
            return $out;
        }

        // Una regla con id_shop 0 vale para todas las tiendas; con una tienda concreta, solo para
        // esa. En multitienda dos marcas distintas necesitan reglas distintas y no compartirlas.
        $idShop = (int) Context::getContext()->shop->id;
        $key = $idShop . ':' . $idProduct;
        if (array_key_exists($key, self::$resolved)) {
            return self::$resolved[$key];
        }

        if (!isset(self::$rules[$idShop])) {
            self::$rules[$idShop] = (array) Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule`
                 WHERE `active` = 1 AND `id_shop` IN (0, ' . $idShop . ')
                 ORDER BY FIELD(`filter_type`, \'' . implode('\', \'', self::SCOPES) . '\'),
                          `id_shop` DESC, `priority` DESC, `id_guarantee_rule` ASC'
            );
        }

        if (!empty(self::$rules[$idShop])) {
            $idManufacturer = (int) Db::getInstance()->getValue(
                'SELECT `id_manufacturer` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . $idProduct
            );
            $categories = array_map('intval', (array) Product::getProductCategories($idProduct));

            foreach (self::$rules[$idShop] as $row) {
                $rule = new self();
                $rule->hydrate($row);
                if (!$rule->matches($idProduct, $idManufacturer, $categories)) {
                    continue;
                }

                foreach (self::BLOCKS as $block) {
                    if ($out[$block] === null && $rule->defines($block)) {
                        $out[$block] = $rule;
                    }
                }
                if (!in_array(null, $out, true)) {
                    break;
                }
            }
        }

        self::$resolved[$key] = $out;

        return $out;
    }

    /**
     * Si la regla dice algo de un bloque. `none` también es decir algo: corta la herencia.
     *
     * @param string $block garan|updates|repair
     *
     * @return bool
     */
    public function defines($block)
    {
        switch ($block) {
            case 'garan':
                return (int) $this->years > 0;
            case 'updates':
                return (string) $this->updates_mode !== '';
            case 'repair':
                return (string) $this->repair_mode !== '';
        }

        return false;
    }

    /**
     * @param int   $idProduct
     * @param int   $idManufacturer
     * @param array $categories
     *
     * @return bool
     */
    public function matches($idProduct, $idManufacturer, array $categories)
    {
        $values = $this->values();
        if (empty($values)) {
            return false;
        }

        switch ($this->filter_type) {
            case self::FILTER_PRODUCTS:
                return in_array((int) $idProduct, $values, true);
            case self::FILTER_MANUFACTURER:
                return $idManufacturer > 0 && in_array((int) $idManufacturer, $values, true);
            case self::FILTER_CATEGORY:
                return (bool) array_intersect($values, $categories);
        }

        return false;
    }

    /**
     * Datos completos de la etiqueta para un producto, o null si no hay etiqueta que pintar.
     *
     * Render defensivo: si falta cualquiera de los tres campos editables, o los años no superan
     * los dos que ya cubre la garantía legal, no se devuelve una etiqueta a medias. Un GARAN sin
     * identificador de modelo es una etiqueta inválida, y es peor que no ponerla.
     *
     * @param int $idProduct
     *
     * @return array|null years, brand, model
     */
    public static function labelFor($idProduct)
    {
        $resolved = self::resolve($idProduct);
        $rule = $resolved['garan'];
        if ($rule === null) {
            return null;
        }

        $years = (int) $rule->years;
        $brand = trim((string) $rule->brand);
        $model = trim((string) $rule->model);

        if ($brand === '') {
            $brand = trim((string) self::manufacturerName($idProduct));
        }
        // El MPN existe desde PrestaShop 1.7.7; antes no hay de dónde heredar el modelo.
        if ($model === '' && property_exists('Product', 'mpn')) {
            $model = trim((string) Db::getInstance()->getValue(
                'SELECT `mpn` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . (int) $idProduct
            ));
        }

        if ($years < self::MIN_YEARS || $brand === '' || $model === '') {
            BkGuaranteeLogger::warning(sprintf(
                'Etiqueta incompleta para el producto %d con la regla %d (años=%d, marca="%s", modelo="%s")',
                (int) $idProduct,
                (int) $rule->id,
                $years,
                $brand,
                $model
            ));

            return null;
        }

        return ['years' => $years, 'brand' => $brand, 'model' => $model];
    }

    /**
     * Los cinco textos de repuestos y reparación de la regla en un idioma, recortados.
     *
     * @param int $idLang
     *
     * @return array campo => texto
     */
    public function texts($idLang)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule_lang`
             WHERE `id_guarantee_rule` = ' . (int) $this->id . ' AND `id_lang` = ' . (int) $idLang
        );

        $out = [];
        foreach (self::PARTS_FIELDS as $field) {
            $out[$field] = $row ? trim((string) $row[$field]) : '';
        }

        return $out;
    }

    /**
     * Idiomas activos a los que les falta alguno de los textos que la regla sí tiene en otro: en
     * ellos esa línea no se pinta, porque servir el texto de otra lengua no informa a nadie.
     *
     * @param int $idRule
     *
     * @return array Códigos ISO en mayúsculas
     */
    public static function missingTextLanguages($idRule)
    {
        $byLang = [];
        foreach ((array) Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule_lang` WHERE `id_guarantee_rule` = ' . (int) $idRule
        ) as $row) {
            $byLang[(int) $row['id_lang']] = $row;
        }

        $filled = [];
        foreach (self::PARTS_FIELDS as $field) {
            foreach ($byLang as $row) {
                if (trim((string) $row[$field]) !== '') {
                    $filled[] = $field;
                    break;
                }
            }
        }

        $missing = [];
        foreach (Language::getLanguages(true) as $language) {
            $row = isset($byLang[(int) $language['id_lang']]) ? $byLang[(int) $language['id_lang']] : null;
            foreach ($filled as $field) {
                if ($row === null || trim((string) $row[$field]) === '') {
                    $missing[] = Tools::strtoupper($language['iso_code']);
                    break;
                }
            }
        }

        return $missing;
    }

    /**
     * Lo que impide guardar los bloques de una regla. Es la misma comprobación en el formulario y
     * en la importación CSV.
     *
     * @param array $data years, updates_mode, updates_until, updates_years, repair_mode,
     *                    repair_score y has_texts (si la regla trae algún texto de reparación)
     *
     * @return array Mensajes sin traducir; los traduce el controlador
     */
    public static function blockErrors(array $data)
    {
        $errors = [];

        $years = (int) $data['years'];
        if ($years > 0 && $years < self::MIN_YEARS) {
            $errors[] = 'A durability guarantee earns a label only above two years.';
        }

        $updates = (string) $data['updates_mode'];
        if (!in_array($updates, self::UPDATES_MODES, true)) {
            $errors[] = 'Unknown software updates option.';
        } elseif ($updates === self::UPDATES_DATE && self::normaliseDate($data['updates_until']) === null) {
            $errors[] = 'Enter a valid date for the software updates.';
        } elseif ($updates === self::UPDATES_YEARS
            && ((int) $data['updates_years'] < 1 || (int) $data['updates_years'] > self::UPDATES_MAX_YEARS)
        ) {
            $errors[] = 'Software updates take between 1 and 30 years.';
        }

        $repair = (string) $data['repair_mode'];
        if (!in_array($repair, self::REPAIR_MODES, true)) {
            $errors[] = 'Unknown repair option.';
        } elseif ($repair === self::REPAIR_SCORE && !in_array((string) $data['repair_score'], self::REPAIR_SCORES, true)) {
            $errors[] = 'Choose the EU repairability score, from A to E.';
        } elseif ($repair === self::REPAIR_PARTS && empty($data['has_texts'])) {
            $errors[] = 'Write at least one spare parts or repair text.';
        }

        if ($years <= 0 && $updates === '' && $repair === '') {
            $errors[] = 'The rule says nothing yet: give it GARAN years, software updates or repair.';
        }

        return $errors;
    }

    /**
     * Fecha escrita por el comerciante o en una hoja de cálculo —2030-12-31 o 31.12.2030— como
     * YYYY-MM-DD, o null si no es una fecha que exista.
     *
     * @param string $value
     *
     * @return string|null
     */
    public static function normaliseDate($value)
    {
        $value = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            list(, $year, $month, $day) = $m;
        } elseif (preg_match('/^(\d{1,2})[.\/](\d{1,2})[.\/](\d{4})$/', $value, $m)) {
            list(, $day, $month, $year) = $m;
        } else {
            return null;
        }

        if (!checkdate((int) $month, (int) $day, (int) $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @param int $idProduct
     *
     * @return string
     */
    private static function manufacturerName($idProduct)
    {
        return (string) Db::getInstance()->getValue(
            'SELECT m.`name` FROM `' . _DB_PREFIX_ . 'manufacturer` m
             INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_manufacturer` = m.`id_manufacturer`
             WHERE p.`id_product` = ' . (int) $idProduct
        );
    }

    /**
     * Crea las dos tablas y añade las columnas que falten. Es la misma llamada en la instalación y
     * en la actualización, y se puede ejecutar dos veces: MySQL 5.7 no tiene ADD COLUMN IF NOT
     * EXISTS, así que cada columna se busca antes de añadirla.
     *
     * @return bool
     */
    public static function installTable()
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'bk_guarantee_rule';

        $ok = $db->execute(
            'CREATE TABLE IF NOT EXISTS `' . $table . '` (
                `id_guarantee_rule` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(128) NOT NULL,
                `filter_type` varchar(16) NOT NULL,
                `filter_values` text,
                `years` int(10) unsigned NOT NULL DEFAULT 0,
                `brand` varchar(128) DEFAULT NULL,
                `model` varchar(128) DEFAULT NULL,
                `id_shop` int(10) unsigned NOT NULL DEFAULT 0,
                `priority` int(11) NOT NULL DEFAULT 0,
                `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_guarantee_rule`),
                KEY `active_priority` (`active`, `id_shop`, `priority`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );

        foreach (self::DURABILITY_COLUMNS as $column => $definition) {
            if ($ok && !$db->executeS('SHOW COLUMNS FROM `' . $table . '` LIKE \'' . pSQL($column) . '\'')) {
                $ok = $db->execute('ALTER TABLE `' . $table . '` ADD `' . bqSQL($column) . '` ' . $definition);
            }
        }

        $texts = '';
        foreach (self::PARTS_FIELDS as $field) {
            $texts .= '`' . $field . '` varchar(' . self::TEXT_MAX . ') NOT NULL DEFAULT \'\',';
        }

        return $ok && $db->execute(
            'CREATE TABLE IF NOT EXISTS `' . $table . '_lang` (
                `id_guarantee_rule` int(10) unsigned NOT NULL,
                `id_lang` int(10) unsigned NOT NULL,
                ' . $texts . '
                PRIMARY KEY (`id_guarantee_rule`, `id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    public static function uninstallTable()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bk_guarantee_rule_lang`, `' . _DB_PREFIX_ . 'bk_guarantee_rule`;'
        );
    }
}
