<?php
/**
 * Importación y exportación de reglas GARAN en CSV.
 *
 * El fichero es el mismo en los dos sentidos: lo que exporta se vuelve a importar sin tocarlo, que
 * es lo que convierte al CSV en la herramienta de trabajo de un catálogo grande —se saca, se edita
 * en la hoja de cálculo y se devuelve.
 *
 * La regla se identifica por su nombre dentro de su tienda: importar dos veces el mismo fichero
 * actualiza las mismas reglas en vez de duplicarlas.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeRuleCsv
{
    /** Columnas del fichero, en orden */
    const COLUMNS = ['name', 'filter_type', 'filter_values', 'years', 'brand', 'model', 'priority', 'active', 'id_shop'];

    /** Separador con el que se escribe; al leer se detecta el que traiga el fichero */
    const DELIMITER = ';';

    /** Límite de filas de una importación: por encima es un catálogo, no una tabla de garantías */
    const MAX_ROWS = 2000;

    /**
     * @param array $rules Filas tal como salen de la tabla
     *
     * @return string Contenido del CSV, con BOM para que Excel lo abra en UTF-8
     */
    public static function export(array $rules)
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, self::COLUMNS, self::DELIMITER);

        foreach ($rules as $rule) {
            $line = [];
            foreach (self::COLUMNS as $column) {
                $line[] = isset($rule[$column]) ? $rule[$column] : '';
            }
            fputcsv($out, $line, self::DELIMITER);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Fichero de ejemplo: las columnas y una fila de cada tipo de filtro, para que quien abra la
     * plantilla vea qué se espera en cada celda sin leer nada.
     *
     * @return string
     */
    public static function template()
    {
        return self::export([
            ['name' => 'Bosch power tools', 'filter_type' => BkGuaranteeRule::FILTER_MANUFACTURER, 'filter_values' => '1,2', 'years' => 5, 'brand' => '', 'model' => '', 'priority' => 0, 'active' => 1, 'id_shop' => 0],
            ['name' => 'Outdoor furniture', 'filter_type' => BkGuaranteeRule::FILTER_CATEGORY, 'filter_values' => '11', 'years' => 8, 'brand' => '', 'model' => '', 'priority' => 10, 'active' => 1, 'id_shop' => 0],
            ['name' => 'Model X9', 'filter_type' => BkGuaranteeRule::FILTER_PRODUCTS, 'filter_values' => '3,7,9', 'years' => 10, 'brand' => 'Acme', 'model' => 'X9', 'priority' => 20, 'active' => 1, 'id_shop' => 0],
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

        $missing = array_diff(['name', 'filter_type', 'filter_values', 'years'], $header);
        if (!empty($missing)) {
            fclose($handle);

            return ['rows' => [], 'fatal' => 'The file is missing columns.', 'detail' => implode(', ', $missing)];
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

            $data = self::normalise(array_combine(
                $header,
                array_pad(array_slice($line, 0, count($header)), count($header), '')
            ));
            $errors = self::validate($data);

            $key = Tools::strtolower($data['name']) . '#' . $data['id_shop'];
            if (isset($seen[$key])) {
                $errors[] = 'The file repeats this rule name for the same shop.';
            }
            $seen[$key] = true;

            $rows[] = [
                'data' => $data,
                'id' => isset($existing[$key]) ? (int) $existing[$key] : 0,
                'action' => !empty($errors) ? 'skip' : (isset($existing[$key]) ? 'update' : 'new'),
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
                $rule->$field = $value;
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
     * @param array $data
     *
     * @return array Mensajes sin traducir; los traduce el controlador
     */
    private static function validate(array $data)
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
        if ((int) $data['years'] < BkGuaranteeRule::MIN_YEARS) {
            $errors[] = 'A durability guarantee earns a label only above two years.';
        }
        if ($data['id_shop'] && !Validate::isLoadedObject(new Shop((int) $data['id_shop']))) {
            $errors[] = 'Unknown shop.';
        }

        return $errors;
    }

    /**
     * @param array $raw
     *
     * @return array Fila lista para el ObjectModel
     */
    private static function normalise(array $raw)
    {
        $get = function ($key, $default = '') use ($raw) {
            return isset($raw[$key]) ? trim(self::clean($raw[$key])) : $default;
        };

        $ids = array_filter(array_map('intval', preg_split('/[^0-9]+/', $get('filter_values'))));

        return [
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
    }

    /**
     * @return array clave nombre#tienda => id
     */
    private static function existingByName()
    {
        $map = [];
        $rows = Db::getInstance()->executeS(
            'SELECT `id_guarantee_rule`, `name`, `id_shop` FROM `' . _DB_PREFIX_ . 'bk_guarantee_rule`'
        );

        foreach ((array) $rows as $row) {
            $map[Tools::strtolower($row['name']) . '#' . (int) $row['id_shop']] = (int) $row['id_guarantee_rule'];
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
