<?php
/**
 * El aviso en el correo de confirmación de pedido.
 *
 * La información precontractual tiene que seguir a mano del consumidor después de la compra, y el
 * correo de confirmación es el soporte duradero que ya llega a todos. Va de dos formas a la vez y
 * por un motivo: el cuerpo lo enseña de un vistazo, pero media bandeja de entrada bloquea las
 * imágenes remotas, así que el adjunto es el que garantiza que el aviso llega de verdad.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeEmail
{
    /** Plantilla del correo de confirmación de pedido */
    const TEMPLATE = 'order_conf';

    /**
     * @param string $template
     *
     * @return bool
     */
    public static function isOrderConfirmation($template)
    {
        return self::TEMPLATE === (string) $template;
    }

    /**
     * Bloque HTML que se añade al final del cuerpo del correo.
     *
     * La URL es absoluta y de la propia tienda: un correo no tiene contexto de rutas relativas.
     *
     * @param int    $idLang
     * @param string $title
     * @param string $alt
     *
     * @return string
     */
    public static function htmlBlock($idLang, $title, $alt)
    {
        $iso = self::isoFor($idLang);
        $url = BkGuaranteeNotice::urlFor($iso);
        if ($url === null) {
            return '';
        }

        // urlFor() ya devuelve una ruta desde la raíz del dominio, con la base de la tienda
        // incluida: basta anteponerle el dominio. Recortarle la base a mano rompía la URL en las
        // tiendas instaladas en la raíz, donde __PS_BASE_URI__ es una sola barra.
        $absolute = Tools::getShopDomainSsl(true) . $url;

        return '<table style="width:100%;border-collapse:collapse;margin:24px 0 0">'
            . '<tr><td style="padding:0 0 8px;font-family:Arial,sans-serif;font-size:13px;color:#333">'
            . '<strong>' . Tools::htmlentitiesUTF8($title) . '</strong></td></tr>'
            . '<tr><td><img src="' . Tools::htmlentitiesUTF8($absolute) . '" alt="'
            . Tools::htmlentitiesUTF8($alt) . '" width="420" style="display:block;max-width:100%;height:auto;border:0"></td></tr>'
            . '</table>';
    }

    /**
     * @param int    $idLang
     * @param string $title
     *
     * @return string
     */
    public static function textBlock($idLang, $title)
    {
        $iso = self::isoFor($idLang);
        if (BkGuaranteeNotice::pathFor($iso) === null) {
            return '';
        }

        return PHP_EOL . PHP_EOL . $title . PHP_EOL;
    }

    /**
     * Adjunto del aviso, en el formato que espera Mail::Send.
     *
     * @param int $idLang
     *
     * @return array|null content, name, mime
     */
    public static function attachment($idLang)
    {
        $iso = self::isoFor($idLang);
        $path = BkGuaranteeNotice::pathFor($iso);
        if ($path === null) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            BkGuaranteeLogger::error('No se pudo leer el aviso para adjuntarlo: ' . $path);

            return null;
        }

        return [
            'content' => $content,
            'name' => 'garantia-legal-' . $iso . '.jpg',
            'mime' => 'image/jpeg',
        ];
    }

    /**
     * Mail::Send acepta un adjunto suelto o una lista: se normaliza a lista antes de añadir el
     * nuestro, para no pisar el que traiga otro módulo —una factura, por ejemplo—.
     *
     * @param mixed $existing
     * @param array $ours
     *
     * @return array
     */
    public static function mergeAttachment($existing, array $ours)
    {
        if (empty($existing)) {
            return [$ours];
        }

        if (isset($existing['content'])) {
            return [$existing, $ours];
        }

        $existing[] = $ours;

        return $existing;
    }

    /**
     * @param int $idLang
     *
     * @return string
     */
    private static function isoFor($idLang)
    {
        $iso = Language::getIsoById((int) $idLang);

        return $iso ? $iso : Context::getContext()->language->iso_code;
    }
}
