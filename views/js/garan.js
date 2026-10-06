/**
 * Visualización anidada de la etiqueta GARAN.
 *
 * INVARIANTE: el primer clic, el primer paso del ratón o el primer toque tienen que mostrar la
 * etiqueta completa; es la condición con la que el anexo II concede el plegado. Lo que el anexo no
 * exige es que siga abierta, así que el ratón la abre mientras está encima y la cierra al salir, y
 * el clic la fija hasta que se vuelve a pulsar —que es la única forma de abrirla en pantalla táctil
 * y con teclado, donde no existe «salir».
 *
 * La etiqueta desplegada flota: se coloca en `fixed` junto al botón para que abrirla no empuje la
 * página, y para que ninguna columna con `overflow` del tema la recorte.
 *
 * Los eventos se escuchan en el documento, en fase de captura, y no en cada etiqueta: PrestaShop
 * repinta trozos de página sin recargarla —el bloque de compra de la ficha al cambiar de
 * combinación, el checkout de los módulos de pago en una página— y una etiqueta pintada después
 * tiene que abrirse igual que la primera. La captura además llega aunque un contenedor del tema
 * corte la propagación. El estado de cada etiqueta vive en su propio elemento: una repintada nace
 * cerrada.
 */
(function () {
    'use strict';

    var BOX = '.bkgaran--nested';
    var MARGIN = 8;

    function isBox(node) {
        return !!(node && node.nodeType === 1 && node.matches(BOX));
    }

    function boxOf(node) {
        return node && node.nodeType === 1 ? node.closest(BOX) : null;
    }

    function stateOf(box) {
        if (!box.bkgaranState) {
            box.bkgaranState = { pinned: false, hovering: false, focused: false };
        }

        return box.bkgaranState;
    }

    function place(toggle, full) {
        var anchor = toggle.getBoundingClientRect();
        var size = full.getBoundingClientRect();
        var below = window.innerHeight - anchor.bottom - MARGIN;
        var above = anchor.top - MARGIN;
        // Cabe debajo salvo que no quepa y arriba sí: entonces se vuelca hacia arriba.
        var flip = size.height > below && above > below;
        var top = flip ? anchor.top - size.height - MARGIN : anchor.bottom + MARGIN;

        // Si no cabe a ningún lado del botón, se encaja dentro de la ventana aunque lo tape: la
        // etiqueta tiene que verse entera, y el CSS ya limita su alto al de la ventana.
        top = Math.max(MARGIN, Math.min(top, window.innerHeight - size.height - MARGIN));

        full.classList.toggle('is-above', flip);
        full.style.top = top + 'px';
        full.style.left = Math.max(
            MARGIN,
            Math.min(anchor.left, window.innerWidth - size.width - MARGIN)
        ) + 'px';
    }

    function render(box) {
        var toggle = box.querySelector('.bkgaran__toggle');
        var full = box.querySelector('.bkgaran__full');
        if (!toggle || !full) {
            return;
        }

        var state = stateOf(box);
        var open = state.pinned || state.hovering || state.focused;
        full.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            place(toggle, full);
        }
    }

    function listen(type, handler) {
        document.addEventListener(type, handler, true);
    }

    // El ratón entra y sale de la caja entera, no del botón: la etiqueta desplegada forma parte
    // de la zona, o al bajar hacia ella se cerraría sola. Solo cuenta el ratón: en un dedo, el
    // navegador emite el mismo evento y la etiqueta se quedaría abierta sin poder cerrarla.
    function isMouse(event) {
        return !event.pointerType || event.pointerType === 'mouse';
    }

    listen(window.PointerEvent ? 'pointerenter' : 'mouseenter', function (event) {
        if (isBox(event.target) && isMouse(event)) {
            stateOf(event.target).hovering = true;
            render(event.target);
        }
    });

    // Con ratón, salir de la caja cierra siempre, también tras un clic: el clic fija solo donde
    // no existe «salir» (dedo y teclado). Así el puntero manda y la etiqueta nunca se queda
    // colgada sobre la página.
    listen(window.PointerEvent ? 'pointerleave' : 'mouseleave', function (event) {
        if (isBox(event.target) && isMouse(event)) {
            var state = stateOf(event.target);
            state.hovering = false;
            state.pinned = false;
            render(event.target);
        }
    });

    // Solo el foco de teclado abre: el clic del ratón también enfoca, y entonces la etiqueta
    // se quedaría abierta con el puntero ya fuera.
    listen('focus', function (event) {
        var box = boxOf(event.target);
        if (!box || !event.target.classList.contains('bkgaran__toggle')) {
            return;
        }
        var focused;
        try {
            focused = event.target.matches(':focus-visible');
        } catch (e) {
            focused = true;
        }
        stateOf(box).focused = focused;
        render(box);
    });

    listen('focusout', function (event) {
        var box = boxOf(event.target);
        if (box && !box.contains(event.relatedTarget)) {
            stateOf(box).focused = false;
            render(box);
        }
    });

    // El clic fija o suelta. Al soltar, la etiqueta se cierra en el acto aunque el ratón siga
    // encima: el hover vuelve a abrirla en la siguiente entrada, no en la misma.
    listen('click', function (event) {
        var toggle = event.target.nodeType === 1 ? event.target.closest('.bkgaran__toggle') : null;
        var box = boxOf(toggle);
        if (!box) {
            return;
        }
        var state = stateOf(box);
        state.pinned = !state.pinned;
        if (!state.pinned) {
            state.hovering = false;
            state.focused = false;
        }
        render(box);
    });

    // Fijada, se suelta también con Escape y con un clic fuera de la caja: la capa flota sobre
    // la página y el cliente tiene que poder quitarla sin volver a encontrar el botón.
    function unpinAll(except) {
        [].forEach.call(document.querySelectorAll(BOX), function (box) {
            var state = stateOf(box);
            if (!state.pinned || (except && box.contains(except))) {
                return;
            }
            state.pinned = false;
            state.hovering = false;
            state.focused = false;
            render(box);
        });
    }

    listen('keydown', function (event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
            unpinAll(null);
        }
    });

    listen(window.PointerEvent ? 'pointerdown' : 'mousedown', function (event) {
        unpinAll(event.target);
    });

    // Mientras está abierta sigue al botón: la página se puede seguir moviendo debajo.
    ['scroll', 'resize'].forEach(function (type) {
        window.addEventListener(type, function () {
            [].forEach.call(document.querySelectorAll(BOX), function (box) {
                var toggle = box.querySelector('.bkgaran__toggle');
                var full = box.querySelector('.bkgaran__full');
                if (toggle && full && !full.hidden) {
                    place(toggle, full);
                }
            });
        }, { passive: true });
    });
})();
