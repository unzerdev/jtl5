{include file="{$hpPayment.pluginPath}paymentmethod/template/_includes.tpl"}

<div class="unzerUI form" novalidate>
    <div id="customer" class="field"></div>
    <div id="paylater-installment"></div>
</div>

<script>
$(document).ready(function() {
    var HpPayment = new window.HpPayment('{$hpPayment.publicKey}', window.HpPayment.PAYMENT_TYPES.PAYLATER_INSTALLMENT, {
        submitButton: $('{if $hpPayment.config.selectorSubmitButton}{$hpPayment.config.selectorSubmitButton}{else}#form_payment_extra .submit, #form_payment_extra .submit_once{/if}').get(0),
        locale: '{$hpPayment.locale}',
        {if empty($hpPayment.customerId) || $hpPayment.customerId == -1}
            customerId: {if !empty($hpPayment.customer->getId())}'{$hpPayment.customer->getId()}'{else}null{/if},
        {else}
            customerId: '{$hpPayment.customerId}',
        {/if}
        customer: {$hpPayment.customer->jsonSerialize()},
        amount: {json_encode($hpPayment.amount)},
        currency: {json_encode($hpPayment.currency)},
        country: {json_encode($hpPayment.country)},
    });
});
</script>

{include file="{$hpPayment.pluginPath}paymentmethod/template/_footer.tpl"}