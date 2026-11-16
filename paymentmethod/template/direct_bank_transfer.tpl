{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "component": "alipay",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")},
    "autoSubmit": true,
    "merchantConfig": {if !empty($hpPayment.merchantConfig)}{$hpPayment.merchantConfig}{else}null{/if}
}'>
    <div class="redirecting-note alert alert-info">{$hpPayment.redirectingNote}</div>

    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}" {if !empty($hpPayment.merchantConfig)}skipFetchingMerchantConfig{/if}>
        <unzer-open-banking />
    </unzer-payment>
</div>
