<div id="error-container" style="display: none">
    <div class="alert alert-danger"> </div>
</div>

<style>
    :root {
        {if !empty($hpPayment.config.ui_fontFamily)}--unzer-font:{$hpPayment.config.ui_fontFamily};{else}--unzer-font: var(--et-font-body-family, var(--font-family-sans-serif, sans-serif));{/if}
        {if !empty($hpPayment.config.ui_brandColor)}--unzer-brand-color: {$hpPayment.config.ui_brandColor};{/if}
        {if !empty($hpPayment.config.ui_textColor)}--unzer-text-color: {$hpPayment.config.ui_textColor};{/if}
        {if !empty($hpPayment.config.ui_backgroundColor)}--unzer-background-color: {$hpPayment.config.ui_backgroundColor};{/if}
        {if !empty($hpPayment.config.ui_linkColor)}--unzer-link-color: {$hpPayment.config.ui_linkColor};{/if}
        {if !empty($hpPayment.config.ui_cornerRadius)}--unzer-corner-radius: {$hpPayment.config.ui_cornerRadius === 'on'};{/if}
        {if !empty($hpPayment.config.ui_shadows)}--unzer-shadows: {$hpPayment.config.ui_shadows === 'on'};{/if}
    }

    #unzer-checkout-wrapper {
        display: block;
    }
</style>

{if $hpPayment.isSandbox && $hpPayment.isDev}
    <script type="module" src="https://static.test.unzer.com/v2/ui-components/index.js"></script>
{else}
    <script type="module" src="https://static-v2.unzer.com/v2/ui-components/index.js"></script>
{/if}
<script src="{$hpPayment.frontendUrl}js/unzer-ui-components.min.js?v={$hpPayment.pluginVersion}" defer="defer"></script> {* TODO *}