{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

{if $hpPayment.googlepay.buttonOptions.buttonSize === 'static'}
    <style>
        [data-unzer-ui-component] unzer-payment {
            display: flex;
            justify-content: flex-end;
        }
    </style>
{/if}


<div data-payment-data-request='{json_encode($hpPayment.googlepay)}' data-unzer-ui-component='{
    "component": "google-pay",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")},
    "merchantConfig": {if !empty($hpPayment.merchantConfig)}{$hpPayment.merchantConfig}{else}null{/if}
}'>
    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}" {if !empty($hpPayment.merchantConfig)}skipFetchingMerchantConfig{/if}>
        <unzer-google-pay />
    </unzer-payment>
</div>