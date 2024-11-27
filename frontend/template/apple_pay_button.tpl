{include file="{$hpPayment.pluginPath}paymentmethod/template/_includes.tpl"}

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
</style>

<div class="unzerUI form" novalidate>
    <div class="applePayButtonContainer">
        <div class="apple-pay-button apple-pay-button-black" lang="{$hpPayment.locale}" role="link" tabindex="0">
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const applePayPaymentRequest = {json_encode($hpPayment.paymentRequest)};
    const snippets = {json_encode($hpPayment.snippets)};

    new window.UnzerApplePayV2('{$hpPayment.publicKey}', applePayPaymentRequest, snippets, {
        form: {if isset($hpPayment.config.pqSelectorOrderConfirmForm)}document.querySelector({json_encode($hpPayment.config.pqSelectorOrderConfirmForm)}) || {/if}document.getElementById('complete_order'),
        locale: '{$hpPayment.locale}'
    });
});
</script>