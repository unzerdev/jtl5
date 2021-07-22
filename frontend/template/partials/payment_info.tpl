{block name='unzer_payment_info'}
    {if $Bestellung->Zahlungsinfo}
        <div class="unzer-payment-info">
            <h3>{lang key='doFollowingBanktransfer' section='checkout'}</h3>
            <p>{lang key='iban' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cIBAN}</strong></p>
            <p>{lang key='bic' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cBIC}</strong></p>
            <p>{lang key='accountHolder' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cInhaber}</strong></p>
            <p>{lang key='purpose' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cVerwendungszweck}</strong></p>
        </div>
    {/if}
{/block}