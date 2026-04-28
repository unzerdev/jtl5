{include file="{$hpPayment.pluginPath}paymentmethod/template/_components_v2.tpl"}

<div data-unzer-ui-component='{
    "hasSavedInfo": {json_encode($hpPayment.allowSave && !empty($hpPayment.savedInfo))},
    "component": "paypal",
    "submitButton": {json_encode($hpPayment.config.selectorSubmitButton|default:"#form_payment_extra .submit, #form_payment_extra .submit_once")}
}'>
    {* Save New Payment Data *}
    {if $hpPayment.allowSave && !empty($hpPayment.savedInfo)}
        <div class="mb-2">
            <div class="card-title h3">{lang key='s360_hp_savePaymentDataPaypal' section="s360_unzer_shop5"}</div>
            {radiogroup stacked=true class="saved-payment-form-group"}
                {foreach from=$hpPayment.savedInfo item=$item}
                    {radio
                        name="paymentData[resourceId]"
                        value=$item.payment_type_id
                        id="saved-payment-{$item.id}"
                        checked=($item@first)
                    }
                        <span class="content">
                            <strong>{$item.data.email}</strong>
                        </span>
                    {/radio}
                {/foreach}

                {radio name="paymentData[resourceId]" value="new" id="saved-payment-new"}
                    <span class="content">
                        <strong>{lang key='s360_hp_newPaymentDataPaypal' section="s360_unzer_shop5"}</strong>
                    </span>
                {/radio}
            {/radiogroup}
        </div>
    {/if}

    <unzer-payment publicKey="{$hpPayment.publicKey}" locale="{$hpPayment.locale}">
        <unzer-paypal credentialsOnFile="{if $hpPayment.allowSave}true{else}false{/if}" />
        <unzer-credentials-on-file />
    </unzer-payment>
</div>