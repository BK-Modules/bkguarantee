{**
 * Buscador de productos por AJAX: se escribe el nombre o la referencia y se van añadiendo fichas.
 * El campo que viaja al servidor sigue siendo una lista de identificadores separados por comas, así
 * que la configuración no cambia de forma por haber cambiado la manera de rellenarla.
 *}
<div class="bkguar-finder" data-target="{$name|escape:'htmlall':'UTF-8'}">
  <input type="hidden" name="{$name|escape:'htmlall':'UTF-8'}" value="{$value|escape:'htmlall':'UTF-8'}">
  <div class="input-group fixed-width-xxl">
    <span class="input-group-addon"><i class="icon-search"></i></span>
    <input type="text" class="form-control bkguar-finder__input" autocomplete="off"
           placeholder="{l s='Type a product name or reference' d='Modules.Bkguarantee.Admin'}">
  </div>
  <div class="bkguar-finder__results"></div>
  <div class="bkguar-finder__chips">
    {foreach from=$items item=it}
      <span class="bkguar-chip" data-id="{$it.id|intval}">
        {$it.name|escape:'htmlall':'UTF-8'} <small>#{$it.id|intval}</small>
        <a href="#" class="bkguar-chip__x" title="{l s='Remove' d='Modules.Bkguarantee.Admin'}">&times;</a>
      </span>
    {/foreach}
  </div>
</div>
