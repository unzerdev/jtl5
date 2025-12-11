{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "component": "paylater-direct-debit",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")},
    "customer": {$hpPayment.customer->jsonSerialize()},
    "isB2B": {json_encode($hpPayment.isB2B)}
}'>
    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}">
        <unzer-paylater-direct-debit />
    </unzer-payment>
</div>