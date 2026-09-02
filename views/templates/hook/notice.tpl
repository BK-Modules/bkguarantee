{**
 * Aviso armonizado sobre la garantía legal de conformidad.
 *
 * La imagen se sirve entera, en color y sin nada superpuesto: el anexo I del Reglamento (UE)
 * 2025/1960 prohíbe editar cualquiera de sus elementos, y la visualización anidada solo la admite
 * para la etiqueta GARAN. La tarjeta que la rodea es contenido propio del tema: presenta el aviso,
 * no lo modifica ni lo tapa.
 *}
<section class="bkguar bkguar--{$bkguar_place|escape:'htmlall':'UTF-8'}"
         style="--bkguar-w:{$bkguar_width|intval}px">
  <header class="bkguar__head">
    <span class="bkguar__mark" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2.5 4 6v6c0 4.6 3.2 8.4 8 9.5 4.8-1.1 8-4.9 8-9.5V6z"></path>
        <path d="m8.8 12.2 2.2 2.2 4.2-4.4"></path>
      </svg>
    </span>
    <span class="bkguar__title">{$bkguar_title|escape:'htmlall':'UTF-8'}</span>
  </header>

  <img class="bkguar__notice"
       src="{$bkguar_url|escape:'htmlall':'UTF-8'}"
       alt="{$bkguar_alt|escape:'htmlall':'UTF-8'}"
       loading="lazy">

  <a class="bkguar__full"
     href="{$bkguar_url|escape:'htmlall':'UTF-8'}"
     target="_blank"
     rel="noopener">{$bkguar_full|escape:'htmlall':'UTF-8'}</a>
</section>
