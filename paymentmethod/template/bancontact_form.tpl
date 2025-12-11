{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "component": "bancontact",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")}
}'>
    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}">
        <unzer-bancontact />
    </unzer-payment>
</div>
