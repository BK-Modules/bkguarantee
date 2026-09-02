<?php
/**
 * Traza del módulo en fichero propio. Es la única vía de logging: nada de error_log ni de
 * PrestaShopLogger, que mezclarían estas líneas con las del núcleo.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeLogger
{
    const FILE = 'bkguarantee.log';
    /** Tamaño a partir del cual el fichero se rota */
    const MAX_BYTES = 1048576;

    public static function error($message)
    {
        self::write('error', $message);
    }

    public static function warning($message)
    {
        self::write('warning', $message);
    }

    public static function confirmation($message)
    {
        self::write('confirmation', $message);
    }

    /**
     * Solo escribe con la traza encendida: en marcha normal el fichero no crece.
     */
    public static function debug($message)
    {
        if (BkGuaranteeConfig::isOn(BkGuaranteeConfig::DEBUG)) {
            self::write('debug', $message);
        }
    }

    private static function write($level, $message)
    {
        $dir = _PS_MODULE_DIR_ . 'bkguarantee/log/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . self::FILE;
        if (is_file($file) && filesize($file) > self::MAX_BYTES) {
            @rename($file, $file . '.1');
        }

        $line = sprintf('[%s] %s: %s%s', date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        @file_put_contents($file, $line, FILE_APPEND);
    }
}
