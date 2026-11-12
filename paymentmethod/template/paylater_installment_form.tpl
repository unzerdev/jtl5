{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "component": "paylater-installment",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")},
    "customer": {$hpPayment.customer->jsonSerialize()},
    "basket": {
        "amount": {json_encode($hpPayment.amount)},
        "currencyType": {json_encode($hpPayment.currency)},
        "country": {json_encode($hpPayment.country)}
    },
    "merchantConfig": {if !empty($hpPayment.merchantConfig)}{$hpPayment.merchantConfig}{else}null{/if}
}'>
    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}" {if !empty($hpPayment.merchantConfig)}skipFetchingMerchantConfig{/if}>
        <unzer-paylater-installment />
    </unzer-payment>
</div>
