{block name='unzer_payment_info'}
    {if $Bestellung->Zahlungsinfo}
        <div class="unzer-payment-info">
            <h3>{lang key='doFollowingBanktransfer' section='checkout'}</h3>
            {if !empty($Bestellung->Zahlungsinfo->cIBAN)}
                <p>{lang key='iban' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cIBAN}</strong></p>
            {/if}
            {if !empty($Bestellung->Zahlungsinfo->cBIC)}
                <p>{lang key='bic' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cBIC}</strong></p>
            {/if}
            {if !empty($Bestellung->Zahlungsinfo->cInhaber)}
                <p>{lang key='accountHolder' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cInhaber}</strong></p>
            {/if}
            {if !empty($Bestellung->Zahlungsinfo->cVerwendungszweck)}
                <p>{lang key='purpose' section='checkout'}: <strong>{$Bestellung->Zahlungsinfo->cVerwendungszweck}</strong></p>
            {/if}
        </div>
    {/if}
{/block}