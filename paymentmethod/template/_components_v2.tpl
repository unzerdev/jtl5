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

    /** Fixed for saved payments **/
    .label-slide #order-additional-payment .saved-payment-form-group label {
        position: relative;
        cursor: pointer;
        font-size: inherit;
        transform: none;
        inset: 0;
        line-height: inherit;
        pointer-events: auto;
        overflow: visible;
    }

    .label-slide #order-additional-payment .saved-payment-form-group label:after {
        top: 0.15625rem;
        left: -1.5rem;
        z-index: inherit;
        height: 1rem;
        width: 1rem;
        background: 50%/50% 50% no-repeat;
    }

    #order-additional-payment .custom-radio .custom-control-input:checked ~ .custom-control-label::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23ffffff'/%3e%3c/svg%3e");
    }
</style>

{if $hpPayment.isSandbox && $hpPayment.isDev}
    <script type="module" src="https://static.test.unzer.com/v2/ui-components/index.js"></script>
{else}
    <script type="module" src="https://static-v2.unzer.com/v2/ui-components/index.js"></script>
{/if}
<script src="{$hpPayment.frontendUrl}js/unzer-ui-components.min.js?v={$hpPayment.pluginVersion}" defer="defer"></script> {* TODO *}