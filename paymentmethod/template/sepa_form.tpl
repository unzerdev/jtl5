
{include file="{$hpPayment.pluginPath}paymentmethod/template/_includes.tpl"}

<div class="unzerUI form" novalidate>
    <div id="sepa-IBAN" class="field"></div>

    <div id="sepa-mandate">
        {$hpPayment.mandate}
    </div>
</div>

<script>
$(document).ready(function() {
    var HpPayment = new window.HpPayment('{$hpPayment.config.publicKey}', window.HpPayment.PAYMENT_TYPES.SEPA, {
        submitButton: $('{if $hpPayment.config.selectorSubmitButton}{$hpPayment.config.selectorSubmitButton}{else}#form_payment_extra .submit, #form_payment_extra .submit_once{/if}').get(0),
        locale: '{$hpPayment.locale}'
    });
});
</script>

{include file="{$hpPayment.pluginPath}paymentmethod/template/_footer.tpl"}