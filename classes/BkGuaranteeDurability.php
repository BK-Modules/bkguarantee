<?php
/**
 * Lo que el fabricante dice de actualizaciones de software y reparación de un producto, listo
 * para pintar.
 *
 * Es información precontractual (Art. 246a § 1 Abs. 1 Nr. 11c, 20 y 21 EGBGB; § 4 Abs. 1 Z 12d, 20
 * y 21 FAGG; art. 49 c.1 lett. n-quater, v-bis y v-ter del Codice del consumo): va en la ficha,
 * antes de comprar, y otra vez en la confirmación del pedido. Las dos salen de aquí y con los
 * mismos textos, porque lo que se enseñó antes de comprar es lo que pasa a ser contenido del
 * contrato (§ 312d Abs. 1 BGB, § 4 Abs. 4 FAGG).
 *
 * Los rótulos se traducen con el XLF del módulo en el idioma pedido —el del visitante en la
 * ficha, el del pedido en el correo—, no con el traductor del contexto. Un texto del fabricante
 * que falta en un idioma no se pinta en ese idioma: el de otra lengua no informa a nadie.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeDurability
{
    /** Dominio de los rótulos del front y del correo */
    const DOMAIN = 'Modules.Bkguarantee.Shop';

    /** Productos de un pedido con bloque propio en el correo: por encima es un inventario */
    const MAX_PER_ORDER = 20;

    /** Rótulo de cada texto de repuestos y reparación, en el orden de BkGuaranteeRule::PARTS_FIELDS */
    const PARTS_LABELS = [
        'parts_availability' => 'Spare parts: availability',
        'parts_cost' => 'Spare parts: estimated cost',
        'parts_order' => 'Spare parts: how to order',
        'repair_manuals' => 'Repair and maintenance instructions',
        'repair_restrictions' => 'Repair restrictions',
    ];

    /**
     * Filas de un producto en un idioma: rótulo, valor en texto y valor en HTML (escapado, con las
     * URL convertidas en enlace). Vacío si el fabricante no ha dado nada que pintar.
     *
     * @param int $idProduct
     * @param int $idLang
     *
     * @return array [['label' => ..., 'text' => ..., 'html' => ...]]
     */
    public static function linesFor($idProduct, $idLang)
    {
        $resolved = BkGuaranteeRule::resolve($idProduct);
        $rows = [];

        $updates = $resolved['updates'];
        if ($updates !== null) {
            $value = '';
            if ($updates->updates_mode === BkGuaranteeRule::UPDATES_DATE && $updates->updates_until !== '') {
                $value = self::text($idLang, 'at least until %date%', ['%date%' => self::date($updates->updates_until, $idLang)]);
            } elseif ($updates->updates_mode === BkGuaranteeRule::UPDATES_YEARS && (int) $updates->updates_years > 0) {
                $value = (int) $updates->updates_years === 1
                    ? self::text($idLang, 'for at least 1 year')
                    : self::text($idLang, 'for at least %years% years', ['%years%' => (int) $updates->updates_years]);
            }
            if ($value !== '') {
                $rows[] = self::row(self::text($idLang, 'Software updates'), $value);
            }
        }

        $repair = $resolved['repair'];
        if ($repair !== null) {
            if ($repair->repair_mode === BkGuaranteeRule::REPAIR_SCORE
                && in_array($repair->repair_score, BkGuaranteeRule::REPAIR_SCORES, true)
            ) {
                $rows[] = self::row(self::text($idLang, 'Repairability score'), $repair->repair_score . ' (A–E)');
            } elseif ($repair->repair_mode === BkGuaranteeRule::REPAIR_PARTS) {
                foreach ($repair->texts($idLang) as $field => $value) {
                    if ($value !== '') {
                        $rows[] = self::row(self::text($idLang, self::PARTS_LABELS[$field]), $value);
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Productos de un pedido con lo que su fabricante dice, en el idioma del pedido.
     *
     * @param int $idOrder
     * @param int $idLang
     *
     * @return array [['name' => ..., 'rows' => [...]]]
     */
    public static function forOrder($idOrder, $idLang)
    {
        $lines = Db::getInstance()->executeS(
            'SELECT `product_id`, MIN(`product_name`) AS `product_name` FROM `' . _DB_PREFIX_ . 'order_detail`
             WHERE `id_order` = ' . (int) $idOrder . '
             GROUP BY `product_id`
             ORDER BY MIN(`id_order_detail`)'
        );

        $out = [];
        foreach ((array) $lines as $line) {
            $rows = self::linesFor((int) $line['product_id'], $idLang);
            if (empty($rows)) {
                continue;
            }

            // El nombre del producto sin la combinación: el dato es del modelo, no de la talla.
            $name = (string) Product::getProductName((int) $line['product_id'], null, (int) $idLang);
            $out[] = ['name' => $name !== '' ? $name : $line['product_name'], 'rows' => $rows];

            if (count($out) >= self::MAX_PER_ORDER) {
                BkGuaranteeLogger::warning(sprintf(
                    'El pedido %d tiene más de %d productos con datos de actualizaciones o reparación; el correo lleva los %d primeros',
                    (int) $idOrder,
                    self::MAX_PER_ORDER,
                    self::MAX_PER_ORDER
                ));
                break;
            }
        }

        return $out;
    }

    /**
     * Rótulo del front y del correo en un idioma, con el XLF del módulo.
     *
     * @param int    $idLang
     * @param string $string Cadena origen en inglés
     * @param array  $params
     *
     * @return string
     */
    public static function text($idLang, $string, array $params = [])
    {
        $language = Language::getLanguage((int) $idLang);

        return \BkModules\I18n\V1\Translations::trans(
            'bkguarantee',
            self::DOMAIN,
            $language ? (string) $language['locale'] : '',
            $string,
            $params
        );
    }

    /**
     * @param string $label
     * @param string $value
     *
     * @return array
     */
    private static function row($label, $value)
    {
        return ['label' => $label, 'text' => $value, 'html' => self::linkify($value)];
    }

    /**
     * Texto escapado con sus URL http(s) convertidas en enlace. Se parte por las URL antes de
     * escapar: escapar primero metería las entidades dentro de la dirección.
     *
     * @param string $text
     *
     * @return string
     */
    private static function linkify($text)
    {
        $parts = preg_split('~(https?://[^\s<>"]+[^\s<>".,;:!?)\]])~i', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';
        foreach ($parts as $i => $part) {
            $escaped = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
            $html .= $i % 2
                ? '<a href="' . $escaped . '" target="_blank" rel="nofollow noopener">' . $escaped . '</a>'
                : $escaped;
        }

        return $html;
    }

    /**
     * Fecha en el formato corto del idioma (31.12.2030, 31/12/2030, 12/31/2030).
     *
     * @param string $date YYYY-MM-DD
     * @param int    $idLang
     *
     * @return string
     */
    private static function date($date, $idLang)
    {
        $language = Language::getLanguage((int) $idLang);
        $format = $language && !empty($language['date_format_lite']) ? $language['date_format_lite'] : 'Y-m-d';
        $time = strtotime($date . ' 12:00:00');

        return $time ? date($format, $time) : (string) $date;
    }
}
