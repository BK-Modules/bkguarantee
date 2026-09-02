<?php
/**
 * Resuelve qué imagen oficial del aviso armonizado corresponde a un idioma.
 *
 * El aviso es una obra cerrada del Reglamento de Ejecución (UE) 2025/1960: el anexo I prohíbe
 * editar cualquiera de sus elementos, así que el módulo no compone nada — sirve el fichero tal
 * como lo publica el Diario Oficial en cada lengua.
 *
 * INVARIANTE: si no hay imagen para el idioma que se está pintando, no se pinta nada. Enseñar el
 * aviso de otra lengua es peor que no enseñarlo: el comerciante creería estar cumpliendo y no lo
 * estaría. Los idiomas sin arte se denuncian en la pantalla de configuración.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeNotice
{
    /** Directorio del arte oficial, relativo al módulo */
    const DIR = 'views/img/notice/';
    /** Extensión con la que el Diario Oficial publica las figuras */
    const EXT = '.jpg';

    /**
     * Fracción del ancho que ocupa el código QR en cada arte, medida sobre los ficheros oficiales:
     * el del aviso es proporcionalmente más pequeño que el de la etiqueta.
     */
    const QR_RATIO_NOTICE = 0.18;
    const QR_RATIO_LABEL = 0.263;
    /** Lado del QR en píxeles a partir del cual se escanea con comodidad desde una pantalla */
    const QR_COMFORTABLE = 110;
    /** Lado por debajo del cual no hay nada que hacer */
    const QR_MINIMUM = 80;

    /**
     * El anexo I exige que el QR sea escaneable con un móvil normal y con luz normal, y eso no
     * depende del arte sino del ancho al que lo pinte la tienda: a 240 px el código baja de 45 px
     * de lado y no hay teléfono que lo lea.
     *
     * @param int   $width Ancho al que se pinta la pieza
     * @param float $ratio Proporción del QR dentro de la pieza
     *
     * @return array side, level (ok|tight|bad)
     */
    public static function qrCheck($width, $ratio = self::QR_RATIO_NOTICE)
    {
        $side = (int) round((int) $width * $ratio);

        if ($side >= self::QR_COMFORTABLE) {
            $level = 'ok';
        } elseif ($side >= self::QR_MINIMUM) {
            $level = 'tight';
        } else {
            $level = 'bad';
        }

        return ['side' => $side, 'level' => $level];
    }

    /**
     * @param float $ratio
     *
     * @return int Ancho mínimo al que el QR se escanea con comodidad
     */
    public static function widthForComfortableQr($ratio = self::QR_RATIO_NOTICE)
    {
        return (int) ceil(self::QR_COMFORTABLE / $ratio);
    }

    /**
     * @param string $isoCode Código ISO del idioma (es, en, fr…)
     *
     * @return string|null Ruta absoluta en disco, o null si ese idioma no tiene arte instalado
     */
    public static function pathFor($isoCode)
    {
        $iso = Tools::strtolower(preg_replace('/[^a-zA-Z]/', '', (string) $isoCode));
        if ($iso === '') {
            return null;
        }

        $path = _PS_MODULE_DIR_ . 'bkguarantee/' . self::DIR . $iso . self::EXT;

        return is_file($path) ? $path : null;
    }

    /**
     * @param string $isoCode
     *
     * @return string|null URL pública del aviso, con la fecha del fichero para romper caché
     */
    public static function urlFor($isoCode)
    {
        $path = self::pathFor($isoCode);
        if ($path === null) {
            return null;
        }

        $iso = Tools::strtolower(preg_replace('/[^a-zA-Z]/', '', (string) $isoCode));

        return _MODULE_DIR_ . 'bkguarantee/' . self::DIR . $iso . self::EXT . '?v=' . filemtime($path);
    }

    /**
     * Estado por idioma de la tienda, para la pantalla de configuración.
     *
     * @return array Cada fila: iso, name, ready
     */
    public static function coverage()
    {
        $rows = [];
        foreach (Language::getLanguages(false) as $lang) {
            $rows[] = [
                'iso' => $lang['iso_code'],
                'name' => $lang['name'],
                'ready' => self::pathFor($lang['iso_code']) !== null,
            ];
        }

        return $rows;
    }

    /**
     * @return array Códigos ISO de los idiomas activos que se quedarían sin aviso
     */
    public static function missingLanguages()
    {
        $missing = [];
        foreach (self::coverage() as $row) {
            if (!$row['ready']) {
                $missing[] = $row['iso'];
            }
        }

        return $missing;
    }
}
