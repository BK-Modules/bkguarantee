<?php
/**
 * Importación y exportación de las reglas de datos del fabricante en CSV.
 *
 * El fichero es el mismo en los dos sentidos: lo que exporta se vuelve a importar sin tocarlo, que
 * es lo que convierte al CSV en la herramienta de trabajo de un catálogo grande —se saca, se edita
 * en la hoja de cálculo y se devuelve.
 *
 * La regla se identifica por su nombre dentro de su tienda: importar dos veces el mismo fichero
 * actualiza las mismas reglas en vez de duplicarlas.
 *
 * Columnas de actualizaciones y reparación: `updates` (vacío | none | fecha | años), `repair`
 * (vacío | none | índice A–E) y, por cada idioma, los cinco textos de repuestos y reparación
 * (`parts_availability_de`…). Vacío en `repair` con algún texto en la fila es «repuestos y
 * reparación». **Una columna que el fichero no trae no se toca**: un fichero de la versión anterior,
 * con las nueve columnas de la etiqueta GARAN, sigue importando sin borrar nada de lo nuevo.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeRuleCsv
{
    /** Columnas de la regla, en orden; detrás van los textos de reparación de cada idioma */
    const COLUMNS = ['name', 'filter_type', 'filter_values', 'years', 'brand', 'model', 'priority', 'active', 'id_shop', 'updates', 'repair'];

    /** Las que tiene que traer cualquier fichero */
    const REQUIRED = ['name', 'filter_type', 'filter_values', 'years'];

    /** Separador con el que se escribe; al leer se detecta el que traiga el fichero */
    const DELIMITER = ';';

    /** Límite de filas de una importación: por encima es un catálogo, no una tabla de garantías */
    const MAX_ROWS = 2000;

    /**
     * @return array Todas las columnas, con los textos de cada idioma de la tienda
     */
    public static function columns()
    {
        $columns = self::COLUMNS;
        foreach (Language::getLanguages(false) as $language) {
            foreach (BkGuaranteeRule::PARTS_FIELDS as $field) {
                $columns[] = $field . '_' . Tools::strtolower($language['iso_code']);
            }
        }

        return $columns;
    }

    /**
     * Todas las reglas de la tabla, con sus textos.
     *
     * @return string
     */
    public static function exportAll()
    {
        $rules = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule` ORDER BY `id_guarantee_rule`'
        );

        $texts = [];
        foreach ((array) Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule_lang`') as $row) {
            foreach (BkGuaranteeRule::PARTS_FIELDS as $field) {
                $texts[(int) $row['id_guarantee_rule']][$field][(int) $row['id_lang']] = $row[$field];
            }
        }

        return self::export((array) $rules, $texts);
    }

    /**
     * @param array $rules Filas tal como salen de la tabla
     * @param array $texts id regla => campo => id_lang => texto
     *
     * @return string Contenido del CSV, con BOM para que Excel lo abra en UTF-8
     */
    public static function export(array $rules, array $texts)
    {
        $languages = Language::getLanguages(false);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, self::columns(), self::DELIMITER);

        foreach ($rules as $rule) {
            $line = [];
            foreach (self::COLUMNS as $column) {
                if ($column === 'updates') {
                    $line[] = self::updatesCell($rule);
                } elseif ($column === 'repair') {
                    $line[] = self::repairCell($rule);
                } else {
                    $line[] = isset($rule[$column]) ? $rule[$column] : '';
                }
            }

            $id = isset($rule['id_guarantee_rule']) ? (int) $rule['id_guarantee_rule'] : 0;
            foreach ($languages as $language) {
                foreach (BkGuaranteeRule::PARTS_FIELDS as $field) {
                    $line[] = isset($texts[$id][$field][(int) $language['id_lang']]) ? $texts[$id][$field][(int) $language['id_lang']] : '';
                }
            }
            fputcsv($out, $line, self::DELIMITER);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Fichero de ejemplo: las columnas y una fila de cada caso, para que quien abra la plantilla vea
     * qué se espera en cada celda sin leer nada. Los textos de ejemplo van en el idioma por defecto.
     *
     * @return string
     */
    public static function template()
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $base = ['brand' => '', 'model' => '', 'priority' => 0, 'active' => 1, 'id_shop' => 0, 'updates_mode' => '', 'updates_until' => '', 'updates_years' => 0, 'repair_mode' => '', 'repair_score' => ''];

        return self::export([
            ['id_guarantee_rule' => 1, 'name' => 'Bosch power tools', 'filter_type' => BkGuaranteeRule::FILTER_MANUFACTURER, 'filter_values' => '1,2', 'years' => 5, 'updates_mode' => BkGuaranteeRule::UPDATES_NONE] + $base,
            ['id_guarantee_rule' => 2, 'name' => 'Outdoor furniture', 'filter_type' => BkGuaranteeRule::FILTER_CATEGORY, 'filter_values' => '11', 'years' => 8, 'priority' => 10] + $base,
            ['id_guarantee_rule' => 3, 'name' => 'Acme phones', 'filter_type' => BkGuaranteeRule::FILTER_MANUFACTURER, 'filter_values' => '3', 'years' => 0, 'repair_mode' => BkGuaranteeRule::REPAIR_PARTS] + $base,
            ['id_guarantee_rule' => 4, 'name' => 'Model X9', 'filter_type' => BkGuaranteeRule::FILTER_PRODUCTS, 'filter_values' => '3,7,9', 'years' => 10, 'brand' => 'Acme', 'model' => 'X9', 'priority' => 20, 'updates_mode' => BkGuaranteeRule::UPDATES_DATE, 'updates_until' => '2031-12-31', 'repair_mode' => BkGuaranteeRule::REPAIR_SCORE, 'repair_score' => 'B'] + $base,
        ], [
            3 => [
                'parts_availability' => [$idLang => 'Available for at least 7 years after the last unit is placed on the market'],
                'parts_cost' => [$idLang => 'Battery 39 EUR, screen 129 EUR'],
                'parts_order' => [$idLang => 'https://acme.example/spare-parts'],
                'repair_manuals' => [$idLang => 'https://acme.example/repair'],
                'repair_restrictions' => [$idLang => 'Only original batteries can be fitted'],
            ],
        ]);
    }

    /**
     * Lee el fichero y devuelve lo que se haría con él, sin tocar nada: esa lista es la vista
     * previa, y es la misma que después ejecuta apply().
     *
     * @param string $path
     *
     * @return array rows => [['data' => [...], 'action' => new|update|skip, 'errors' => []]], fatal => string|null,
     *               detail => string con lo que el mensaje no puede llevar traducido
     */
    public static function parse($path)
    {
        if (!is_readable($path)) {
            return ['rows' => [], 'fatal' => 'The file could not be read.', 'detail' => ''];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['rows' => [], 'fatal' => 'The file could not be read.', 'detail' => ''];
        }

        $header = fgets($handle);
        if ($header === false) {
            fclose($handle);

            return ['rows' => [], 'fatal' => 'The file is empty.', 'detail' => ''];
        }

        // El separador lo pone la hoja de cálculo que exportó el fichero, no nosotros.
        $delimiter = substr_count($header, ';') >= substr_count($header, ',') ? ';' : ',';
        $header = str_getcsv(self::clean($header), $delimiter);
        $header = array_map(function ($column) {
            return Tools::strtolower(trim(self::clean($column)));
        }, $header);

        $missing = array_diff(self::REQUIRED, $header);
        if (!empty($missing)) {
            fclose($handle);

            return ['rows' => [], 'fatal' => 'The file is missing columns.', 'detail' => implode(', ', $missing)];
        }

        $languages = [];
        foreach (Language::getLanguages(false) as $language) {
            $languages[Tools::strtolower($language['iso_code'])] = (int) $language['id_lang'];
        }

        $existing = self::existingByName();
        $rows = [];
        $seen = [];

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
            if (count($line) === 1 && trim((string) $line[0]) === '') {
                continue;
            }

            $raw = array_combine(
                $header,
                array_pad(array_slice($line, 0, count($header)), count($header), '')
            );
            $data = self::normalise($raw, $languages);

            $key = Tools::strtolower($data['name']) . '#' . $data['id_shop'];
            $current = isset($existing[$key]) ? $existing[$key] : null;
            $errors = self::validate($data, $raw, $current);

            if (isset($seen[$key])) {
                $errors[] = 'The file repeats this rule name for the same shop.';
            }
            $seen[$key] = true;

            $rows[] = [
                'data' => $data,
                'id' => $current ? (int) $current['id_guarantee_rule'] : 0,
                'action' => !empty($errors) ? 'skip' : ($current ? 'update' : 'new'),
                'errors' => $errors,
            ];
        }

        fclose($handle);

        return ['rows' => $rows, 'fatal' => null, 'detail' => ''];
    }

    /**
     * @param array $rows Filas de parse()
     *
     * @return array created, updated, skipped
     */
    public static function apply(array $rows)
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if ($row['action'] === 'skip') {
                ++$skipped;
                continue;
            }

            $rule = new BkGuaranteeRule($row['id'] ? (int) $row['id'] : null);
            foreach ($row['data'] as $field => $value) {
                if ($field !== 'texts') {
                    $rule->$field = $value;
                }
            }
            if (isset($row['data']['texts'])) {
                foreach ($row['data']['texts'] as $field => $byLang) {
                    $merged = is_array($rule->$field) ? $rule->$field : [];
                    $rule->$field = array_replace($merged, $byLang);
                }
            }

            if ($rule->save()) {
                $row['id'] ? ++$updated : ++$created;
            } else {
                ++$skipped;
            }
        }

        BkGuaranteeLogger::confirmation(sprintf(
            'Importación CSV de reglas: %d creadas, %d actualizadas, %d descartadas',
            $created,
            $updated,
            $skipped
        ));

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * @param array $rule
     *
     * @return string
     */
    private static function updatesCell(array $rule)
    {
        $mode = isset($rule['updates_mode']) ? (string) $rule['updates_mode'] : '';
        if ($mode === BkGuaranteeRule::UPDATES_DATE) {
            return (string) $rule['updates_until'];
        }
        if ($mode === BkGuaranteeRule::UPDATES_YEARS) {
            return (string) (int) $rule['updates_years'];
        }

        return $mode === BkGuaranteeRule::UPDATES_NONE ? 'none' : '';
    }

    /**
     * Repuestos y reparación no tiene palabra propia: es la celda vacía con algún texto en la fila.
     *
     * @param array $rule
     *
     * @return string
     */
    private static function repairCell(array $rule)
    {
        $mode = isset($rule['repair_mode']) ? (string) $rule['repair_mode'] : '';
        if ($mode === BkGuaranteeRule::REPAIR_SCORE) {
            return (string) $rule['repair_score'];
        }

        return $mode === BkGuaranteeRule::REPAIR_NONE ? 'none' : '';
    }

    /**
     * @param array      $data    Fila normalizada
     * @param array      $raw     Fila tal como viene, para saber qué columnas trae
     * @param array|null $current Regla que la fila actualizaría
     *
     * @return array Mensajes sin traducir; los traduce el controlador
     */
    private static function validate(array $data, array $raw, $current)
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'The rule needs a name.';
        }
        if (!array_key_exists($data['filter_type'], BkGuaranteeRule::filterTypes())) {
            $errors[] = 'Unknown filter type.';
        }
        if ($data['filter_values'] === '') {
            $errors[] = 'The rule has no targets.';
        }
        if ($data['id_shop'] && !Validate::isLoadedObject(new Shop((int) $data['id_shop']))) {
            $errors[] = 'Unknown shop.';
        }

        foreach (isset($data['texts']) ? $data['texts'] : [] as $byLang) {
            foreach ($byLang as $text) {
                if (Tools::strlen($text) > BkGuaranteeRule::TEXT_MAX) {
                    $errors[] = 'A spare parts or repair text is longer than 512 characters.';
                    break 2;
                }
            }
        }

        // Lo que la fila no trae se juzga con lo que la regla ya tiene: es lo que quedará guardado.
        $effective = ['years' => $data['years'], 'has_texts' => true];
        foreach (['updates_mode', 'updates_until', 'updates_years', 'repair_mode', 'repair_score'] as $field) {
            $effective[$field] = array_key_exists($field, $data)
                ? $data[$field]
                : ($current ? $current[$field] : '');
        }

        return array_merge($errors, BkGuaranteeRule::blockErrors($effective));
    }

    /**
     * @param array $raw       Celdas por columna
     * @param array $languages iso => id_lang
     *
     * @return array Fila lista para el ObjectModel, con los textos aparte en `texts`
     */
    private static function normalise(array $raw, array $languages)
    {
        $get = function ($key, $default = '') use ($raw) {
            return isset($raw[$key]) ? trim(self::clean($raw[$key])) : $default;
        };

        $ids = array_filter(array_map('intval', preg_split('/[^0-9]+/', $get('filter_values'))));

        $data = [
            'name' => Tools::substr($get('name'), 0, 128),
            'filter_type' => Tools::strtolower($get('filter_type')),
            'filter_values' => implode(',', array_unique($ids)),
            'years' => (int) $get('years'),
            'brand' => Tools::substr($get('brand'), 0, 128),
            'model' => Tools::substr($get('model'), 0, 128),
            'priority' => (int) $get('priority', '0'),
            'active' => in_array(Tools::strtolower($get('active', '1')), ['0', 'no', 'false', ''], true) ? 0 : 1,
            'id_shop' => (int) $get('id_shop', '0'),
        ];

        $hasTexts = false;
        foreach ($raw as $column => $value) {
            if (preg_match('/^(' . implode('|', BkGuaranteeRule::PARTS_FIELDS) . ')_([a-z]{2,3})$/', $column, $m)
                && isset($languages[$m[2]])
            ) {
                $text = trim(self::clean($value));
                $data['texts'][$m[1]][$languages[$m[2]]] = $text;
                $hasTexts = $hasTexts || $text !== '';
            }
        }

        if (array_key_exists('updates', $raw)) {
            $cell = $get('updates');
            $data['updates_mode'] = '';
            $data['updates_until'] = '';
            $data['updates_years'] = 0;
            if (Tools::strtolower($cell) === 'none') {
                $data['updates_mode'] = BkGuaranteeRule::UPDATES_NONE;
            } elseif (preg_match('/^\d{1,2}$/', $cell)) {
                $data['updates_mode'] = BkGuaranteeRule::UPDATES_YEARS;
                $data['updates_years'] = (int) $cell;
            } elseif ($cell !== '') {
                // Cualquier otra cosa tiene que ser una fecha; si no lo es, la validación lo dice.
                $data['updates_mode'] = BkGuaranteeRule::UPDATES_DATE;
                $data['updates_until'] = (string) BkGuaranteeRule::normaliseDate($cell);
            }
        }

        if (array_key_exists('repair', $raw)) {
            $cell = Tools::strtoupper($get('repair'));
            $data['repair_mode'] = $hasTexts ? BkGuaranteeRule::REPAIR_PARTS : '';
            $data['repair_score'] = '';
            if ($cell === 'NONE') {
                $data['repair_mode'] = BkGuaranteeRule::REPAIR_NONE;
            } elseif ($cell !== '') {
                $data['repair_mode'] = BkGuaranteeRule::REPAIR_SCORE;
                $data['repair_score'] = Tools::substr($cell, 0, 8);
            }
        }

        return $data;
    }

    /**
     * @return array clave nombre#tienda => fila de la regla
     */
    private static function existingByName()
    {
        $map = [];
        $rows = Db::getInstance()->executeS(
            'SELECT `id_guarantee_rule`, `name`, `id_shop`, `updates_mode`, `updates_until`, `updates_years`,
                    `repair_mode`, `repair_score`
             FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule`'
        );

        foreach ((array) $rows as $row) {
            $map[Tools::strtolower($row['name']) . '#' . (int) $row['id_shop']] = $row;
        }

        return $map;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private static function clean($value)
    {
        return str_replace("\xEF\xBB\xBF", '', (string) $value);
    }
}
