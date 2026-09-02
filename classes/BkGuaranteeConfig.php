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
    /** Presentación: card | framed | plain | band */
    const STYLE = 'BK_GUAR_STYLE';
    /** Alineación dentro de su columna: left | center */
    const ALIGN = 'BK_GUAR_ALIGN';
    /** Punto de la ficha donde se pinta: info | thumbs | footer */
    const PLACEMENT = 'BK_GUAR_PLACEMENT';
    /** Etiqueta GARAN en la ficha de producto */
    const GARAN_ON = 'BK_GUAR_GARAN';
    /** Punto de la ficha donde va la etiqueta: info | thumbs | footer */
    const GARAN_PLACEMENT = 'BK_GUAR_GARAN_PLACE';
    /** Ancho máximo de la etiqueta en píxeles */
    const GARAN_WIDTH = 'BK_GUAR_GARAN_W';
    /** Visualización anidada de la etiqueta: se abre entera al primer clic */
    const GARAN_NESTED = 'BK_GUAR_GARAN_NEST';
    /** Aviso en el correo de confirmación de pedido */
    const ON_EMAIL = 'BK_GUAR_EMAIL';
    /** Adjunta además el aviso al correo, para que llegue aunque el cliente bloquee imágenes */
    const EMAIL_ATTACH = 'BK_GUAR_EMAIL_ATT';
    /** Punto del checkout donde se pinta: payment | summary */
    const CHECKOUT_PLACEMENT = 'BK_GUAR_CO_PLACE';
    /** Alcance del catálogo: all | categories */
    const SCOPE_MODE = 'BK_GUAR_SCOPE';
    /** Categorías en las que se muestra cuando el alcance es 'categories' */
    const INCLUDED_CATEGORIES = 'BK_GUAR_CAT_IN';
    /** Categorías que nunca llevan aviso */
    const EXCLUDED_CATEGORIES = 'BK_GUAR_CAT_OUT';
    /** Productos que nunca llevan aviso */
    const EXCLUDED_PRODUCTS = 'BK_GUAR_PROD_OUT';
    /** Deja fuera los productos virtuales: servicios y contenido digital no son bienes */
    const SKIP_VIRTUAL = 'BK_GUAR_SKIP_VIRTUAL';
    /** Traza de depuración */
    const DEBUG = 'BK_GUAR_DEBUG';

    /** Alcances admitidos */
    const SCOPE_MODES = ['all', 'categories'];

    /** Presentaciones admitidas */
    const STYLES = ['card', 'framed', 'plain', 'band'];
    /** Alineaciones admitidas */
    const ALIGNS = ['left', 'center'];
    /**
     * Puntos del checkout admitidos, con el hook que los sirve.
     *
     * displayCheckoutSubtotalDetails NO sirve: el tema solo lo dispara en la línea de gastos de
     * envío y dentro de un <small>, así que en un carrito sin transporte no llega a ejecutarse.
     */
    const CHECKOUT_PLACEMENTS = [
        'summary' => 'displayCheckoutSummaryTop',
        'payment' => 'displayPaymentTop',
    ];
    /** Puntos de la ficha admitidos, con el hook que los sirve */
    const PLACEMENTS = [
        'info' => 'displayProductAdditionalInfo',
        'thumbs' => 'displayAfterProductThumbs',
        'footer' => 'displayFooterProduct',
    ];

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
            self::STYLE => 'card',
            self::ALIGN => 'left',
            self::PLACEMENT => 'info',
            self::CHECKOUT_PLACEMENT => 'summary',
            self::GARAN_ON => '1',
            self::GARAN_PLACEMENT => 'thumbs',
            self::GARAN_WIDTH => '300',
            self::GARAN_NESTED => '0',
            self::ON_EMAIL => '1',
            self::EMAIL_ATTACH => '1',
            self::SCOPE_MODE => 'all',
            self::INCLUDED_CATEGORIES => '',
            self::EXCLUDED_CATEGORIES => '',
            self::EXCLUDED_PRODUCTS => '',
            self::SKIP_VIRTUAL => '0',
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
     * Los tres ajustes de presentación se validan al leerlos contra su lista blanca: un valor
     * heredado de otra instalación o escrito a mano en la base de datos no puede dejar la tarjeta
     * con una clase que el CSS no conoce.
     *
     * @return string
     */
    public static function getStyle()
    {
        $value = (string) Configuration::get(self::STYLE);

        return in_array($value, self::STYLES, true) ? $value : 'card';
    }

    /**
     * @return string
     */
    public static function getAlign()
    {
        $value = (string) Configuration::get(self::ALIGN);

        return in_array($value, self::ALIGNS, true) ? $value : 'left';
    }

    /**
     * @return string
     */
    public static function getPlacement()
    {
        $value = (string) Configuration::get(self::PLACEMENT);

        return isset(self::PLACEMENTS[$value]) ? $value : 'info';
    }

    /**
     * @return string
     */
    public static function getGaranPlacement()
    {
        $value = (string) Configuration::get(self::GARAN_PLACEMENT);

        return isset(self::PLACEMENTS[$value]) ? $value : 'thumbs';
    }

    /**
     * @return int
     */
    public static function getGaranWidth()
    {
        $width = (int) Configuration::get(self::GARAN_WIDTH);

        return max(180, min(520, $width ?: 300));
    }

    /**
     * @return string
     */
    public static function getCheckoutPlacement()
    {
        $value = (string) Configuration::get(self::CHECKOUT_PLACEMENT);

        return isset(self::CHECKOUT_PLACEMENTS[$value]) ? $value : 'summary';
    }

    /**
     * @return string
     */
    public static function getScopeMode()
    {
        $value = (string) Configuration::get(self::SCOPE_MODE);

        return in_array($value, self::SCOPE_MODES, true) ? $value : 'all';
    }

    /**
     * @return array Identificadores de grupo considerados B2B
     */
    public static function getB2bGroups()
    {
        return self::idList(self::B2B_GROUPS);
    }

    /**
     * @return array
     */
    public static function getIncludedCategories()
    {
        return self::idList(self::INCLUDED_CATEGORIES);
    }

    /**
     * @return array
     */
    public static function getExcludedCategories()
    {
        return self::idList(self::EXCLUDED_CATEGORIES);
    }

    /**
     * @return array
     */
    public static function getExcludedProducts()
    {
        return self::idList(self::EXCLUDED_PRODUCTS);
    }

    /**
     * Las listas se guardan como texto separado por comas y se limpian al leerlas: así una lista
     * escrita a mano o traída de otra instalación no puede colar nada que no sea un identificador.
     *
     * @param string $key
     *
     * @return array
     */
    private static function idList($key)
    {
        $raw = (string) Configuration::get($key);
        $ids = array_filter(array_map('intval', preg_split('/[^0-9]+/', $raw)));

        return array_values(array_unique($ids));
    }
}
