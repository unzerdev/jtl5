<script src="{$hpPayment.frontendUrl}js/heidelpay.min.js?v={$hpPayment.pluginVersion}" defer="defer"></script>
<script>
$(document).ready(function() {
    window.HpInstalment('#heidelpayHDDPlans', document.getElementById('heidelpayHDDPlansSubmit'), $('#complete_order'));
});
</script>

<!-- Modal -->
<div class="modal fade" id="heidelpayHDDPlans" tabindex="-1" role="dialog" aria-labelledby="heidelpayHDDPlansLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="heidelpayHDDPlansLabel">
                    <i class="fa fa-search"></i>
                    {$hpInstalment.lang.confirmTitle}
                </h4>
            </div>
            <div class="modal-body">
                <p>{$hpInstalment.lang.downloadAndConfirm}</p>

                <div class="row">
                    <div class="col-12 col-xs-12 col-md-6"><strong>{$hpInstalment.lang.totalPurchaseAmount}</strong></div>
                    <div class="col-12 col-xs-12 col-md-6" id="total_purchase_amount">{number_format($hpInstalment.totalPurchaseAmount|default:0, 2)} {$hpInstalment.currency}</div>
                </div>

                <div class="row">
                    <div class="col-12 col-xs-12 col-md-6"><strong>{$hpInstalment.lang.totalInterestAmount}</strong></div>
                    <div class="col-12 col-xs-12 col-md-6" id="total_interest_amount">{number_format($hpInstalment.totalInterestAmount|default:0, 2)} {$hpInstalment.currency}</div>
                </div>

                <div class="row">
                    <div class="col-12 col-xs-12 col-md-6"><strong>{$hpInstalment.lang.totalAmount}</strong></div>
                    <div class="col-12 col-xs-12 col-md-6" id="total_amount">{number_format($hpInstalment.totalAmount|default:0, 2)} {$hpInstalment.currency}</div>
                </div>
                <br/>
                <p>
                    <strong>{$hpInstalment.lang.downloadYourPlan}</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{$hpInstalment.lang.closeModal}</button>
                <button type="button" class="btn btn-primary" id="heidelpayHDDPlansSubmit">{lang key="orderLiableToPay" section="checkout"}</button>
            </div>
        </div>
    </div>
</div>
