<?php
/**
 * BkGuarantee — actualización a 1.2.0.
 *
 * Actualizaciones de software, índice de reparabilidad y repuestos (Directiva (UE) 2024/825): las
 * reglas pasan a decir también eso. Añade a `bk_guarantee_rule` las columnas de actualizaciones y
 * reparación, crea `bk_guarantee_rule_lang` con los textos por idioma, registra el hook que rellena
 * el bloque del correo, da de alta los ajustes que falten —como valor global, el que heredan todas
 * las tiendas— y renombra la pestaña de las reglas a «Datos del fabricante».
 *
 * Las reglas existentes quedan con actualizaciones y reparación vacías, es decir, heredando: dan
 * exactamente la misma etiqueta GARAN que antes. No pisa ningún ajuste y se puede ejecutar dos veces.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($module)
{
    if (!BkGuaranteeRule::installTable()) {
        BkGuaranteeLogger::error('Actualización a 1.2.0: no se pudieron crear las columnas o la tabla de textos');

        return false;
    }

    if (!$module->registerHooks()) {
        return false;
    }

    BkGuaranteeConfig::installDefaults();

    if (!BkGuaranteeTabsInstaller::install($module->name)) {
        BkGuaranteeLogger::error('Actualización a 1.2.0: no se pudieron poner al día las pestañas');
    }

    BkGuaranteeLogger::confirmation('Actualizado a ' . $module->version . ': actualizaciones de software y reparación');

    \BkModules\Registry\V1\InstallReporter::report($module->name, $module->version, 'upgrade');

    return true;
}
