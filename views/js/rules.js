/**
 * Formulario de reglas: solo se enseña lo que corresponde a lo elegido.
 *
 * - El campo de destino del filtro elegido: los tres comparten columna en la tabla, así que
 *   enseñar los tres a la vez invita a rellenar uno que no se va a guardar.
 * - En actualizaciones y reparación, los campos del modo marcado. Cada fila lleva la clase
 *   `bkguar-when--<campo>--<valor>` y solo se ve con ese valor marcado.
 */
(function () {
    'use strict';

    function syncTargets() {
        var select = document.querySelector('select[name="filter_type"]');
        if (!select) {
            return;
        }

        ['category', 'manufacturer', 'products'].forEach(function (type) {
            document.querySelectorAll('.bkguar-row--' + type).forEach(function (row) {
                row.hidden = select.value !== type;
            });
        });
    }

    function syncModes() {
        document.querySelectorAll('[class*="bkguar-when--"]').forEach(function (row) {
            var match = row.className.match(/bkguar-when--([a-z_]+)--([a-z]+)/);
            if (!match) {
                return;
            }
            var checked = document.querySelector('input[name="' + match[1] + '"]:checked');
            row.hidden = !(checked && checked.value === match[2]);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var select = document.querySelector('select[name="filter_type"]');
        if (select) {
            select.addEventListener('change', syncTargets);
            syncTargets();
        }

        document.querySelectorAll('input[name="updates_mode"], input[name="repair_mode"]').forEach(function (input) {
            input.addEventListener('change', syncModes);
        });
        syncModes();
    });
})();
