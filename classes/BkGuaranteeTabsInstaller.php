<?php
/**
 * TabsInstaller — gestión de tabs del módulo bkguarantee.
 *
 * El nombre lleva el del módulo porque la clase es global: dos módulos con una clase `TabsInstaller`
 * definirían la misma y ganaría la que cargue primero el autoloader, cruzando la columna `module`
 * de sus pestañas.
 *
 * @author BK Modules
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeTabsInstaller
{
    private static $tabs_structure = [
        [
            'name' => [
                'en' => 'EU Guarantee Notice',
                'es' => 'Aviso de garantía UE',
                'fr' => 'Avis de garantie UE',
                'de' => 'EU-Gewährleistungshinweis',
                'it' => 'Avviso di garanzia UE',
                'pt' => 'Aviso de garantia UE',
            ],
            'class_name' => 'AdminBkGuaranteeConfig',
            'parent_class_name' => 'CONFIGURE',
            'wording' => 'EU Guarantee Notice',
            'wording_domain' => 'Modules.Bkguarantee.Admin',
            'icon' => 'verified_user',
        ],
    ];

    public static function install($module_name)
    {
        $languages = Language::getLanguages(false);

        foreach (self::$tabs_structure as $tab_data) {
            // Idempotencia: si el tab ya existe (de una instalación previa o
            // asociado por error a otro módulo), reutilizamos el id y
            // actualizamos el campo `module`. Evita duplicados y arregla el
            // caso "Page not found" cuando el tab quedó vinculado al módulo
            // equivocado.
            $existingId = (int) Tab::getIdFromClassName($tab_data['class_name']);
            $tab = $existingId ? new Tab($existingId) : new Tab();

            $tab->active = 1;
            $tab->class_name = $tab_data['class_name'];
            $tab->module = $module_name;

            $parentId = Tab::getIdFromClassName($tab_data['parent_class_name']);
            // Fallback para PS que no resuelve 'CONFIGURE' por alias
            if (!$parentId && $tab_data['parent_class_name'] === 'CONFIGURE') {
                $parentId = (int) Db::getInstance()->getValue(
                    'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = \'CONFIGURE\''
                );
            }
            $tab->id_parent = $parentId;

            if (isset($tab_data['icon'])) {
                $tab->icon = $tab_data['icon'];
            }

            foreach ($languages as $language) {
                $iso = strtolower($language['iso_code']);
                if (isset($tab_data['name'][$iso])) {
                    $tab->name[$language['id_lang']] = $tab_data['name'][$iso];
                } else {
                    $tab->name[$language['id_lang']] = $tab_data['name']['en'];
                }
            }

            $ok = $existingId ? $tab->update() : $tab->add();
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * Repara los tabs existentes asociándolos al módulo correcto.
     * Útil cuando los tabs quedaron vinculados a otro módulo (p.ej. tras
     * importar una BBDD de otro entorno) y producen "Page not found" en el BO.
     *
     * @param string $module_name Nombre del módulo que debe poseer los tabs
     *
     * @return bool
     */
    public static function repair($module_name)
    {
        foreach (self::$tabs_structure as $tab_data) {
            $id_tab = (int) Tab::getIdFromClassName($tab_data['class_name']);
            if (!$id_tab) {
                continue;
            }
            $tab = new Tab($id_tab);
            $tab->module = $module_name;
            $tab->active = 1;
            $tab->update();
        }

        return true;
    }

    public static function uninstall()
    {
        foreach (array_reverse(self::$tabs_structure) as $tab_data) {
            $id_tab = Tab::getIdFromClassName($tab_data['class_name']);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }
}
