{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "component": "wechatpay",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")},
    "autoSubmit": true
}'>
    <div class="redirecting-note alert alert-info">{$hpPayment.redirectingNote}</div>

    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}">
        <unzer-wechatpay />
    </unzer-payment>
</div>