{include file="{$hpPayment.pluginPath}paymentmethod/template/_includes.tpl"}

<div class="unzerUI form" novalidate>
    <div id="bancontact-holder" class="field"></div>
</div>

<script>
$(document).ready(function() {
    var HpPayment = new window.HpPayment('{$hpPayment.config.publicKey}', window.HpPayment.PAYMENT_TYPES.BANCONTACT, {
        submitButton: $('{if $hpPayment.config.selectorSubmitButton}{$hpPayment.config.selectorSubmitButton}{else}#form_payment_extra .submit, #form_payment_extra .submit_once{/if}').get(0),
        locale: '{$hpPayment.locale}',
        {if $hpPayment.styling}
            styling: {json_encode($hpPayment.styling)}
        {/if}
    });
});
</script>

{include file="{$hpPayment.pluginPath}paymentmethod/template/_footer.tpl"}