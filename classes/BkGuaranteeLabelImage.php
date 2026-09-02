<?php
/**
 * Compone la etiqueta GARAN como imagen, para donde no hay navegador que la monte: el correo.
 *
 * En el front la etiqueta es el arte oficial vaciado más un SVG encima. En un correo no hay SVG que
 * valga, así que aquí se dibuja lo mismo con GD sobre el mismo fichero y en las mismas coordenadas.
 * Los elementos que la norma declara intocables siguen siendo píxeles del Diario Oficial: solo se
 * escriben los tres campos editables.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeLabelImage
{
    /** Arte oficial con los tres campos vaciados */
    const ART = 'views/img/garan-blank.png';
    /** Las mismas coordenadas que usa el SVG del front, en el espacio del arte (741 x 780) */
    const BRAND_X = 20;
    const BRAND_Y = 207;
    const MODEL_X = 727;
    const MODEL_Y = 207;
    const YEARS_X = 22;
    const YEARS_Y = 414;
    const FIELD_SIZE = 29;
    const YEARS_SIZE = 222;
    /** Calidad del JPEG: alta, porque el QR tiene que seguir leyéndose */
    const QUALITY = 92;
    /** Tope de etiquetas por pedido: un correo con veinte adjuntos no lo quiere nadie */
    const MAX_PER_ORDER = 5;

    /**
     * @param array $label years, brand, model
     *
     * @return string|null Contenido JPEG, o null si no se pudo componer
     */
    public static function render(array $label)
    {
        if (!function_exists('imagettftext')) {
            BkGuaranteeLogger::warning('GD sin soporte de TrueType: la etiqueta no viaja en el correo');

            return null;
        }

        $art = _PS_MODULE_DIR_ . 'bkguarantee/' . self::ART;
        $image = @imagecreatefrompng($art);
        if (!$image) {
            BkGuaranteeLogger::error('No se pudo abrir el arte de la etiqueta: ' . $art);

            return null;
        }

        $black = imagecolorallocate($image, 0, 0, 0);
        $regular = self::font('Inter-Regular.ttf');
        $extraBold = self::font('Inter-ExtraBold.ttf');

        if ($regular === null || $extraBold === null) {
            imagedestroy($image);

            return null;
        }

        imagettftext($image, self::FIELD_SIZE, 0, self::BRAND_X, self::BRAND_Y, $black, $regular, $label['brand']);

        // El identificador del modelo va alineado a la derecha: se mide primero y se resta
        $box = imagettfbbox(self::FIELD_SIZE, 0, $regular, $label['model']);
        $modelWidth = $box[2] - $box[0];
        imagettftext($image, self::FIELD_SIZE, 0, self::MODEL_X - $modelWidth, self::MODEL_Y, $black, $regular, $label['model']);

        imagettftext($image, self::YEARS_SIZE, 0, self::YEARS_X, self::YEARS_Y, $black, $extraBold, (string) (int) $label['years']);

        // JPEG y no PNG: el arte viene de una foto y en PNG cada etiqueta pesa 400 kB, que en un
        // pedido con varios modelos convierte el correo en un ladrillo. La calidad se deja alta
        // para que el QR siga leyéndose.
        ob_start();
        imagejpeg($image, null, self::QUALITY);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        return $jpeg ?: null;
    }

    /**
     * @param string $file
     *
     * @return string|null
     */
    private static function font($file)
    {
        $path = _PS_MODULE_DIR_ . 'bkguarantee/views/fonts/' . $file;
        if (!is_file($path)) {
            BkGuaranteeLogger::error('Falta la tipografía ' . $file . ': la etiqueta no se puede componer');

            return null;
        }

        return $path;
    }

    /**
     * Etiquetas de los productos de un pedido, sin repetir: dos unidades del mismo modelo no
     * generan dos adjuntos iguales.
     *
     * @param int $idOrder
     *
     * @return array Cada entrada: years, brand, model
     */
    public static function forOrder($idOrder)
    {
        // Se leen las líneas del pedido directamente: Order::getProducts() arrastra la pila de
        // precios y moneda, que en el envío de un correo no hace ninguna falta.
        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT `product_id` FROM `' . _DB_PREFIX_ . 'order_detail`
             WHERE `id_order` = ' . (int) $idOrder
        );
        if (empty($rows)) {
            return [];
        }

        $labels = [];
        foreach ($rows as $row) {
            $label = BkGuaranteeRule::labelFor((int) $row['product_id']);
            if ($label === null) {
                continue;
            }

            $key = $label['years'] . '|' . $label['brand'] . '|' . $label['model'];
            $labels[$key] = $label;
        }

        if (count($labels) > self::MAX_PER_ORDER) {
            BkGuaranteeLogger::warning(sprintf(
                'El pedido %d tiene %d etiquetas distintas; solo viajan las %d primeras',
                (int) $idOrder,
                count($labels),
                self::MAX_PER_ORDER
            ));
            $labels = array_slice($labels, 0, self::MAX_PER_ORDER, true);
        }

        return array_values($labels);
    }
}
