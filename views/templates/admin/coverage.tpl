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
          {l s='Preview at the configured width. This is the size the customer sees, so judge it here and not at full size.' d='Modules.Bkguarantee.Admin'}
        </p>
        <img src="{$bkguar_preview|escape:'htmlall':'UTF-8'}"
             alt="{l s='EU harmonised notice on the legal guarantee of conformity' d='Modules.Bkguarantee.Admin'}"
             style="max-width:{$bkguar_width|intval}px;width:100%;border:1px solid #d6dbe0;border-radius:3px">
      {else}
        <div class="alert alert-danger" style="margin:0">
          {l s='No official notice is installed yet, so nothing is being shown anywhere in the shop.' d='Modules.Bkguarantee.Admin'}
        </div>
      {/if}
    </div>
  </div>
</div>
