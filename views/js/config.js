/**
 * Pantalla de configuración: pestañas, campos que se pliegan y buscador de productos.
 *
 * Un ajuste que gobierna a otros los esconde hasta que hacen falta: enseñar la lista de grupos B2B
 * cuando la exclusión está apagada invita a rellenar algo que no se va a usar.
 *
 * Las pestañas son propias y no dependen de ningún plugin del back office: el menú es el mismo en
 * 1.7.5 y en 9.x, que son back offices distintos. La pestaña abierta viaja en el ancla, así que al
 * guardar se vuelve a la que se estaba tocando.
 */
(function ($) {
    'use strict';

    function toggleRows() {
        document.querySelectorAll('.bkguar-row[data-when]').forEach(function (row) {
            var name = row.getAttribute('data-when');
            var on = document.querySelector('input[name="' + name + '"]:checked');
            row.hidden = !(on && on.value === '1');
        });
        document.querySelectorAll('.bkguar-row[data-when-select]').forEach(function (row) {
            var sel = document.querySelector('select[name="' + row.getAttribute('data-when-select') + '"]');
            row.hidden = !(sel && sel.value === row.getAttribute('data-value'));
        });
    }

    function tabs() {
        var items = [].slice.call(document.querySelectorAll('.bkguar-nav__item'));
        var panes = [].slice.call(document.querySelectorAll('.bkguar-config .tab-pane'));
        if (!items.length) {
            return;
        }

        function show(id) {
            var found = false;
            items.forEach(function (item) {
                var on = item.getAttribute('href') === id;
                item.classList.toggle('is-active', on);
                item.setAttribute('aria-selected', on ? 'true' : 'false');
                found = found || on;
            });
            panes.forEach(function (pane) {
                pane.classList.toggle('active', '#' + pane.id === id);
            });

            // Guardar recarga la pantalla y el ancla no sobrevive al redirect, así que la pestaña
            // abierta se recuerda: se vuelve a la que se estaba tocando, no a la primera.
            if (found) {
                try {
                    window.sessionStorage.setItem('bkguarTab', id);
                } catch (e) {
                    // Sin almacenamiento la pestaña simplemente no se recuerda.
                }
            }

            return found;
        }

        // Los pasos de la puesta en marcha llevan al mismo sitio que el menú
        [].slice.call(document.querySelectorAll('.bkguar-config a[href^="#bkguar-tab-"]'))
            .filter(function (link) { return items.indexOf(link) === -1; })
            .forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    show(link.getAttribute('href'));
                    window.scrollTo({ top: document.querySelector('.bkguar-nav').getBoundingClientRect().top + window.scrollY - 20, behavior: 'smooth' });
                });
            });

        items.forEach(function (item) {
            item.addEventListener('click', function (event) {
                event.preventDefault();
                var id = item.getAttribute('href');
                show(id);
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', window.location.pathname + window.location.search + id);
                }
            });
        });

        var remembered = null;
        try {
            remembered = window.sessionStorage.getItem('bkguarTab');
        } catch (e) {
            remembered = null;
        }

        if (!(window.location.hash && show(window.location.hash))
            && !(remembered && show(remembered))
        ) {
            show(items[0].getAttribute('href'));
        }
    }

    function picker() {
        document.querySelectorAll('.bkguar-pick input[type=radio]').forEach(function (input) {
            input.addEventListener('change', function () {
                document.querySelectorAll('.bkguar-pick').forEach(function (p) {
                    p.classList.toggle('is-selected', p.contains(input) && input.checked);
                });
            });
        });
    }

    /**
     * El buscador guarda los identificadores en el campo oculto: lo que ve el comerciante son
     * fichas con el nombre del producto, pero lo que se guarda sigue siendo la misma lista de antes.
     */
    function finder(box) {
        var hidden = box.querySelector('input[type=hidden]');
        var input = box.querySelector('.bkguar-finder__input');
        var results = box.querySelector('.bkguar-finder__results');
        var chips = box.querySelector('.bkguar-finder__chips');
        var timer = null;

        function sync() {
            var ids = [].map.call(chips.querySelectorAll('.bkguar-chip'), function (c) {
                return c.getAttribute('data-id');
            });
            hidden.value = ids.join(',');
        }

        function add(id, name) {
            if (chips.querySelector('.bkguar-chip[data-id="' + id + '"]')) {
                return;
            }
            var chip = document.createElement('span');
            chip.className = 'bkguar-chip';
            chip.setAttribute('data-id', id);
            chip.innerHTML = '';
            chip.appendChild(document.createTextNode(name + ' '));
            var small = document.createElement('small');
            small.appendChild(document.createTextNode('#' + id));
            chip.appendChild(small);
            var x = document.createElement('a');
            x.href = '#';
            x.className = 'bkguar-chip__x';
            x.innerHTML = '&times;';
            chip.appendChild(x);
            chips.appendChild(chip);
            sync();
        }

        chips.addEventListener('click', function (e) {
            if (e.target.classList.contains('bkguar-chip__x')) {
                e.preventDefault();
                e.target.parentNode.remove();
                sync();
            }
        });

        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(timer);
            if (q.length < 2) {
                results.innerHTML = '';
                return;
            }
            timer = setTimeout(function () {
                $.getJSON(window.bkguarSearchUrl, { bkguar_q: q }, function (rows) {
                    results.innerHTML = '';
                    (rows || []).forEach(function (r) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'bkguar-finder__hit';
                        a.textContent = r.name + (r.reference ? ' · ' + r.reference : '') + ' · #' + r.id;
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            add(r.id, r.name);
                            results.innerHTML = '';
                            input.value = '';
                        });
                        results.appendChild(a);
                    });
                });
            }, 250);
        });
    }

    $(document).ready(function () {
        if ($.fn.chosen) {
            $('.bkguar-chosen').chosen({ width: '100%', search_contains: true });
        }
        tabs();
        picker();
        toggleRows();
        $('#bkguar-form').on('change', 'input[type=radio], select', toggleRows);
        document.querySelectorAll('.bkguar-finder').forEach(finder);
    });
})(jQuery);
