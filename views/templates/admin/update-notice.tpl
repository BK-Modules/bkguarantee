{**
 * Aviso de que hay una versión más reciente publicada.
 *
 * La versión la dice bkmodules.com en la misma respuesta del catálogo; la comparación la hace el
 * módulo contra la suya, que es la única que conoce con certeza.
 *}
<div class="alert alert-warning bkguar-update">
  <p class="bkguar-update-title">
    <i class="icon-refresh"></i> {l s='There is a newer version of this module' d='Modules.Bkguarantee.Admin'}
  </p>
  <p>
    {l s='You have version %installed%; the latest published version is %latest%.' sprintf=['%installed%' => $bkguar_version.installed, '%latest%' => $bkguar_version.latest] d='Modules.Bkguarantee.Admin'}
  </p>
  <a class="btn btn-primary" href="{$bkguar_version.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
    <i class="icon-download"></i> {l s='Get the update' d='Modules.Bkguarantee.Admin'}
  </a>
</div>
