{**
 * Actualizaciones de software y reparación en la ficha de producto.
 *
 * Solo lo que el fabricante ha dado: una fila por dato, rótulo y valor. Los rótulos llegan ya
 * traducidos y el valor ya escapado —con sus URL convertidas en enlace—, igual que en el correo de
 * confirmación, que sale de la misma BkGuaranteeDurability: lo que se enseña antes de comprar es lo
 * que después confirma el pedido.
 *}
<section class="bkdura" aria-labelledby="bkdura-title">
  <h2 class="bkdura__title" id="bkdura-title">{$bkdura_title|escape:'htmlall':'UTF-8'}</h2>
  <dl class="bkdura__list">
    {foreach from=$bkdura_rows item=bkdura_row}
      <div class="bkdura__row">
        <dt class="bkdura__label">{$bkdura_row.label|escape:'htmlall':'UTF-8'}</dt>
        <dd class="bkdura__value">{$bkdura_row.html nofilter}</dd>
      </div>
    {/foreach}
  </dl>
  <p class="bkdura__note">{$bkdura_note|escape:'htmlall':'UTF-8'}</p>
</section>
