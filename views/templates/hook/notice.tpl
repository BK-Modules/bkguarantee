{**
 * Aviso armonizado sobre la garantía legal de conformidad.
 *
 * La imagen se sirve entera, en color y sin nada superpuesto: el anexo I del Reglamento (UE)
 * 2025/1960 prohíbe editar cualquiera de sus elementos. Lo que cambia entre presentaciones es el
 * marco y el texto que lo acompaña, nunca el aviso.
 *
 * INVARIANTE: ninguna presentación lo pliega, lo esconde tras un clic ni lo sustituye por un
 * enlace. La visualización anidada la reserva el reglamento para la etiqueta GARAN.
 *}
<section class="bkguar bkguar--{$bkguar_style|escape:'htmlall':'UTF-8'} bkguar--align-{$bkguar_align|escape:'htmlall':'UTF-8'} bkguar--{$bkguar_place|escape:'htmlall':'UTF-8'}"
         style="--bkguar-w:{$bkguar_width|intval}px">

  {if $bkguar_style === 'band'}
    {* Banda: el aviso deja de ser un cartel huérfano y pasa a ser una sección con su titular *}
    <div class="bkguar__figure">
      <img class="bkguar__notice"
           src="{$bkguar_url|escape:'htmlall':'UTF-8'}"
           alt="{$bkguar_alt|escape:'htmlall':'UTF-8'}"
           loading="lazy">
    </div>
    <div class="bkguar__aside">
      <h3 class="bkguar__band-title">{$bkguar_band_title|escape:'htmlall':'UTF-8'}</h3>
      <p class="bkguar__band-text">{$bkguar_band_text|escape:'htmlall':'UTF-8'}</p>
      <a class="bkguar__full"
         href="{$bkguar_url|escape:'htmlall':'UTF-8'}"
         target="_blank"
         rel="noopener">{$bkguar_full|escape:'htmlall':'UTF-8'}</a>
    </div>

  {else}
    {if $bkguar_style === 'card'}
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
    {/if}

    <img class="bkguar__notice"
         src="{$bkguar_url|escape:'htmlall':'UTF-8'}"
         alt="{$bkguar_alt|escape:'htmlall':'UTF-8'}"
         loading="lazy">

    <a class="bkguar__full"
       href="{$bkguar_url|escape:'htmlall':'UTF-8'}"
       target="_blank"
       rel="noopener">{$bkguar_full|escape:'htmlall':'UTF-8'}</a>
  {/if}
</section>
