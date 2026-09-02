{**
 * Aviso armonizado sobre la garantía legal de conformidad.
 *
 * Se sirve como imagen entera y en color, sin recortes, sin plegar y sin superponerle nada:
 * el anexo I del Reglamento (UE) 2025/1960 prohíbe editar cualquiera de sus elementos, y la
 * visualización anidada solo la admite para la etiqueta GARAN.
 *}
<div class="bkguar bkguar--{$bkguar_place|escape:'htmlall':'UTF-8'}">
  <img class="bkguar__notice"
       src="{$bkguar_url|escape:'htmlall':'UTF-8'}"
       alt="{$bkguar_alt|escape:'htmlall':'UTF-8'}"
       style="max-width:{$bkguar_width|intval}px"
       loading="lazy">
  <a class="bkguar__full"
     href="{$bkguar_url|escape:'htmlall':'UTF-8'}"
     target="_blank"
     rel="noopener">{$bkguar_full|escape:'htmlall':'UTF-8'}</a>
</div>
