<?php
/**
 * Regla de garantía comercial de durabilidad: los datos con los que se compone la etiqueta GARAN.
 *
 * La etiqueta identifica **un modelo concreto**, no una categoría ni una marca. Lo que la regla
 * hace es decir a qué productos se les aplica un mismo dato del fabricante, que es el trabajo real
 * en un catálogo grande; el dato sigue resolviéndose producto a producto.
 *
 * Solo puede llevar etiqueta la garantía que ofrece el **productor**, sin coste adicional, sobre
 * la totalidad del bien y por más de dos años (considerando 8 del Reglamento (UE) 2025/1960). Una
 * garantía comercial de la propia tienda no es GARAN.
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

    /** Duración mínima que la norma exige para que exista etiqueta */
    const MIN_YEARS = 3;

    /** @var string */
    public $name;
    /** @var string category|manufacturer|products */
    public $filter_type;
    /** @var string Identificadores separados por comas */
    public $filter_values;
    /** @var int Años de la garantía del productor */
    public $years;
    /** @var string Marca del productor; vacío = la marca del producto */
    public $brand;
    /** @var string Identificador del modelo; vacío = el MPN del producto */
    public $model;
    /** @var int A mayor prioridad, antes se evalúa */
    public $priority;
    /** @var bool */
    public $active;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    public static $definition = [
        'table' => 'bk_guarantee_rule',
        'primary' => 'id_guarantee_rule',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'filter_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'filter_values' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 2048],
            'years' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'brand' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'model' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

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
     * La primera regla activa que case, por prioridad descendente y luego por antigüedad: una
     * regla de producto suelto se pone por encima subiéndole la prioridad, no cambiando el orden
     * en el que se crearon.
     *
     * @param int $idProduct
     *
     * @return BkGuaranteeRule|null
     */
    public static function forProduct($idProduct)
    {
        $idProduct = (int) $idProduct;
        if ($idProduct <= 0) {
            return null;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule`
             WHERE `active` = 1 ORDER BY `priority` DESC, `id_guarantee_rule` ASC'
        );
        if (empty($rows)) {
            return null;
        }

        $idManufacturer = (int) Db::getInstance()->getValue(
            'SELECT `id_manufacturer` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . $idProduct
        );
        $categories = array_map('intval', (array) Product::getProductCategories($idProduct));

        foreach ($rows as $row) {
            $rule = new self();
            $rule->hydrate($row);

            if ($rule->matches($idProduct, $idManufacturer, $categories)) {
                return $rule;
            }
        }

        return null;
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
        $rule = self::forProduct($idProduct);
        if ($rule === null) {
            return null;
        }

        $years = (int) $rule->years;
        $brand = trim((string) $rule->brand);
        $model = trim((string) $rule->model);

        if ($brand === '') {
            $brand = trim((string) self::manufacturerName($idProduct));
        }
        if ($model === '') {
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

    public static function installTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bk_guarantee_rule` (
                `id_guarantee_rule` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(128) NOT NULL,
                `filter_type` varchar(16) NOT NULL,
                `filter_values` text,
                `years` int(10) unsigned NOT NULL DEFAULT 0,
                `brand` varchar(128) DEFAULT NULL,
                `model` varchar(128) DEFAULT NULL,
                `priority` int(11) NOT NULL DEFAULT 0,
                `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_guarantee_rule`),
                KEY `active_priority` (`active`, `priority`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    public static function uninstallTable()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bk_guarantee_rule`;'
        );
    }
}
