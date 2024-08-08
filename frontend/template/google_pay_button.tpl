<link rel="stylesheet" href="https://static.unzer.com/v1/unzer.css" />
<link rel="stylesheet" href="{$hpPayment.frontendUrl}css/unzer.min.css?v={$hpPayment.pluginVersion}" />
<script type="text/javascript" src="https://static.unzer.com/v1/unzer.js"></script>
<script src="https://pay.google.com/gp/p/js/pay.js"></script>
<script src="{$hpPayment.frontendUrl}js/unzer.min.js?v={$hpPayment.pluginVersion}" defer="defer"></script>

<div id="error-container" style="display: none">
    <div class="alert alert-danger"> </div>
</div>

<div class="unzerUI form">
    <div id="googlepay-holder" class="field"></div>
</div>

<script>
$(function() {
    const settings = {json_encode($hpPayment.googlepay)};

    new window.UnzerGooglePay('{$hpPayment.publicKey}', {
        googlepay: settings,
        form: document.getElementById('complete_order'),
        locale: '{$hpPayment.locale}'
    });
});
</script>