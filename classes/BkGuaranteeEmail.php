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

        // Todo el estilo va en línea y el bloque se acota a su propio ancho: la plantilla del
        // comerciante puede ser cualquier cosa, así que este bloque no hereda nada de ella ni le
        // impone ancho —un table al 100 % deforma un diseño estrecho—.
        return '<div style="margin:24px auto 0;max-width:420px;text-align:left">'
            . '<p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:13px;'
            . 'line-height:1.4;color:#333"><strong>' . Tools::htmlentitiesUTF8($title) . '</strong></p>'
            . '<img src="' . Tools::htmlentitiesUTF8($absolute) . '" alt="'
            . Tools::htmlentitiesUTF8($alt) . '" width="420" style="display:block;width:100%;'
            . 'max-width:420px;height:auto;border:0;outline:none;text-decoration:none">'
            . '</div>';
    }

    /**
     * Inserta el bloque dentro del cuerpo, antes de </body>, en vez de pegarlo detrás del cierre:
     * la plantilla del comerciante es un documento completo y lo que va después de </html> queda
     * fuera del documento, con clientes de correo que lo esconden o lo pintan donde les parece.
     *
     * @param string $html
     * @param string $block
     *
     * @return string
     */
    public static function insertIntoBody($html, $block)
    {
        if ($block === '' || trim((string) $html) === '') {
            return $html;
        }

        foreach (['</body>', '</BODY>'] as $needle) {
            $at = strripos($html, $needle);
            if ($at !== false) {
                return substr($html, 0, $at) . $block . substr($html, $at);
            }
        }

        return $html . $block;
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
        $url = BkGuaranteeNotice::urlFor($iso);
        if ($url === null) {
            return '';
        }

        // En la versión de texto no hay imagen que valga: va el enlace, que es lo único que le
        // sirve a quien recibe el correo en texto plano.
        return PHP_EOL . PHP_EOL . $title . PHP_EOL
            . Tools::getShopDomainSsl(true) . $url . PHP_EOL;
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
     * Etiquetas GARAN de un pedido, ya compuestas como PNG y listas para adjuntar.
     *
     * @param array $templateVars Variables de la plantilla del correo
     *
     * @return array Adjuntos en el formato de Mail::Send
     */
    public static function garanAttachments(array $templateVars)
    {
        $idOrder = isset($templateVars['{id_order}']) ? (int) $templateVars['{id_order}'] : 0;
        if ($idOrder <= 0) {
            return [];
        }

        $out = [];
        foreach (BkGuaranteeLabelImage::forOrder($idOrder) as $label) {
            $png = BkGuaranteeLabelImage::render($label);
            if ($png === null) {
                continue;
            }

            $out[] = [
                'content' => $png,
                'name' => 'garan-' . Tools::str2url($label['model']) . '.jpg',
                'mime' => 'image/jpeg',
            ];
        }

        return $out;
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
        return self::mergeAttachments($existing, [$ours]);
    }

    /**
     * @param mixed $existing
     * @param array $ours Lista de adjuntos
     *
     * @return mixed
     */
    public static function mergeAttachments($existing, array $ours)
    {
        if (empty($ours)) {
            return $existing;
        }

        if (empty($existing)) {
            return $ours;
        }

        $list = isset($existing['content']) ? [$existing] : $existing;

        return array_merge($list, $ours);
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
