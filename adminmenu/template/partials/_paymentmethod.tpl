<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-4">
    <h5 class="d-flex justify-content-between">
        <span>
            <img src="{$method->logo}" class="img-fluid" alt=""/>
            {__($method->name)}
        </span>

        <span class="ml-2">
            {if $method->isValid}
                <i class="fas fa-check-circle text-success"></i>
            {else}
                <i class="fas fa-times-circle text-danger"></i>
            {/if}
        </span>
    </h5>

    <div class="mb-2">
        <strong><a href="{$method->configUrl}">{__('Konfiguration')} <i class="fas fa-cog"></i></a></strong>
        <div class="d-flex justify-content-between">
            {__('Bei Unzer aktiv:')}
            <span class="ml-2">
                {if $method->isActiveUnzer}
                    <i aria-label="{__('Yes')}" class="fas fa-check-circle text-success"></i>
                {else}
                    <i aria-label="{__('No')}" class="fas fa-times-circle text-danger"></i>
                {/if}
            </span>
        </div>
        <div class="d-flex justify-content-between">
            {__('Im Shop aktiv:')}
            <span class="ml-2">
                {if $method->isActiveShop}
                    <i aria-label="{__('Yes')}" class="fas fa-check-circle text-success"></i>
                {else}
                    <i aria-label="{__('No')}" class="fas fa-times-circle text-danger"></i>
                {/if}
            </span>
        </div>
        <div class="d-flex justify-content-between">
            {__('Versandart verknüpft:')}

            <span class="ml-2">
                {if $method->hasShippingMethod}
                    <i aria-label="{__('Yes')}" class="fas fa-check-circle text-success"></i>
                {else}
                    <i aria-label="{__('No')}" class="fas fa-times-circle text-danger"></i>
                {/if}
            </span>
        </div>
        <div class="d-flex justify-content-between">
            {__('Zahlung vor Bestellabschluss:')}

            <span class="ml-2">
                {if $method->paymentBeforeOrderCompletion}
                    {__('Ja')}
                {else}
                    {__('Nein')}
                {/if}
            </span>
        </div>
    </div>

    <div class="mb-2">
        <strong>{__('Informationen')}</strong>
        <div class="d-flex justify-content-between">
            {__('Währungen')}
            <div class="ml-2 text-right">
                {foreach $method->currencies as $currency}
                    <span class="badge badge-pill badge-light">{$currency}</span>
                {/foreach}
            </div>
        </div>
        <div class="d-flex justify-content-between">
            {__('Länder')}

            <div class="ml-2 text-right">
                {foreach $method->countries as $country}
                    <span class="badge badge-pill badge-light">{$country}</span>
                {/foreach}
                {if $method->isActiveUnzer && empty($method->countries)}
                    <span class="badge badge-pill badge-light">{__('Alle')}</span>
                {/if}
            </div>
        </div>
        <div class="d-flex justify-content-between">
            {__('Kunden')}

            <div class="ml-2 text-right">
                {foreach $method->customerTypes as $customerType}
                    <span class="badge badge-pill badge-light">{$customerType}</span>
                {/foreach}
            </div>
        </div>
    </div>
</div>