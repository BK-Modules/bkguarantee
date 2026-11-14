{**
 * Importación y exportación de las reglas de datos del fabricante.
 *
 * El fichero que sale por «exportar» es el que entra por «importar»: se saca, se edita en la hoja
 * de cálculo y se devuelve. Subir un fichero nunca escribe nada — enseña lo que se haría y espera
 * la confirmación.
 *}
<div class="panel bkguar-csv">
  <div class="panel-heading">
    <i class="icon-table"></i> {$bkguar_module->t('Rules by spreadsheet', [], 'Modules.Bkguarantee.Admin') nofilter}
  </div>

  <div class="bkguar-csv__intro">
    {$bkguar_module->t('Export what you already have, edit it in your spreadsheet and upload it back. A rule is recognised by its name within its shop, so importing the same file twice updates the same rules instead of duplicating them.', [], 'Modules.Bkguarantee.Admin') nofilter}
  </div>

  <form method="post" action="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}" enctype="multipart/form-data" class="bkguar-csv__form">
    <div class="bkguar-csv__file">
      <input type="file" name="bkguar_csv" accept=".csv,text/csv" id="bkguar_csv" required>
      <p class="help-block">{$bkguar_module->t('CSV separated by ; or , in UTF-8. Columns:', [], 'Modules.Bkguarantee.Admin') nofilter} <code>{$bkguar_columns|escape:'htmlall':'UTF-8'}</code></p>
      <p class="help-block">{$bkguar_module->t('updates: empty, none, a date (2031-12-31 or 31.12.2031) or a number of years. repair: empty, none or the EU score A to E; empty with any spare parts text means spare parts and repair. A column missing from the file is left as it is.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
    </div>
    <div class="bkguar-csv__actions">
      <button type="submit" name="submitBkGuarImport" class="btn btn-primary">
        <i class="icon-eye"></i> {$bkguar_module->t('Review the file', [], 'Modules.Bkguarantee.Admin') nofilter}
      </button>
      <a class="btn btn-default" href="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}&amp;submitBkGuarTemplate=1">
        <i class="icon-download"></i> {$bkguar_module->t('Download template', [], 'Modules.Bkguarantee.Admin') nofilter}
      </a>
      <a class="btn btn-default" href="{$bkguar_csv_action|escape:'htmlall':'UTF-8'}&amp;submitBkGuarExport=1">
        <i class="icon-share"></i> {$bkguar_module->t('Export rules', [], 'Modules.Bkguarantee.Admin') nofilter}
      </a>
    </div>
  </form>

  {if $bkguar_preview}
    <div class="bkguar-csv__preview">
      <p class="bkguar-csv__counts">
        <span class="badge badge-success">{$bkguar_counts.new|intval} {$bkguar_module->t('new', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
        <span class="badge badge-info">{$bkguar_counts.update|intval} {$bkguar_module->t('updated', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
        <span class="badge badge-danger">{$bkguar_counts.skip|intval} {$bkguar_module->t('skipped', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
        <span class="text-muted">{$bkguar_module->t('Nothing has been written yet.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
      </p>

      <div class="table-responsive">
        <table class="table bkguar-csv__table">
          <thead>
            <tr>
              <th>{$bkguar_module->t('Rule', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Applies by', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Targets', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th class="text-center">{$bkguar_module->t('Years', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Producer', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Model identifier', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Software updates', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Repair', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              <th>{$bkguar_module->t('Result', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
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
                {* Estados ya escapados por el controlador, los mismos del listado *}
                <td>{$row.updates_label}</td>
                <td>{$row.repair_label}</td>
                <td>
                  {if $row.action === 'new'}
                    <span class="badge badge-success">{$bkguar_module->t('Will be created', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
                  {elseif $row.action === 'update'}
                    <span class="badge badge-info">{$bkguar_module->t('Will be updated', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
                  {else}
                    <span class="badge badge-danger">{$bkguar_module->t('Skipped', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
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
          <i class="icon-check"></i> {$bkguar_module->t('Import these rules', [], 'Modules.Bkguarantee.Admin') nofilter}
        </button>
      </form>
    </div>
  {/if}
</div>
