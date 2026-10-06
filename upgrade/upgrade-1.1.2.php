<?php
/**
 * BkGuarantee — actualización a 1.1.2.
 *
 * La etiqueta GARAN de cada producto del carrito se muestra inmediatamente antes del botón de
 * compra (§ 312j Abs. 2 BGB, § 8 Abs. 1 FAGG, art. 51 c.2 Codice del consumo). Registra el hook
 * del hueco encima del botón y da de alta los ajustes que falten, con la posición por defecto de la
 * versión de PrestaShop. No pisa ningún ajuste que ya exista y se puede ejecutar más de una vez.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_2($module)
{
    if (!$module->registerHooks()) {
        return false;
    }

    BkGuaranteeConfig::installDefaults();

    BkGuaranteeLogger::confirmation(
        'Actualizado a ' . $module->version . ': etiqueta GARAN antes del botón de compra en la posición '
        . BkGuaranteeConfig::getGaranCheckoutPlacement()
    );

    \BkModules\Registry\V1\InstallReporter::report($module->name, $module->version, 'upgrade');

    return true;
}
