<style>
    .apple-pay-button {
        display: block;
        -webkit-appearance: -apple-pay-button;
        -apple-pay-button-type: buy;
        -apple-pay-button-style: black;
    }

    .applePayButtonContainer {
        position: relative;
    }

    [data-unzer-ui-component] {
        /* display: flex;
        justify-content: flex-end;
        flex-flow: row wrap; */
        --apple-pay-button-height: 40px;
    }
</style>

{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-payment-data-request='{json_encode($hpPayment.paymentRequest)}' data-unzer-ui-component='{
    "component": "apple-pay",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")}
}'>
    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}">
        <unzer-apple-pay />
    </unzer-payment>
</div>