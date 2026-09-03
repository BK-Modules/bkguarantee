{**
 * Etiqueta armonizada GARAN.
 *
 * El arte es el fichero oficial del anexo II con los tres campos editables vaciados; encima va un
 * SVG con esos tres valores en las coordenadas exactas del original. Nada más se toca: título,
 * recordatorio de garantía legal, calendario, QR y las traducciones a las 24 lenguas son píxeles
 * del Diario Oficial.
 *
 * El SVG usa el mismo sistema de coordenadas que el arte (741 x 780), así que la etiqueta escala
 * entera sin que haya que calcular ningún tamaño de letra.
 *
 * La visualización anidada es la figura del propio anexo II (1030 x 162), donde el único campo
 * editable es «XX»: los años. El marco que la rodea es de la tienda, la figura no se toca.
 *}
<div class="bkgaran{if $bkgaran_nested} bkgaran--nested{/if}" style="--bkgaran-w:{$bkgaran_width|intval}px;--bkgaran-badge-w:{$bkgaran_badge_width|intval}px">
  {if $bkgaran_nested}
    <button type="button" class="bkgaran__toggle" aria-expanded="false" aria-controls="bkgaran-full-{$bkgaran_uid|escape:'htmlall':'UTF-8'}">
      <span class="bkgaran__badge">
        <img class="bkgaran__badge-art" src="{$bkgaran_nested_art|escape:'htmlall':'UTF-8'}" alt="{$bkgaran_alt|escape:'htmlall':'UTF-8'}" loading="lazy">
        <svg class="bkgaran__badge-fields" viewBox="0 0 1030 162" role="presentation" focusable="false">
          <text class="bkgaran__badge-years" x="36" y="130">{$bkgaran_years|intval}</text>
        </svg>
      </span>
      <span class="bkgaran__hint">
        <span class="bkgaran__hint-text">{$bkgaran_toggle|escape:'htmlall':'UTF-8'}</span>
        <span class="bkgaran__hint-more">{$bkgaran_more|escape:'htmlall':'UTF-8'}</span>
      </span>
    </button>
  {/if}

  <figure class="bkgaran__full" id="bkgaran-full-{$bkgaran_uid|escape:'htmlall':'UTF-8'}"{if $bkgaran_nested} hidden{/if}>
    <img class="bkgaran__art" src="{$bkgaran_art|escape:'htmlall':'UTF-8'}" alt="{$bkgaran_alt|escape:'htmlall':'UTF-8'}" loading="lazy">
    <svg class="bkgaran__fields" viewBox="0 0 741 780" role="presentation" focusable="false">
      <text class="bkgaran__brand" x="20" y="207">{$bkgaran_brand|escape:'htmlall':'UTF-8'}</text>
      <text class="bkgaran__model" x="727" y="207" text-anchor="end">{$bkgaran_model|escape:'htmlall':'UTF-8'}</text>
      <text class="bkgaran__years" x="22" y="414">{$bkgaran_years|intval}</text>
    </svg>
  </figure>
</div>
