{**
 * Pantalla de configuración del módulo.
 *
 * Un solo formulario repartido en pestañas: la pantalla tiene demasiados ajustes para una lista
 * plana, y en una lista plana el bloque de cobertura por idioma se comía la primera pantalla entera.
 * Cada ajuste que gobierna a otros los pliega hasta que hacen falta.
 *}
<div class="panel bkguar-config">
  <div class="panel-heading">
    <i class="icon-shield"></i> {l s='EU guarantee notice' d='Modules.Bkguarantee.Admin'}
  </div>

  <ul class="nav nav-tabs bkguar-tabs" role="tablist">
    <li class="active"><a href="#bkguar-tab-status" data-toggle="tab">
      <i class="icon-flag"></i> {l s='Status' d='Modules.Bkguarantee.Admin'}
      {if $bkguar_missing|@count}<span class="badge badge-danger">{$bkguar_missing|@count}</span>{/if}
    </a></li>
    <li><a href="#bkguar-tab-look" data-toggle="tab"><i class="icon-paint-brush"></i> {l s='Presentation' d='Modules.Bkguarantee.Admin'}</a></li>
    <li><a href="#bkguar-tab-scope" data-toggle="tab"><i class="icon-filter"></i> {l s='Catalogue' d='Modules.Bkguarantee.Admin'}</a></li>
    <li><a href="#bkguar-tab-garan" data-toggle="tab"><i class="icon-certificate"></i> GARAN</a></li>
    <li><a href="#bkguar-tab-mail" data-toggle="tab"><i class="icon-envelope"></i> {l s='Email' d='Modules.Bkguarantee.Admin'}</a></li>
  </ul>

  <form id="bkguar-form" class="form-horizontal" method="post" action="{$bkguar_action|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="submitBkGuaranteeConfig" value="1">
    <div class="tab-content">

      {* ---------------- estado ---------------- *}
      <div class="tab-pane active" id="bkguar-tab-status">
        {if $bkguar_missing|@count}
          <div class="alert alert-warning">
            <strong>{l s='These languages show no notice at all:' d='Modules.Bkguarantee.Admin'}</strong>
            {' '}{', '|implode:$bkguar_missing|escape:'htmlall':'UTF-8'}.
            <p>{l s='The notice may not be edited or translated by hand, so the module never falls back to another language: showing a Spanish notice to a French customer would look compliant without being compliant. Drop the official file published in the Official Journal for that language into' d='Modules.Bkguarantee.Admin'}
              <code>{$bkguar_dir|escape:'htmlall':'UTF-8'}&lt;iso&gt;.jpg</code>.</p>
          </div>
        {/if}

        <div class="row">
          <div class="col-lg-6">
            <table class="table bkguar-langs">
              <thead><tr>
                <th>{l s='Language' d='Modules.Bkguarantee.Admin'}</th>
                <th>{l s='ISO' d='Modules.Bkguarantee.Admin'}</th>
                <th>{l s='Notice' d='Modules.Bkguarantee.Admin'}</th>
              </tr></thead>
              <tbody>
                {foreach from=$bkguar_rows item=row}
                  <tr>
                    <td>{$row.name|escape:'htmlall':'UTF-8'}</td>
                    <td><code>{$row.iso|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{if $row.ready}<span class="badge badge-success">{l s='Installed' d='Modules.Bkguarantee.Admin'}</span>
                        {else}<span class="badge badge-danger">{l s='Missing' d='Modules.Bkguarantee.Admin'}</span>{/if}</td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>
          <div class="col-lg-6">
            <div class="alert {if $bkguar_qr.level === 'ok'}alert-success{elseif $bkguar_qr.level === 'tight'}alert-warning{else}alert-danger{/if}">
              <strong>{l s='QR code' d='Modules.Bkguarantee.Admin'}</strong> &mdash;
              {l s='the notice' d='Modules.Bkguarantee.Admin'}: {$bkguar_qr.side|intval}&nbsp;px,
              {if $bkguar_qr.level === 'ok'}{l s='scans comfortably.' d='Modules.Bkguarantee.Admin'}
              {elseif $bkguar_qr.level === 'tight'}{l s='tight: it scans on a good camera and fails on a mediocre one.' d='Modules.Bkguarantee.Admin'}
              {else}{l s='too small, no phone will read it.' d='Modules.Bkguarantee.Admin'}{/if}
              {if $bkguar_qr.level !== 'ok'}{l s='It scans comfortably from' d='Modules.Bkguarantee.Admin'} <strong>{$bkguar_qr_ideal|intval}&nbsp;px</strong>.{/if}
              <br>GARAN ({$bkguar_garan_width|intval}&nbsp;px): {$bkguar_qr_garan.side|intval}&nbsp;px,
              {if $bkguar_qr_garan.level === 'ok'}{l s='fine.' d='Modules.Bkguarantee.Admin'}
              {elseif $bkguar_qr_garan.level === 'tight'}{l s='tight.' d='Modules.Bkguarantee.Admin'}
              {else}{l s='too small.' d='Modules.Bkguarantee.Admin'}{/if}
              <br><small>{l s='The regulation requires the QR to be scannable with a standard phone in normal light, and that depends on the width you choose here, not on the artwork.' d='Modules.Bkguarantee.Admin'}</small>
            </div>
            {if $bkguar_preview}
              <a class="bkguar-fullsize" href="{$bkguar_preview|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">
                <img src="{$bkguar_preview|escape:'htmlall':'UTF-8'}" alt="{l s='EU harmonised notice on the legal guarantee of conformity' d='Modules.Bkguarantee.Admin'}">
              </a>
            {/if}
          </div>
        </div>
      </div>

      {* ---------------- presentación ---------------- *}
      <div class="tab-pane" id="bkguar-tab-look">
        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Presentation' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <p class="help-block">{l s='Pick one. They are drawn with the CSS of your shop; the notice inside is identical in all of them.' d='Modules.Bkguarantee.Admin'}</p>
            <div class="bkguar-picker">
              {foreach from=$bkguar_styles item=style}
                <label class="bkguar-pick{if $style === $bkguar_style} is-selected{/if}" data-style="{$style|escape:'htmlall':'UTF-8'}">
                  <input type="radio" name="BK_GUAR_STYLE" value="{$style|escape:'htmlall':'UTF-8'}"{if $style === $bkguar_style} checked{/if}>
                  <span class="bkguar-pick__shot">
                    {if $bkguar_preview}<img src="{$bkguar_preview|escape:'htmlall':'UTF-8'}" alt="">{/if}
                    <span class="bkguar-pick__frame bkguar-pick__frame--{$style|escape:'htmlall':'UTF-8'}"></span>
                  </span>
                  <span class="bkguar-pick__name">
                    {if $style === 'card'}{l s='Card with blue header' d='Modules.Bkguarantee.Admin'}
                    {elseif $style === 'framed'}{l s='Thin frame only' d='Modules.Bkguarantee.Admin'}
                    {elseif $style === 'plain'}{l s='No frame' d='Modules.Bkguarantee.Admin'}
                    {else}{l s='Wide band with a heading beside it' d='Modules.Bkguarantee.Admin'}{/if}
                  </span>
                </label>
              {/foreach}
            </div>
          </div>
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_ON name='BK_GUAR_ON'
          label={l s='Show the notice' d='Modules.Bkguarantee.Admin'}
          desc={l s='Master switch. Turn it off only if this shop sells exclusively to businesses.' d='Modules.Bkguarantee.Admin'}}
        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_PRODUCT name='BK_GUAR_PRODUCT'
          label={l s='On the product page' d='Modules.Bkguarantee.Admin'} desc=''}

        <div class="form-group bkguar-row" data-when="BK_GUAR_PRODUCT">
          <label class="control-label col-lg-3">{l s='Position on the product page' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_PLACEMENT" class="fixed-width-xxl">
              <option value="info"{if $bkguar_v.BK_GUAR_PLACEMENT === 'info'} selected{/if}>{l s='Below the add-to-cart block' d='Modules.Bkguarantee.Admin'}</option>
              <option value="thumbs"{if $bkguar_v.BK_GUAR_PLACEMENT === 'thumbs'} selected{/if}>{l s='Under the product gallery' d='Modules.Bkguarantee.Admin'}</option>
              <option value="footer"{if $bkguar_v.BK_GUAR_PLACEMENT === 'footer'} selected{/if}>{l s='At the bottom of the product page' d='Modules.Bkguarantee.Admin'}</option>
            </select>
            <p class="help-block">{l s='Not every theme renders every position. If one of them shows nothing, try another or move the module from Design > Positions.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>

        <div class="form-group bkguar-row" data-when="BK_GUAR_PRODUCT">
          <label class="control-label col-lg-3">{l s='Alignment' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_ALIGN" class="fixed-width-lg">
              <option value="left"{if $bkguar_v.BK_GUAR_ALIGN === 'left'} selected{/if}>{l s='Left' d='Modules.Bkguarantee.Admin'}</option>
              <option value="center"{if $bkguar_v.BK_GUAR_ALIGN === 'center'} selected{/if}>{l s='Centred' d='Modules.Bkguarantee.Admin'}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Width in pixels' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <input type="text" name="BK_GUAR_WIDTH" class="fixed-width-sm" value="{$bkguar_v.BK_GUAR_WIDTH|intval}">
            <p class="help-block">{l s='Between 240 and 720. On phones the notice always uses the full width available.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_CHECKOUT name='BK_GUAR_CHECKOUT'
          label={l s='On the order summary' d='Modules.Bkguarantee.Admin'} desc=''}

        <div class="form-group bkguar-row" data-when="BK_GUAR_CHECKOUT">
          <label class="control-label col-lg-3">{l s='Position in the checkout' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_CO_PLACE" class="fixed-width-xxl">
              <option value="summary"{if $bkguar_v.BK_GUAR_CO_PLACE === 'summary'} selected{/if}>{l s='Top of the order summary' d='Modules.Bkguarantee.Admin'}</option>
              <option value="payment"{if $bkguar_v.BK_GUAR_CO_PLACE === 'payment'} selected{/if}>{l s='Above the payment methods' d='Modules.Bkguarantee.Admin'}</option>
            </select>
            <p class="help-block">{l s='The order summary is visible from the first step. Above the payment methods the notice only appears once the customer has finished the address and shipping steps.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>
      </div>

      {* ---------------- alcance ---------------- *}
      <div class="tab-pane" id="bkguar-tab-scope">
        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Catalogue covered' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_SCOPE" class="fixed-width-xxl">
              <option value="all"{if $bkguar_v.BK_GUAR_SCOPE === 'all'} selected{/if}>{l s='Every product' d='Modules.Bkguarantee.Admin'}</option>
              <option value="categories"{if $bkguar_v.BK_GUAR_SCOPE === 'categories'} selected{/if}>{l s='Only the categories I choose' d='Modules.Bkguarantee.Admin'}</option>
            </select>
            <p class="help-block">{l s='The notice is mandatory on the sale of goods. Services and pure digital content are not goods, and that is the reason to leave part of the catalogue out.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>

        <div class="form-group bkguar-row" data-when-select="BK_GUAR_SCOPE" data-value="categories">
          <label class="control-label col-lg-3">{l s='Categories covered' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_CAT_IN[]" multiple class="bkguar-chosen">
              {foreach from=$bkguar_categories item=c}
                <option value="{$c.id|intval}"{if in_array($c.id, $bkguar_cat_in)} selected{/if}>{$c.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Categories left out' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_CAT_OUT[]" multiple class="bkguar-chosen">
              {foreach from=$bkguar_categories item=c}
                <option value="{$c.id|intval}"{if in_array($c.id, $bkguar_cat_out)} selected{/if}>{$c.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
            <p class="help-block">{l s='Wins over anything else: a product in one of these never shows the notice.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Products left out' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            {include file=$bkguar_finder name='BK_GUAR_PROD_OUT' value=$bkguar_v.BK_GUAR_PROD_OUT items=$bkguar_prod_out}
          </div>
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_SKIP_VIRTUAL name='BK_GUAR_SKIP_VIRTUAL'
          label={l s='Leave virtual products out' d='Modules.Bkguarantee.Admin'}
          desc={l s='Downloads and services are not goods under the sale of goods directive. Check your own catalogue before turning this on: a physical product flagged as virtual would lose the notice too.' d='Modules.Bkguarantee.Admin'}}

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_HIDE_B2B name='BK_GUAR_HIDE_B2B'
          label={l s='Hide it from business customers' d='Modules.Bkguarantee.Admin'}
          desc={l s='Only a shop selling exclusively to businesses falls outside the obligation. In a mixed shop this is your call, not a recommendation.' d='Modules.Bkguarantee.Admin'}}

        <div class="form-group bkguar-row" data-when="BK_GUAR_HIDE_B2B">
          <label class="control-label col-lg-3">{l s='Business customer groups' d='Modules.Bkguarantee.Admin'}</label>
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
          label={l s='Show the GARAN label' d='Modules.Bkguarantee.Admin'}
          desc={l s='Only appears on products covered by a durability guarantee rule with all three fields resolved.' d='Modules.Bkguarantee.Admin'}}

        <div class="form-group bkguar-row" data-when="BK_GUAR_GARAN">
          <label class="control-label col-lg-3">{l s='Position of the GARAN label' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <select name="BK_GUAR_GARAN_PLACE" class="fixed-width-xxl">
              <option value="thumbs"{if $bkguar_v.BK_GUAR_GARAN_PLACE === 'thumbs'} selected{/if}>{l s='Under the product gallery' d='Modules.Bkguarantee.Admin'}</option>
              <option value="info"{if $bkguar_v.BK_GUAR_GARAN_PLACE === 'info'} selected{/if}>{l s='Below the add-to-cart block' d='Modules.Bkguarantee.Admin'}</option>
              <option value="footer"{if $bkguar_v.BK_GUAR_GARAN_PLACE === 'footer'} selected{/if}>{l s='At the bottom of the product page' d='Modules.Bkguarantee.Admin'}</option>
            </select>
            <p class="help-block">{l s='The regulation places it next to the image of the goods.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>

        <div class="form-group bkguar-row" data-when="BK_GUAR_GARAN">
          <label class="control-label col-lg-3">{l s='GARAN width in pixels' d='Modules.Bkguarantee.Admin'}</label>
          <div class="col-lg-9">
            <input type="text" name="BK_GUAR_GARAN_W" class="fixed-width-sm" value="{$bkguar_v.BK_GUAR_GARAN_W|intval}">
            <p class="help-block">{l s='Between 180 and 520.' d='Modules.Bkguarantee.Admin'}</p>
          </div>
        </div>

        <div class="bkguar-row" data-when="BK_GUAR_GARAN">
          {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_GARAN_NEST name='BK_GUAR_GARAN_NEST'
            label={l s='Nested display for GARAN' d='Modules.Bkguarantee.Admin'}
            desc={l s='A compact badge that opens the full label on the first click, hover or touch. The regulation allows this for the label only, never for the notice.' d='Modules.Bkguarantee.Admin'}}
        </div>

        <div class="form-group">
          <div class="col-lg-9 col-lg-offset-3">
            <a class="btn btn-default" href="{$bkguar_rules_url|escape:'htmlall':'UTF-8'}">
              <i class="icon-list"></i> {l s='Durability guarantees' d='Modules.Bkguarantee.Admin'}
            </a>
          </div>
        </div>
      </div>

      {* ---------------- correo ---------------- *}
      <div class="tab-pane" id="bkguar-tab-mail">
        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_EMAIL name='BK_GUAR_EMAIL'
          label={l s='In the order confirmation email' d='Modules.Bkguarantee.Admin'}
          desc={l s='The notice has to stay available to the customer after the purchase, and the confirmation email is the durable medium that already reaches everyone.' d='Modules.Bkguarantee.Admin'}}

        <div class="bkguar-row" data-when="BK_GUAR_EMAIL">
          {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_EMAIL_ATT name='BK_GUAR_EMAIL_ATT'
            label={l s='Attach it to the email as well' d='Modules.Bkguarantee.Admin'}
            desc={l s='Half the inboxes block remote images. The attachment is what guarantees the notice actually arrives.' d='Modules.Bkguarantee.Admin'}}
        </div>

        {include file=$bkguar_switch on=$bkguar_v.BK_GUAR_DEBUG name='BK_GUAR_DEBUG'
          label={l s='Debug log' d='Modules.Bkguarantee.Admin'}
          desc={l s='Writes to log/bkguarantee.log inside the module.' d='Modules.Bkguarantee.Admin'}}
      </div>
    </div>

    <div class="panel-footer">
      <button type="submit" class="btn btn-default pull-right">
        <i class="process-icon-save"></i> {l s='Save' d='Modules.Bkguarantee.Admin'}
      </button>
    </div>
  </form>
</div>
