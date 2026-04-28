<div class="col account-data-item account-data-item-saved-payment-methods col-lg-6 col-12">
    <div class="card ">
        <div class="card-header ">
            <div class="row align-items-center-util">
                <div class="col ">
                    <h2 class="h3">{lang key='s360_hp_saved_payment_methods' section='s360_unzer_shop5'}</h2>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vertical-middle table-hover">
                <tbody>
                    {foreach from=$s360_unzer.savedPaymentMethods item=$method}
                        <tr title="">
                            <td>{$method.name}</td>
                            <td>
                                {if $method.type == 'PAYPAL'}
                                    <strong>{$method.data.email}</strong>
                                {elseif $method.type == 'SEPA'}
                                    <strong>{$method.data.iban}</strong>
                                {elseif $method.type == 'CARD'}
                                    <strong>{$method.data.number}</strong>
                                    <p><small>{$method.data.cardHolder} ({$method.data.expiryDate})</small></p>
                                {/if}
                            </td>
                            <td class="text-right-util d-none d-md-table-cell">
                                <button class="btn btn-link delete-payment-method" type="button" data-id="{$method.payment_type_id}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.account-data-item-saved-payment-methods .delete-payment-method').on('click', function(e) {
        e.preventDefault();
        const $item = $(this).closest('tr');

        $.evo.io().request({
            name: 's360_unzer_shop5::deletePaymentMethod',
            params: [$(this).data('id')]
        },
        {},
        function (error, data) {
            if (error) {
                return;
            }

            if (data.success) {
                $item.fadeOut();
            }
        });
    })
});
</script>