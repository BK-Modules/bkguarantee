{**
 * Importación y exportación de reglas GARAN.
 *
 * El fichero que sale por «exportar» es el que entra por «importar»: se saca, se edita en la hoja
 * de cálculo y se devuelve. Subir un fichero nunca escribe nada — enseña lo que se haría y espera
 * la confirmación.
 *}
<div class="panel bkguar-csv">
  <div class="panel-heading">
    <i class="icon-table"></i> {l s='Rules by spreadsheet' d='Modules.Bkguarantee.Admin'}
  </div>

  <div class="bkguar-csv__intro">
    {l s='Export what you already have, edit it in your spreadsheet and upload it back. A rule is recognised by its name within its shop, so importing the same file twice updates the same rules instead of duplicating them.' d='Modules.Bkguarantee.Admin'}
  </div>

  <form method="post" action="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}" enctype="multipart/form-data" class="bkguar-csv__form">
    <div class="bkguar-csv__file">
      <input type="file" name="bkguar_csv" accept=".csv,text/csv" id="bkguar_csv" required>
      <p class="help-block">{l s='CSV separated by ; or , in UTF-8. Columns:' d='Modules.Bkguarantee.Admin'} <code>{$bkguar_columns|escape:'htmlall':'UTF-8'}</code></p>
    </div>
    <div class="bkguar-csv__actions">
      <button type="submit" name="submitBkGuarImport" class="btn btn-primary">
        <i class="icon-eye"></i> {l s='Review the file' d='Modules.Bkguarantee.Admin'}
      </button>
      <a class="btn btn-default" href="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}&amp;submitBkGuarTemplate=1">
        <i class="icon-download"></i> {l s='Download template' d='Modules.Bkguarantee.Admin'}
      </a>
      <a class="btn btn-default" href="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}&amp;submitBkGuarExport=1">
        <i class="icon-share"></i> {l s='Export rules' d='Modules.Bkguarantee.Admin'}
      </a>
    </div>
  </form>

  {if $bkguar_preview}
    <div class="bkguar-csv__preview">
      <p class="bkguar-csv__counts">
        <span class="badge badge-success">{$bkguar_counts.new|intval} {l s='new' d='Modules.Bkguarantee.Admin'}</span>
        <span class="badge badge-info">{$bkguar_counts.update|intval} {l s='updated' d='Modules.Bkguarantee.Admin'}</span>
        <span class="badge badge-danger">{$bkguar_counts.skip|intval} {l s='skipped' d='Modules.Bkguarantee.Admin'}</span>
        <span class="text-muted">{l s='Nothing has been written yet.' d='Modules.Bkguarantee.Admin'}</span>
      </p>

      <div class="table-responsive">
        <table class="table bkguar-csv__table">
          <thead>
            <tr>
              <th>{l s='Rule' d='Modules.Bkguarantee.Admin'}</th>
              <th>{l s='Applies by' d='Modules.Bkguarantee.Admin'}</th>
              <th>{l s='Targets' d='Modules.Bkguarantee.Admin'}</th>
              <th class="text-center">{l s='Years' d='Modules.Bkguarantee.Admin'}</th>
              <th>{l s='Producer' d='Modules.Bkguarantee.Admin'}</th>
              <th>{l s='Model identifier' d='Modules.Bkguarantee.Admin'}</th>
              <th>{l s='Result' d='Modules.Bkguarantee.Admin'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$bkguar_preview item=row}
              <tr class="bkguar-csv__row bkguar-csv__row--{$row.action|escape:'htmlall':'UTF-8'}">
                <td>{$row.data.name|escape:'htmlall':'UTF-8'}</td>
                <td>{$row.filter_label|escape:'htmlall':'UTF-8'}</td>
                <td><span class="text-muted">{$row.data.filter_values|escape:'htmlall':'UTF-8'}</span></td>
                <td class="text-center">{$row.data.years|intval}</td>
                <td>{$row.data.brand|escape:'htmlall':'UTF-8'}</td>
                <td>{$row.data.model|escape:'htmlall':'UTF-8'}</td>
                <td>
                  {if $row.action === 'new'}
                    <span class="badge badge-success">{l s='Will be created' d='Modules.Bkguarantee.Admin'}</span>
                  {elseif $row.action === 'update'}
                    <span class="badge badge-info">{l s='Will be updated' d='Modules.Bkguarantee.Admin'}</span>
                  {else}
                    <span class="badge badge-danger">{l s='Skipped' d='Modules.Bkguarantee.Admin'}</span>
                    <ul class="bkguar-csv__errors">
                      {foreach from=$row.errors item=error}<li>{$error|escape:'htmlall':'UTF-8'}</li>{/foreach}
                    </ul>
                  {/if}
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>

      <form method="post" action="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}" class="bkguar-csv__confirm">
        <button type="submit" name="submitBkGuarImportConfirm" class="btn btn-primary">
          <i class="icon-check"></i> {l s='Import these rules' d='Modules.Bkguarantee.Admin'}
        </button>
      </form>
    </div>
  {/if}
</div>
