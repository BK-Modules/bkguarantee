{**
 * Módulos y artículos de bkmodules.com en el panel de configuración.
 *
 * El contenido llega del endpoint del hub con caché de un día; los enlaces del área de cliente
 * vienen ya construidos desde allí y aquí solo se pintan.
 *
 * Las imágenes las sirve la propia tienda (BkGuaranteeRemoteImage se trae una copia); si alguna
 * copia no se pudo hacer queda su URL de bkmodules.com, y por eso viajan los atributos de carga:
 * prioridad baja y decodificación asíncrona para no competir con la pantalla, y sin referer para
 * que ningún CDN las trate como enlazadas desde otro sitio. Sin `loading=lazy` a propósito: en una
 * pestaña que no está en primer plano una imagen diferida no se pide nunca.
 *}
<div class="panel bkguar-info">
  <div class="panel-heading">
    <i class="icon-info-circle"></i> {$bkguar_module->t('More from BK Modules', [], 'Modules.Bkguarantee.Admin') nofilter}
  </div>

  {* Estado de la versión: lo primero que se mira al abrir la ayuda *}
  <div class="bkguar-status {if !$bkguar_version.known}bkguar-status-unknown{elseif $bkguar_version.outdated}bkguar-status-outdated{else}bkguar-status-current{/if}">
    <span class="bkguar-status-icon">{if !$bkguar_version.known}?{elseif $bkguar_version.outdated}!{else}&#10003;{/if}</span>
    <span class="bkguar-status-text">
      <strong>{$bkguar_display_name|escape:'html':'UTF-8'} {$bkguar_version.installed|escape:'html':'UTF-8'}</strong>
      {if !$bkguar_version.known}
        {$bkguar_module->t('The latest published version could not be checked right now.', [], 'Modules.Bkguarantee.Admin') nofilter}
      {elseif $bkguar_version.outdated}
        {$bkguar_module->t('You have version %installed%; the latest published version is %latest%.', ['%installed%' => $bkguar_version.installed, '%latest%' => $bkguar_version.latest], 'Modules.Bkguarantee.Admin') nofilter}
      {else}
        {$bkguar_module->t('Your module is up to date.', [], 'Modules.Bkguarantee.Admin') nofilter}
      {/if}
    </span>
    {if $bkguar_version.outdated}
      <a class="btn btn-primary" href="{$bkguar_version.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
        <i class="icon-download"></i> {$bkguar_module->t('Get the update', [], 'Modules.Bkguarantee.Admin') nofilter}
      </a>
    {/if}
  </div>

  <div class="bkguar-info-links">
    <a class="btn btn-primary" href="{$bk_remote.links.contact|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
      <i class="icon-envelope"></i> {$bkguar_module->t('Contact support', [], 'Modules.Bkguarantee.Admin') nofilter}
    </a>
    <a class="btn btn-default" href="{$bk_remote.links.licenses|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
      <i class="icon-download"></i> {$bkguar_module->t('My licenses and downloads', [], 'Modules.Bkguarantee.Admin') nofilter}
    </a>
    <a class="btn btn-default" href="{$bk_remote.links.blog|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
      <i class="icon-book"></i> {$bkguar_module->t('Guides and news', [], 'Modules.Bkguarantee.Admin') nofilter}
    </a>
  </div>

  {if $bk_remote.catalog}
    <h4 class="bkguar-info-title">
      <i class="icon-puzzle-piece"></i> {$bkguar_module->t('Other BK Modules modules', [], 'Modules.Bkguarantee.Admin') nofilter}
    </h4>
    <div class="bk-catalog">
      {foreach from=$bk_remote.catalog item=item}
        <a class="bk-catalog-card" href="{$item.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
          {if $item.image}
            {* Si la imagen no llega, se retira el hueco en vez de dejar el icono de rota *}
            <span class="bk-catalog-media"><img src="{$item.image|escape:'html':'UTF-8'}" alt=""
                     decoding="async" fetchpriority="low" referrerpolicy="no-referrer"
                     onerror="this.parentNode.style.display='none';"></span>
          {/if}
          <span class="bk-catalog-body">
            <span class="bk-catalog-name">{$item.name|escape:'html':'UTF-8'}</span>
            <span class="bk-catalog-desc">{$item.description|escape:'html':'UTF-8'|truncate:120}</span>
            <span class="bk-catalog-more">{$bkguar_module->t('View the module', [], 'Modules.Bkguarantee.Admin') nofilter} →</span>
          </span>
        </a>
      {/foreach}
    </div>
  {/if}

  {if $bk_remote.posts}
    <h4 class="bkguar-info-title">
      <i class="icon-rss"></i> {$bkguar_module->t('Latest blog posts', [], 'Modules.Bkguarantee.Admin') nofilter}
    </h4>
    <div class="bk-posts">
      {foreach from=$bk_remote.posts item=post}
        <a class="bk-post-card" href="{$post.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
          {if $post.image}
            <span class="bk-post-thumb"><img src="{$post.image|escape:'html':'UTF-8'}" alt=""
                     decoding="async" fetchpriority="low" referrerpolicy="no-referrer"
                     onerror="this.parentNode.style.display='none';"></span>
          {/if}
          <span class="bk-post-body">
            <span class="bk-post-title">{$post.title|escape:'html':'UTF-8'}</span>
            <span class="bk-post-summary">{$post.summary|escape:'html':'UTF-8'}</span>
            <span class="bk-post-date">{$post.date|date_format:'%d/%m/%Y'}</span>
          </span>
        </a>
      {/foreach}
    </div>
  {/if}
</div>
