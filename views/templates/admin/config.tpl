{**
 * Pantalla de configuración del módulo.
 *
 * Un solo formulario repartido en pestañas: la pantalla tiene demasiados ajustes para una lista
 * plana, y en una lista plana el bloque de cobertura por idioma se comía la primera pantalla entera.
 * Cada ajuste que gobierna a otros los pliega hasta que hacen falta.
 *}
<div class="panel bkguar-config">
  <div class="panel-heading">
    <i class="icon-shield"></i> {$bkguar_module->t('EU guarantee notice', [], 'Modules.Bkguarantee.Admin') nofilter}
  </div>

  <div class="bkguar-summary">
    {foreach from=$bkguar_summary item=chip}
      <span class="bkguar-chip-state bkguar-chip-state--{$chip.level|escape:'htmlall':'UTF-8'}">
        <i class="icon-{$chip.icon|escape:'htmlall':'UTF-8'}"></i>
        <span class="bkguar-chip-state__label">{$chip.label|escape:'htmlall':'UTF-8'}</span>
        <strong>{$chip.value|escape:'htmlall':'UTF-8'}</strong>
      </span>
    {/foreach}
  </div>

  <nav class="bkguar-nav" role="tablist">
    <a class="bkguar-nav__item" href="#bkguar-tab-status" role="tab">
      <i class="icon-flag"></i> {$bkguar_module->t('Status', [], 'Modules.Bkguarantee.Admin') nofilter}
      {if $bkguar_missing|@count}<span class="bkguar-nav__badge">{$bkguar_missing|@count}</span>{/if}
    </a>
    <a class="bkguar-nav__item" href="#bkguar-tab-look" role="tab"><i class="icon-paint-brush"></i> {$bkguar_module->t('Presentation', [], 'Modules.Bkguarantee.Admin') nofilter}</a>
    <a class="bkguar-nav__item" href="#bkguar-tab-scope" role="tab"><i class="icon-filter"></i> {$bkguar_module->t('Catalogue', [], 'Modules.Bkguarantee.Admin') nofilter}</a>
    <a class="bkguar-nav__item" href="#bkguar-tab-garan" role="tab"><i class="icon-certificate"></i> GARAN</a>
    <a class="bkguar-nav__item" href="#bkguar-tab-durability" role="tab"><i class="icon-wrench"></i> {$bkguar_module->t('Updates and repair', [], 'Modules.Bkguarantee.Admin') nofilter}</a>
    <a class="bkguar-nav__item" href="#bkguar-tab-mail" role="tab"><i class="icon-envelope"></i> {$bkguar_module->t('Email', [], 'Modules.Bkguarantee.Admin') nofilter}</a>
  </nav>

  <form id="bkguar-form" class="form-horizontal" method="post" action="{$bkguar_action|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="submitBkGuaranteeConfig" value="1">
    <div class="tab-content">

      {* ---------------- estado ---------------- *}
      <div class="tab-pane active" id="bkguar-tab-status">
        {* Puesta en marcha: lo que hay que mirar y en qué orden. El aviso ya se está sirviendo
           desde la instalación, así que el primer paso es comprobarlo, no encenderlo. *}
        <ol class="bkguar-steps">
          <li class="bkguar-step">
            <span class="bkguar-step__n">1</span>
            <strong class="bkguar-step__t">{$bkguar_module->t('It is already being served', [], 'Modules.Bkguarantee.Admin') nofilter}</strong>
            <span class="bkguar-step__d">{$bkguar_module->t('The official notice travels inside the module in the 24 EU languages. Check below that your shop languages are covered and that the QR code scans.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
          </li>
          <li class="bkguar-step">
            <span class="bkguar-step__n">2</span>
            <strong class="bkguar-step__t">{$bkguar_module->t('Choose how it looks', [], 'Modules.Bkguarantee.Admin') nofilter}</strong>
            <span class="bkguar-step__d">{$bkguar_module->t('Frame, position and width, so it reads as part of your theme.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
            <a class="bkguar-step__go" href="#bkguar-tab-look">{$bkguar_module->t('Presentation', [], 'Modules.Bkguarantee.Admin') nofilter} &rsaquo;</a>
          </li>
          <li class="bkguar-step">
            <span class="bkguar-step__n">3</span>
            <strong class="bkguar-step__t">{$bkguar_module->t('Narrow the catalogue, if you need to', [], 'Modules.Bkguarantee.Admin') nofilter}</strong>
            <span class="bkguar-step__d">{$bkguar_module->t('Services and digital content are not goods: leave them out by category or by product.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
            <a class="bkguar-step__go" href="#bkguar-tab-scope">{$bkguar_module->t('Catalogue', [], 'Modules.Bkguarantee.Admin') nofilter} &rsaquo;</a>
          </li>
          <li class="bkguar-step">
            <span class="bkguar-step__n">4</span>
            <strong class="bkguar-step__t">{$bkguar_module->t('Add producer guarantees, if there are any', [], 'Modules.Bkguarantee.Admin') nofilter}</strong>
            <span class="bkguar-step__d">{$bkguar_module->t('Only for products the producer covers for more than two years, free of charge. One rule by brand or category is enough.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
            <a class="bkguar-step__go" href="#bkguar-tab-garan">GARAN &rsaquo;</a>
          </li>
          <li class="bkguar-step">
            <span class="bkguar-step__n">5</span>
            <strong class="bkguar-step__t">{$bkguar_module->t('Add what the producer says about software updates and repair, if it says it', [], 'Modules.Bkguarantee.Admin') nofilter}</strong>
            <span class="bkguar-step__d">{$bkguar_module->t('Germany, Austria and Italy require it when the producer provides it: how long software updates last, the EU repairability score or how to get spare parts. One rule by brand is usually enough; a product rule can add its own update date.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
            <a class="bkguar-step__go" href="#bkguar-tab-durability">{$bkguar_module->t('Updates and repair', [], 'Modules.Bkguarantee.Admin') nofilter} &rsaquo;</a>
          </li>
        </ol>

        {if $bkguar_missing|@count}
          <div class="alert alert-warning">
            <strong>{$bkguar_module->t('These languages show no notice at all:', [], 'Modules.Bkguarantee.Admin') nofilter}</strong>
            {' '}{', '|implode:$bkguar_missing|escape:'htmlall':'UTF-8'}.
            <p>{$bkguar_module->t('The notice may not be edited or translated by hand, so the module never falls back to another language: showing a Spanish notice to a French customer would look compliant without being compliant. Drop the official file published in the Official Journal for that language into', [], 'Modules.Bkguarantee.Admin') nofilter}
              <code>{$bkguar_dir|escape:'htmlall':'UTF-8'}&lt;iso&gt;.jpg</code>.</p>
          </div>
        {/if}

        <div class="row">
          <div class="col-lg-6">
            <table class="table bkguar-langs">
              <thead><tr>
                <th>{$bkguar_module->t('Language', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
                <th>{$bkguar_module->t('ISO', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
                <th>{$bkguar_module->t('Notice', [], 'Modules.Bkguarantee.Admin') nofilter}</th>
              </tr></thead>
              <tbody>
                {foreach from=$bkguar_rows item=row}
                  <tr>
                    <td>{$row.name|escape:'htmlall':'UTF-8'}</td>
                    <td><code>{$row.iso|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{if $row.ready}<span class="badge badge-success">{$bkguar_module->t('Installed', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
                        {else}<span class="badge badge-danger">{$bkguar_module->t('Missing', [], 'Modules.Bkguarantee.Admin') nofilter}</span>{/if}</td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>
          <div class="col-lg-6">
            <div class="alert {if $bkguar_qr.level === 'ok'}alert-success{elseif $bkguar_qr.level === 'tight'}alert-warning{else}alert-danger{/if}">
              <strong>{$bkguar_module->t('QR code', [], 'Modules.Bkguarantee.Admin') nofilter}</strong> &mdash;
              {$bkguar_module->t('the notice', [], 'Modules.Bkguarantee.Admin') nofilter}: {$bkguar_qr.side|intval}&nbsp;px,
              {if $bkguar_qr.level === 'ok'}{$bkguar_module->t('scans comfortably.', [], 'Modules.Bkguarantee.Admin') nofilter}
              {elseif $bkguar_qr.level === 'tight'}{$bkguar_module->t('tight: it scans on a good camera and fails on a mediocre one.', [], 'Modules.Bkguarantee.Admin') nofilter}
              {else}{$bkguar_module->t('too small, no phone will read it.', [], 'Modules.Bkguarantee.Admin') nofilter}{/if}
              {if $bkguar_qr.level !== 'ok'}{$bkguar_module->t('It scans comfortably from', [], 'Modules.Bkguarantee.Admin') nofilter} <strong>{$bkguar_qr_ideal|intval}&nbsp;px</strong>.{/if}
              <br>GARAN ({$bkguar_garan_width|intval}&nbsp;px): {$bkguar_qr_garan.side|intval}&nbsp;px,
              {if $bkguar_qr_garan.level === 'ok'}{$bkguar_module->t('fine.', [], 'Modules.Bkguarantee.Admin') nofilter}
              {elseif $bkguar_qr_garan.level === 'tight'}{$bkguar_module->t('tight.', [], 'Modules.Bkguarantee.Admin') nofilter}
              {else}{$bkguar_module->t('too small.', [], 'Modules.Bkguarantee.Admin') nofilter}{/if}
              <br><small>{$bkguar_module->t('The regulation requires the QR to be scannable with a standard phone in normal light, and that depends on the width you choose here, not on the artwork.', [], 'Modules.Bkguarantee.Admin') nofilter}</small>
            </div>
            {if $bkguar_preview}
              <a class="bkguar-fullsize" href="{$bkguar_preview|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">
                <img src="{$bkguar_preview|escape:'htmlall':'UTF-8'}" alt="{$bkguar_module->t('EU harmonised notice on the legal guarantee of conformity', [], 'Modules.Bkguarantee.Admin') nofilter}">
              </a>
            {/if}
          </div>
        </div>
      </div>

      {* ---------------- presentación ---------------- *}
      <div class="tab-pane" id="bkguar-tab-look">
        <div class="form-group">
          <label class="control-label col-lg-3">{$bkguar_module->t('Presentation', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <p class="help-block">{$bkguar_module->t('Pick one. They are drawn with the CSS of your shop; the notice inside is identical in all of them.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
            <div class="bkguar-picker">
              {foreach from=$bkguar_styles item=style}
                <label class="bkguar-pick{if $style === $bkguar_style} is-selected{/if}" data-style="{$style|escape:'htmlall':'UTF-8'}">
                  <input type="radio" name="BK_GUAR_STYLE" value="{$style|escape:'htmlall':'UTF-8'}"{if $style === $bkguar_style} checked{/if}>
                  <span class="bkguar-pick__shot">
                    {if $bkguar_preview}<img src="{$bkguar_preview|escape:'htmlall':'UTF-8'}" alt="">{/if}
                    <span class="bkguar-pick__frame bkguar-pick__frame--{$style|escape:'htmlall':'UTF-8'}"></span>
                  </span>
                  <span class="bkguar-pick__name">
                    {if $style === 'card'}{$bkguar_module->t('Card with blue header', [], 'Modules.Bkguarantee.Admin') nofilter}
                    {elseif $style === 'framed'}{$bkguar_module->t('Thin frame only', [], 'Modules.Bkguarantee.Admin') nofilter}
                    {elseif $style === 'plain'}{$bkguar_module->t('No frame', [], 'Modules.Bkguarantee.Admin') nofilter}
                    {else}{$bkguar_module->t('Wide band with a heading beside it', [], 'Modules.Bkguarantee.Admin') nofilter}{/if}
                  </span>
                </label>
              {/foreach}
            </div>
          </div>
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_ON name='BK_GUAR_ON'
          label=$bkguar_module->t('Show the notice', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('Master switch. Turn it off only if this shop sells exclusively to businesses.', [], 'Modules.Bkguarantee.Admin')}
        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_PRODUCT name='BK_GUAR_PRODUCT'
          label=$bkguar_module->t('On the product page', [], 'Modules.Bkguarantee.Admin') desc=''}

        <div class="form-group bkguar-row" data-when="BK_GUAR_PRODUCT">
          <label class="control-label col-lg-3">{$bkguar_module->t('Position on the product page', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_PLACEMENT" class="fixed-width-xxl">
              <option value="info"{if $bkguar_v.BK_GUAR_PLACEMENT === 'info'} selected{/if}>{$bkguar_module->t('Below the add-to-cart block', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              {if isset($bkguar_placements.thumbs)}
              <option value="thumbs"{if $bkguar_v.BK_GUAR_PLACEMENT === 'thumbs'} selected{/if}>{$bkguar_module->t('Under the product gallery', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              {/if}
              <option value="footer"{if $bkguar_v.BK_GUAR_PLACEMENT === 'footer'} selected{/if}>{$bkguar_module->t('At the bottom of the product page', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
            </select>
            <p class="help-block">{$bkguar_module->t('Not every theme renders every position. If one of them shows nothing, try another or move the module from Design > Positions.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        <div class="form-group bkguar-row" data-when="BK_GUAR_PRODUCT">
          <label class="control-label col-lg-3">{$bkguar_module->t('Alignment', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_ALIGN" class="fixed-width-lg">
              <option value="left"{if $bkguar_v.BK_GUAR_ALIGN === 'left'} selected{/if}>{$bkguar_module->t('Left', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              <option value="center"{if $bkguar_v.BK_GUAR_ALIGN === 'center'} selected{/if}>{$bkguar_module->t('Centred', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-3">{$bkguar_module->t('Width in pixels', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <input type="text" name="BK_GUAR_WIDTH" class="fixed-width-sm" value="{$bkguar_v.BK_GUAR_WIDTH|intval}">
            <p class="help-block">{$bkguar_module->t('Between 240 and 720. On phones the notice always uses the full width available.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_CHECKOUT name='BK_GUAR_CHECKOUT'
          label=$bkguar_module->t('On the order summary', [], 'Modules.Bkguarantee.Admin') desc=''}

        <div class="form-group bkguar-row" data-when="BK_GUAR_CHECKOUT">
          <label class="control-label col-lg-3">{$bkguar_module->t('Position in the checkout', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_CO_PLACE" class="fixed-width-xxl">
              <option value="summary"{if $bkguar_v.BK_GUAR_CO_PLACE === 'summary'} selected{/if}>{$bkguar_module->t('Top of the order summary', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              <option value="payment"{if $bkguar_v.BK_GUAR_CO_PLACE === 'payment'} selected{/if}>{$bkguar_module->t('Above the payment methods', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
            </select>
            <p class="help-block">{$bkguar_module->t('The order summary is visible from the first step. Above the payment methods the notice only appears once the customer has finished the address and shipping steps.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>
      </div>

      {* ---------------- alcance ---------------- *}
      <div class="tab-pane" id="bkguar-tab-scope">
        <div class="form-group">
          <label class="control-label col-lg-3">{$bkguar_module->t('Catalogue covered', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_SCOPE" class="fixed-width-xxl">
              <option value="all"{if $bkguar_v.BK_GUAR_SCOPE === 'all'} selected{/if}>{$bkguar_module->t('Every product', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              <option value="categories"{if $bkguar_v.BK_GUAR_SCOPE === 'categories'} selected{/if}>{$bkguar_module->t('Only the categories I choose', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
            </select>
            <p class="help-block">{$bkguar_module->t('The notice is mandatory on the sale of goods. Services and pure digital content are not goods, and that is the reason to leave part of the catalogue out.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        <div class="form-group bkguar-row" data-when-select="BK_GUAR_SCOPE" data-value="categories">
          <label class="control-label col-lg-3">{$bkguar_module->t('Categories covered', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_CAT_IN[]" multiple class="bkguar-chosen">
              {foreach from=$bkguar_categories item=c}
                <option value="{$c.id|intval}"{if in_array($c.id, $bkguar_cat_in)} selected{/if}>{$c.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-3">{$bkguar_module->t('Categories left out', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_CAT_OUT[]" multiple class="bkguar-chosen">
              {foreach from=$bkguar_categories item=c}
                <option value="{$c.id|intval}"{if in_array($c.id, $bkguar_cat_out)} selected{/if}>{$c.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
            <p class="help-block">{$bkguar_module->t('Wins over anything else: a product in one of these never shows the notice.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-3">{$bkguar_module->t('Products left out', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            {include file=$bkguar_finder name='BK_GUAR_PROD_OUT' value=$bkguar_v.BK_GUAR_PROD_OUT items=$bkguar_prod_out}
          </div>
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_SKIP_VIRTUAL name='BK_GUAR_SKIP_VIRTUAL'
          label=$bkguar_module->t('Leave virtual products out', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('Downloads and services are not goods under the sale of goods directive. Check your own catalogue before turning this on: a physical product flagged as virtual would lose the notice too.', [], 'Modules.Bkguarantee.Admin')}

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_HIDE_B2B name='BK_GUAR_HIDE_B2B'
          label=$bkguar_module->t('Hide it from business customers', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('Only a shop selling exclusively to businesses falls outside the obligation. In a mixed shop this is your call, not a recommendation.', [], 'Modules.Bkguarantee.Admin')}

        <div class="form-group bkguar-row" data-when="BK_GUAR_HIDE_B2B">
          <label class="control-label col-lg-3">{$bkguar_module->t('Business customer groups', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_B2B_GROUPS[]" multiple class="bkguar-chosen">
              {foreach from=$bkguar_groups item=g}
                <option value="{$g.id|intval}"{if in_array($g.id, $bkguar_b2b)} selected{/if}>{$g.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </div>
      </div>

      {* ---------------- GARAN ---------------- *}
      <div class="tab-pane" id="bkguar-tab-garan">
        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_GARAN name='BK_GUAR_GARAN'
          label=$bkguar_module->t('Show the GARAN label', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('Only appears on products covered by a durability guarantee rule with all three fields resolved.', [], 'Modules.Bkguarantee.Admin')}

        <div class="form-group bkguar-row" data-when="BK_GUAR_GARAN">
          <label class="control-label col-lg-3">{$bkguar_module->t('Position of the GARAN label', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_GARAN_PLACE" class="fixed-width-xxl">
              {if isset($bkguar_placements.thumbs)}
              <option value="thumbs"{if $bkguar_v.BK_GUAR_GARAN_PLACE === 'thumbs'} selected{/if}>{$bkguar_module->t('Under the product gallery', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              {/if}
              <option value="info"{if $bkguar_v.BK_GUAR_GARAN_PLACE === 'info'} selected{/if}>{$bkguar_module->t('Below the add-to-cart block', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              <option value="footer"{if $bkguar_v.BK_GUAR_GARAN_PLACE === 'footer'} selected{/if}>{$bkguar_module->t('At the bottom of the product page', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
            </select>
            <p class="help-block">{$bkguar_module->t('The regulation places it next to the image of the goods.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        <div class="form-group bkguar-row" data-when="BK_GUAR_GARAN">
          <label class="control-label col-lg-3">{$bkguar_module->t('GARAN width in pixels', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <input type="text" name="BK_GUAR_GARAN_W" class="fixed-width-sm" value="{$bkguar_v.BK_GUAR_GARAN_W|intval}">
            <p class="help-block">{$bkguar_module->t('Between 180 and 520.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        <div class="bkguar-row" data-when="BK_GUAR_GARAN">
          {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_GARAN_NEST name='BK_GUAR_GARAN_NEST'
            label=$bkguar_module->t('Nested display for GARAN', [], 'Modules.Bkguarantee.Admin')
            desc=$bkguar_module->t('The badge is the nested figure of Annex II itself, and the full label opens on the first click, hover or touch. The regulation grants this to the label only, never to the notice.', [], 'Modules.Bkguarantee.Admin')}
        </div>

        {* Última pantalla antes de comprar: la ley pide la etiqueta ahí, junto al botón. El selector
           cuelga del interruptor y los dos de la etiqueta: apagada, no hay nada que colocar. *}
        <div class="bkguar-row" data-when="BK_GUAR_GARAN">
          {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_GARAN_CO name='BK_GUAR_GARAN_CO'
            label=$bkguar_module->t('Show the label right before the order button', [], 'Modules.Bkguarantee.Admin')
            desc=$bkguar_module->t('Each product in the cart that has a label shows it in the last checkout step, under its name. The law requires it there: § 312j BGB (DE), § 8 FAGG (AT), art. 51 Codice del consumo (IT).', [], 'Modules.Bkguarantee.Admin')}

          <div class="form-group bkguar-row" data-when="BK_GUAR_GARAN_CO">
            <label class="control-label col-lg-3">{$bkguar_module->t('Position before the order button', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
            <div class="col-lg-9">
              <select name="BK_GUAR_GARAN_CO_PLACE">
                {if isset($bkguar_garan_co_placements.confirmation)}
                <option value="confirmation"{if $bkguar_v.BK_GUAR_GARAN_CO_PLACE === 'confirmation'} selected{/if}>{$bkguar_module->t('Above the order button — PrestaShop 8 and 9 themes', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
                {/if}
                <option value="payment"{if $bkguar_v.BK_GUAR_GARAN_CO_PLACE === 'payment'} selected{/if}>{$bkguar_module->t('Top of the payment step — any theme, 1.7 included', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              </select>
              {if isset($bkguar_garan_co_placements.confirmation)}
                <p class="help-block">{$bkguar_module->t('The PrestaShop 1.7 themes have no spot above the button: there the label would not show. If it does not appear in the last step of your checkout, choose the top of the payment step.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
              {/if}
              {if $bkguar_final_summary}
                <div class="alert alert-info">{$bkguar_module->t('Your checkout shows the final order summary (Shop Parameters > Order Settings), and the themes place the spot above the button before that summary: the label heads the order review and the summary sits between it and the button. Turn the final summary off if you want the label right above the button.', [], 'Modules.Bkguarantee.Admin') nofilter}</div>
              {/if}
            </div>
          </div>
        </div>

        <div class="form-group">
          <div class="col-lg-9 col-lg-offset-3">
            <a class="btn btn-default" href="{$bkguar_rules_url|escape:'htmlall':'UTF-8'}">
              <i class="icon-list"></i> {$bkguar_module->t('Producer data', [], 'Modules.Bkguarantee.Admin') nofilter}
            </a>
          </div>
        </div>
      </div>

      {* ---------------- actualizaciones y reparación ---------------- *}
      <div class="tab-pane" id="bkguar-tab-durability">
        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_DURA name='BK_GUAR_DURA'
          label=$bkguar_module->t('Show software updates and repair', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('What the producer states about software updates, the EU repairability score or spare parts, on the product page before the purchase and again in the order confirmation email. Only products covered by a producer data rule show it.', [], 'Modules.Bkguarantee.Admin')}

        <div class="form-group bkguar-row" data-when="BK_GUAR_DURA">
          <label class="control-label col-lg-3">{$bkguar_module->t('Position on the product page', [], 'Modules.Bkguarantee.Admin') nofilter}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_DURA_PLACE" class="fixed-width-xxl">
              <option value="info"{if $bkguar_v.BK_GUAR_DURA_PLACE === 'info'} selected{/if}>{$bkguar_module->t('Below the add-to-cart block', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              {if isset($bkguar_placements.thumbs)}
              <option value="thumbs"{if $bkguar_v.BK_GUAR_DURA_PLACE === 'thumbs'} selected{/if}>{$bkguar_module->t('Under the product gallery', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
              {/if}
              <option value="footer"{if $bkguar_v.BK_GUAR_DURA_PLACE === 'footer'} selected{/if}>{$bkguar_module->t('At the bottom of the product page', [], 'Modules.Bkguarantee.Admin') nofilter}</option>
            </select>
            <p class="help-block">{$bkguar_module->t('The law asks for it before the customer is bound by the order: next to the purchase is where it gets read.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
          </div>
        </div>

        <div class="form-group">
          <div class="col-lg-9 col-lg-offset-3">
            <div class="alert alert-warning">{$bkguar_module->t('What you enter here is shown to your customers before they buy and again in the order confirmation, and becomes part of the contract (§ 312d BGB, § 4 Abs. 4 FAGG, art. 49 c.5 Codice del consumo). Enter only what the manufacturer or provider states.', [], 'Modules.Bkguarantee.Admin') nofilter}</div>
            <a class="btn btn-default" href="{$bkguar_rules_url|escape:'htmlall':'UTF-8'}">
              <i class="icon-list"></i> {$bkguar_module->t('Producer data', [], 'Modules.Bkguarantee.Admin') nofilter}
            </a>
          </div>
        </div>
      </div>

      {* ---------------- correo ---------------- *}
      <div class="tab-pane" id="bkguar-tab-mail">
        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_EMAIL name='BK_GUAR_EMAIL'
          label=$bkguar_module->t('In the order confirmation email', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('The notice has to stay available to the customer after the purchase, and the confirmation email is the durable medium that already reaches everyone.', [], 'Modules.Bkguarantee.Admin')}

        {* El adjunto necesita un hook que PrestaShop dispara desde 1.7.1 (BkGuaranteeConfig::canAttach()) *}
        <div class="bkguar-row" data-when="BK_GUAR_EMAIL">
          {if $bkguar_can_attach}
          {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_EMAIL_ATT name='BK_GUAR_EMAIL_ATT'
            label=$bkguar_module->t('Attach it to the email as well', [], 'Modules.Bkguarantee.Admin')
            desc=$bkguar_module->t('Half the inboxes block remote images. The attachment is what guarantees the notice actually arrives.', [], 'Modules.Bkguarantee.Admin')}
          {else}
          <div class="form-group">
            <div class="col-lg-9 col-lg-offset-3">
              <p class="help-block">{$bkguar_module->t('This version of PrestaShop does not let modules attach files to its emails: the notice travels in the body of the email, as an image and as a link to the official file.', [], 'Modules.Bkguarantee.Admin') nofilter}</p>
            </div>
          </div>
          {/if}
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_DEBUG name='BK_GUAR_DEBUG'
          label=$bkguar_module->t('Debug log', [], 'Modules.Bkguarantee.Admin')
          desc=$bkguar_module->t('Writes to log/bkguarantee.log inside the module.', [], 'Modules.Bkguarantee.Admin')}
      </div>
    </div>

    <div class="panel-footer bkguar-save">
      <span class="bkguar-save__hint">{$bkguar_module->t('The notice is served exactly as the Official Journal publishes it; what you set here is where it goes and how wide.', [], 'Modules.Bkguarantee.Admin') nofilter}</span>
      <button type="submit" class="btn btn-primary">
        <i class="process-icon-save"></i> {$bkguar_module->t('Save', [], 'Modules.Bkguarantee.Admin') nofilter}
      </button>
    </div>
  </form>
</div>
