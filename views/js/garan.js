/**
 * Visualización anidada de la etiqueta GARAN: al primer clic, hover o toque tiene que aparecer
 * completa, que es la condición con la que el anexo II la permite. Por eso se abre y no se vuelve
 * a cerrar sola.
 */
(function () {
    'use strict';

    function open(box) {
        var full = box.querySelector('.bkgaran__full');
        var toggle = box.querySelector('.bkgaran__toggle');
        if (!full || !full.hidden) {
            return;
        }
        full.hidden = false;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.bkgaran--nested').forEach(function (box) {
            var toggle = box.querySelector('.bkgaran__toggle');
            if (!toggle) {
                return;
            }
            ['click', 'mouseenter', 'touchstart'].forEach(function (evt) {
                toggle.addEventListener(evt, function () { open(box); }, { passive: true });
            });
        });
    });
})();
