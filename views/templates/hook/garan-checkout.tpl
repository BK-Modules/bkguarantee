{**
 * Etiqueta GARAN en el último paso del checkout, inmediatamente antes del botón de compra.
 *
 * Una entrada por modelo con el nombre de los productos del carrito a los que corresponde, y debajo
 * la misma garan.tpl de la ficha —anidada si así está configurada—: aquí no se redibuja nada.
 *}
<section class="bkgaran-checkout bkgaran-checkout--{$bkgaran_co_placement|escape:'htmlall':'UTF-8'}" aria-labelledby="bkgaran-checkout-title-{$bkgaran_co_placement|escape:'htmlall':'UTF-8'}">
  <h2 class="bkgaran-checkout__title" id="bkgaran-checkout-title-{$bkgaran_co_placement|escape:'htmlall':'UTF-8'}">{$bkgaran_co_title|escape:'htmlall':'UTF-8'}</h2>
  <ul class="bkgaran-checkout__list">
    {foreach from=$bkgaran_co_labels item=bkgaran_label}
      <li class="bkgaran-checkout__item">
        <p class="bkgaran-checkout__products">{', '|implode:$bkgaran_label.products|escape:'htmlall':'UTF-8'}</p>
        {include file='module:bkguarantee/views/templates/hook/garan.tpl'
          bkgaran_years=$bkgaran_label.years
          bkgaran_brand=$bkgaran_label.brand
          bkgaran_model=$bkgaran_label.model
          bkgaran_uid=$bkgaran_label.uid}
      </li>
    {/foreach}
  </ul>
</section>
