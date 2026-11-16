{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "component": "bancontact",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")},
    "merchantConfig": {if !empty($hpPayment.merchantConfig)}{$hpPayment.merchantConfig}{else}null{/if}
}'>
    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}" {if !empty($hpPayment.merchantConfig)}skipFetchingMerchantConfig{/if}>
        <unzer-bancontact />
    </unzer-payment>
</div>
