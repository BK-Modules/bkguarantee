{**
 * Cobertura del arte oficial por idioma y vista previa al ancho configurado.
 *
 * Un idioma sin arte no enseña nada en el front: el módulo nunca sustituye el aviso por el de
 * otra lengua, así que la única señal de que falta es esta pantalla.
 *}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-flag"></i>
    {l s='Official notice per language' d='Modules.Bkguarantee.Admin'}
  </div>

  {if $bkguar_missing|@count}
    <div class="alert alert-warning">
      <strong>{l s='These languages show no notice at all:' d='Modules.Bkguarantee.Admin'}</strong>
      {' '}{', '|implode:$bkguar_missing|escape:'htmlall':'UTF-8'}.
      <p style="margin-top:.5rem">
        {l s='The notice may not be edited or translated by hand, so the module never falls back to another language: showing a Spanish notice to a French customer would look compliant without being compliant. Drop the official file published in the Official Journal for that language into' d='Modules.Bkguarantee.Admin'}
        <code>{$bkguar_dir|escape:'htmlall':'UTF-8'}&lt;iso&gt;.jpg</code>.
      </p>
    </div>
  {/if}

  <div class="row">
    <div class="col-lg-5">
      <table class="table">
        <thead>
          <tr>
            <th>{l s='Language' d='Modules.Bkguarantee.Admin'}</th>
            <th>{l s='ISO' d='Modules.Bkguarantee.Admin'}</th>
            <th>{l s='Notice' d='Modules.Bkguarantee.Admin'}</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$bkguar_rows item=row}
            <tr>
              <td>{$row.name|escape:'htmlall':'UTF-8'}</td>
              <td><code>{$row.iso|escape:'htmlall':'UTF-8'}</code></td>
              <td>
                {if $row.ready}
                  <span class="badge badge-success">{l s='Installed' d='Modules.Bkguarantee.Admin'}</span>
                {else}
                  <span class="badge badge-danger">{l s='Missing' d='Modules.Bkguarantee.Admin'}</span>
                {/if}
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>

    <div class="col-lg-7">
      {if $bkguar_preview}
        <p class="help-block">
          {l s='The three presentations side by side, with the CSS of the shop. The one in use is marked; the notice inside is identical in all three.' d='Modules.Bkguarantee.Admin'}
        </p>
        <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-start">
          {foreach from=$bkguar_styles item=style}
            <div style="flex:0 0 190px;max-width:190px">
              <div style="font-size:12px;font-weight:600;margin-bottom:6px;
                          color:{if $style === $bkguar_style}#00723d{else}#6c868e{/if}">
                {if $style === 'card'}{l s='Card with blue header' d='Modules.Bkguarantee.Admin'}
                {elseif $style === 'framed'}{l s='Thin frame only' d='Modules.Bkguarantee.Admin'}
                {else}{l s='No frame' d='Modules.Bkguarantee.Admin'}{/if}
                {if $style === $bkguar_style} &middot; {l s='in use' d='Modules.Bkguarantee.Admin'}{/if}
              </div>
              <section class="bkguar bkguar--{$style|escape:'htmlall':'UTF-8'} bkguar--align-left"
                       style="--bkguar-w:190px;margin:0">
                {if $style === 'card'}
                  <header class="bkguar__head">
                    <span class="bkguar__mark" aria-hidden="true">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.5 4 6v6c0 4.6 3.2 8.4 8 9.5 4.8-1.1 8-4.9 8-9.5V6z"></path>
                        <path d="m8.8 12.2 2.2 2.2 4.2-4.4"></path>
                      </svg>
                    </span>
                    <span class="bkguar__title">{l s='EU legal guarantee' d='Modules.Bkguarantee.Shop'}</span>
                  </header>
                {/if}
                <img class="bkguar__notice" src="{$bkguar_preview|escape:'htmlall':'UTF-8'}"
                     alt="{l s='EU harmonised notice on the legal guarantee of conformity' d='Modules.Bkguarantee.Admin'}">
                <span class="bkguar__full">{l s='View full size' d='Modules.Bkguarantee.Shop'}</span>
              </section>
            </div>
          {/foreach}
        </div>
        <p class="help-block" style="margin-top:14px">
          {l s='In the shop the notice is shown at' d='Modules.Bkguarantee.Admin'}
          <strong>{$bkguar_width|intval}&nbsp;px</strong>.
          {l s='Judge legibility at that width, not at full size.' d='Modules.Bkguarantee.Admin'}
        </p>
      {else}
        <div class="alert alert-danger" style="margin:0">
          {l s='No official notice is installed yet, so nothing is being shown anywhere in the shop.' d='Modules.Bkguarantee.Admin'}
        </div>
      {/if}
    </div>
  </div>
</div>
