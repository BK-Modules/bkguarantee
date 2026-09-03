/**
 * Visualización anidada de la etiqueta GARAN.
 *
 * INVARIANTE: el primer clic, el primer paso del ratón o el primer toque tienen que mostrar la
 * etiqueta completa; es la condición con la que el anexo II concede el plegado. Lo que el anexo no
 * exige es que siga abierta, así que el ratón la abre mientras está encima y la cierra al salir, y
 * el clic la fija hasta que se vuelve a pulsar —que es la única forma de abrirla en pantalla táctil
 * y con teclado, donde no existe «salir».
 */
(function () {
    'use strict';

    function bind(box) {
        var toggle = box.querySelector('.bkgaran__toggle');
        var full = box.querySelector('.bkgaran__full');
        if (!toggle || !full) {
            return;
        }

        var pinned = false;
        var hovering = false;
        var focused = false;

        function render() {
            var open = pinned || hovering || focused;
            full.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        // El ratón entra y sale de la caja entera, no del botón: la etiqueta desplegada forma parte
        // de la zona, o al bajar hacia ella se cerraría sola. Solo cuenta el ratón: en un dedo, el
        // navegador emite el mismo evento y la etiqueta se quedaría abierta sin poder cerrarla.
        function isMouse(event) {
            return !event.pointerType || event.pointerType === 'mouse';
        }

        var enter = window.PointerEvent ? 'pointerenter' : 'mouseenter';
        var leave = window.PointerEvent ? 'pointerleave' : 'mouseleave';

        box.addEventListener(enter, function (event) {
            if (isMouse(event)) {
                hovering = true;
                render();
            }
        });

        box.addEventListener(leave, function (event) {
            if (isMouse(event)) {
                hovering = false;
                render();
            }
        });

        // Solo el foco de teclado abre: el clic del ratón también enfoca, y entonces la etiqueta
        // se quedaría abierta con el puntero ya fuera.
        toggle.addEventListener('focus', function () {
            try {
                focused = toggle.matches(':focus-visible');
            } catch (e) {
                focused = true;
            }
            render();
        });

        box.addEventListener('focusout', function (event) {
            if (!box.contains(event.relatedTarget)) {
                focused = false;
                render();
            }
        });

        toggle.addEventListener('click', function () {
            pinned = !pinned;
            render();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.bkgaran--nested').forEach(bind);
    });
})();
