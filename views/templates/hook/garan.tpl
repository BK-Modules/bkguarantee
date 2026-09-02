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
 *}
<div class="bkgaran{if $bkgaran_nested} bkgaran--nested{/if}" style="--bkgaran-w:{$bkgaran_width|intval}px">
  {if $bkgaran_nested}
    <button type="button" class="bkgaran__toggle" aria-expanded="false" aria-controls="bkgaran-full">
      <span class="bkgaran__toggle-mark">GARAN</span>
      <span class="bkgaran__toggle-years">{$bkgaran_years|intval}</span>
      <span class="bkgaran__toggle-text">{$bkgaran_toggle|escape:'htmlall':'UTF-8'}</span>
    </button>
  {/if}

  <figure class="bkgaran__full" id="bkgaran-full"{if $bkgaran_nested} hidden{/if}>
    <img class="bkgaran__art" src="{$bkgaran_art|escape:'htmlall':'UTF-8'}" alt="{$bkgaran_alt|escape:'htmlall':'UTF-8'}" loading="lazy">
    <svg class="bkgaran__fields" viewBox="0 0 741 780" role="presentation" focusable="false">
      <text class="bkgaran__brand" x="20" y="207">{$bkgaran_brand|escape:'htmlall':'UTF-8'}</text>
      <text class="bkgaran__model" x="727" y="207" text-anchor="end">{$bkgaran_model|escape:'htmlall':'UTF-8'}</text>
      <text class="bkgaran__years" x="22" y="414">{$bkgaran_years|intval}</text>
    </svg>
  </figure>
</div>
