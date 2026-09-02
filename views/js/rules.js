/**
 * Formulario de reglas: solo se enseña el campo de destino que corresponde al filtro elegido.
 * Los tres comparten columna en la tabla, así que enseñar los tres a la vez invita a rellenar
 * uno que no se va a guardar.
 */
(function () {
    'use strict';

    function sync() {
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

    document.addEventListener('DOMContentLoaded', function () {
        var select = document.querySelector('select[name="filter_type"]');
        if (!select) {
            return;
        }
        select.addEventListener('change', sync);
        sync();
    });
})();
