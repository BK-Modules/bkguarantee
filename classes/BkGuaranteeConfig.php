<?php
/**
 * Única fuente de configuración del módulo: toda clave nueva entra aquí, no en el controlador.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeConfig
{
    /** Interruptor maestro */
    const ENABLED = 'BK_GUAR_ON';
    /** Aviso en la ficha de producto */
    const ON_PRODUCT = 'BK_GUAR_PRODUCT';
    /** Aviso en el resumen del pedido, antes de confirmar */
    const ON_CHECKOUT = 'BK_GUAR_CHECKOUT';
    /** Ancho máximo del aviso en píxeles */
    const WIDTH = 'BK_GUAR_WIDTH';
    /** Oculta el aviso a los clientes de grupos B2B */
    const HIDE_FOR_B2B = 'BK_GUAR_HIDE_B2B';
    /** Grupos considerados B2B, separados por comas */
    const B2B_GROUPS = 'BK_GUAR_B2B_GROUPS';
    /** Traza de depuración */
    const DEBUG = 'BK_GUAR_DEBUG';

    /** Ancho por debajo del cual el aviso deja de leerse */
    const WIDTH_MIN = 240;
    /** Ancho por encima del cual el aviso desborda la columna de cualquier tema */
    const WIDTH_MAX = 720;

    /**
     * @return array Claves con su valor por defecto
     */
    public static function getDefaults()
    {
        return [
            self::ENABLED => '1',
            self::ON_PRODUCT => '1',
            self::ON_CHECKOUT => '1',
            self::WIDTH => '380',
            self::HIDE_FOR_B2B => '0',
            self::B2B_GROUPS => '',
            self::DEBUG => '0',
        ];
    }

    public static function installDefaults()
    {
        foreach (self::getDefaults() as $key => $value) {
            if (Configuration::get($key) === false) {
                Configuration::updateValue($key, $value);
            }
        }
    }

    public static function uninstallKeys()
    {
        foreach (array_keys(self::getDefaults()) as $key) {
            Configuration::deleteByName($key);
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function isOn($key)
    {
        return (bool) Configuration::get($key);
    }

    /**
     * El ancho se acota al leerlo, no al guardarlo: así un valor heredado de otra instalación o
     * escrito a mano en la base de datos tampoco puede dejar el aviso ilegible.
     *
     * @return int
     */
    public static function getWidth()
    {
        $width = (int) Configuration::get(self::WIDTH);

        return max(self::WIDTH_MIN, min(self::WIDTH_MAX, $width ?: 380));
    }

    /**
     * @return array Identificadores de grupo considerados B2B
     */
    public static function getB2bGroups()
    {
        $raw = (string) Configuration::get(self::B2B_GROUPS);
        $ids = array_filter(array_map('intval', explode(',', $raw)));

        return array_values(array_unique($ids));
    }
}
