<div class="hp-admin-content">
    {* Keypair Selection *}
    <form action="{$formAction}" method="POST" class="hp-dashboard-keypair-form">
        <div class="hp-admin-option row mb-2">
            <div class="hp-admin-option__title col-xs-3 col-3">
                <label for="keypair-selection">{__('hpDashboardKeypairLabel')}</label>
            </div>
            <div class="hp-admin-option__input col-xs-9 col-9">
                <select name="keypair" id="keypair-selection" class="form-control custom-select">
                    <option value="{$defaultKeypair}">({__('hpDashboardKeypairDefault')}) {$defaultKeypair}</option>
                    {foreach $keypairs as $keypair}
                        <option value="{$keypair->getPublicKey()}" {if $keypair->getPublicKey() === $smarty.post.keypair}selected{/if}>
                            {$currencies[$keypair->getCurrencyId()].name} |
                            {if $keypair->isB2B()}({__('hpKeypairIsB2B')}) | {/if}
                            {$keypair->getPublicKey()}
                        </option>
                    {/foreach}
                </select>
                <small class="form-text help-block text-muted">{__('hpDashboardKeypairHelp')}</small>
            </div>
        </div>
    </form>

    {* Loading Indicator *}
    <div class="hp-on-ajax-loading" style="display: none">
        <div class="text-center my-5">
            <i class="fa fas fa-3x fa-spinner fa-pulse"></i>
            <p class="mt-3 text-muted">{__('hpDashboardLoading')}</p>
        </div>
    </div>

    {* Payment Methods (replaced via ajax when the keypair changes) *}
    <div class="hp-dashboard-payment-methods">
        <h2 class="subheading1">{__('Aktive Zahlungsarten')}</h2>
        <hr class="mb-3"/>
        <div class="row">
            {foreach $activePaymentMethods as $method}
                {include file="./partials/_paymentmethod.tpl" method=$method}
            {/foreach}
        </div>

        <h2 class="subheading1">{__('Inaktive Zahlungsarten')}</h2>
        <hr class="mb-3"/>
        <div class="row">
            {foreach $inactivePaymentMethods as $method}
                {include file="./partials/_paymentmethod.tpl" method=$method}
            {/foreach}
        </div>
    </div>
</div>

<script>
    (function($){
        $(function() {
            $('#keypair-selection').on('change', function(e) {
                e.preventDefault();

                const $select = $(this);
                const $paymentMethods = $('.hp-dashboard-payment-methods');
                const $loading = $('.hp-on-ajax-loading');

                $select.prop('disabled', true);
                $loading.show();
                $paymentMethods.hide();

                window.hpAdmin.doAjaxCall('dashboard:change', { keypair: $select.val() }, (response) => {
                    if (response.dashboard) {
                        // wrap the response so top-level elements can be found (scripts are dropped by parseHTML)
                        const $dashboard = $('<div>').append($.parseHTML(response.dashboard)).find('.hp-dashboard-payment-methods');
                        $paymentMethods.html($dashboard.html());

                        $select.prop('disabled', false);
                        $paymentMethods.show();
                        $loading.hide();
                    }
                });
            })
        });
    })(jQuery);
</script>
