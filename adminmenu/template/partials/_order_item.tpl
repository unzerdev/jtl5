{strip}
    <tr class="hp-order-item" data-shop-order-id="{$hpOrder->getId()}">
        <td class="hp-order-table-column hp-shop-order-number">
            {if $hpOrder->getJtlOrderNumber()}{$hpOrder->getJtlOrderNumber()}{elseif $hpOrder->getId()}{$hpOrder->getId()}{else} - {/if}
        </td>
        <td class="hp-order-table-column hp-shop-order-status">
            {if isset($hpOrder->getOrder()->cStatus)}
                {if $hpOrder->getOrder()->cStatus === "-1"}
                    {__('hpStateCanceled')}
                {elseif $hpOrder->getOrder()->cStatus === "1"}
                    {__('hpStateOpen')}
                {elseif $hpOrder->getOrder()->cStatus === "2"}
                    {__('hpStateInProgress')}
                {elseif $hpOrder->getOrder()->cStatus === "3"}
                    {__('hpStatePaid')}
                {elseif $hpOrder->getOrder()->cStatus === "4"}
                    {__('hpStateShipped')}
                {elseif $hpOrder->getOrder()->cStatus === "5"}
                    {__('hpStatePartlyShipped')}
                {else}
                    {$hpOrder->getOrder()->cStatus}
                {/if}
            {else}
                -
            {/if}
        </td>
        <td class="hp-order-table-column hp-payment-id">{if $hpOrder->getPaymentId()}{$hpOrder->getPaymentId()}{else} - {/if}</dt>
        <td class="hp-order-table-column hp-order-status hp-status-{if $hpOrder->getPaymentState()}{mb_strtolower($hpOrder->getPaymentState())|escape}{else}unknown{/if}">
            {if $hpOrder->getPaymentState()}
               {if $hpOrder->getPaymentState() === \UnzerSDK\Constants\PaymentState::STATE_NAME_PENDING}
                    {__('hpStatePending')}
                {elseif $hpOrder->getPaymentState() === \UnzerSDK\Constants\PaymentState::STATE_NAME_COMPLETED}
                    {__('hpStateCompleted')}
                {elseif $hpOrder->getPaymentState() === \UnzerSDK\Constants\PaymentState::STATE_NAME_CANCELED}
                    {__('hpStateCanceled')}
                {elseif $hpOrder->getPaymentState() === \UnzerSDK\Constants\PaymentState::STATE_NAME_PARTLY}
                    {__('hpStatePartly')}
                {elseif $hpOrder->getPaymentState() === \UnzerSDK\Constants\PaymentState::STATE_NAME_PAYMENT_REVIEW}
                    {__('hpStatePaymentReview')}
                {elseif $hpOrder->getPaymentState() === \UnzerSDK\Constants\PaymentState::STATE_NAME_CHARGEBACK}
                    {__('hpStateChargeback')}
                {else}
                    {$hpOrder->getPaymentState()}
                {/if}
            {else}
                -
            {/if}
        </td>
        <td class="hp-order-table-column hp-payment-type">
            {if $hpOrder->getPaymentTypeName()}
                {if $hpOrder->getPaymentTypeName() == 'card'}
                    {__('hpPaymentmethodCard')}
                {else if $hpOrder->getPaymentTypeName() == 'sepa-direct-debit'}
                    {__('hpPaymentmethodSEPA')}
                {else if $hpOrder->getPaymentTypeName() == 'sepa-direct-debit-guaranteed'}
                    {__('hpPaymentmethodSEPAGuaranteed')}
                {else if $hpOrder->getPaymentTypeName() == 'sepa-direct-debit-secured'}
                    {__('hpPaymentmethodSEPAGuaranteed')}
                {else if $hpOrder->getPaymentTypeName() == 'invoice'}
                    {__('hpPaymentmethodInvoice')}
                {else if $hpOrder->getPaymentTypeName() == 'invoice-guaranteed'}
                    {__('hpPaymentmethodInvoiceGuaranteed')}
                {else if $hpOrder->getPaymentTypeName() == 'invoice-secured'}
                    {__('hpPaymentmethodInvoiceGuaranteed')}
                {else if $hpOrder->getPaymentTypeName() == 'invoice-factoring'}
                    {__('hpPaymentmethodInvoiceFactoring')}
                {else if $hpOrder->getPaymentTypeName() == 'paypal'}
                    {__('hpPaymentmethodPayPal')}
                {else if $hpOrder->getPaymentTypeName() == 'sofort'}
                    {__('hpPaymentmethodSOFORT')}
                {else if $hpOrder->getPaymentTypeName() == 'giropay'}
                    {__('hpPaymentmethodGiropay')}
                {else if $hpOrder->getPaymentTypeName() == 'prepayment'}
                    {__('hpPaymentmethodPrepayment')}
                {else if $hpOrder->getPaymentTypeName() == 'eps'}
                    {__('hpPaymentmethodEPS')}
                {else if $hpOrder->getPaymentTypeName() == 'pis'}
                    {__('hpPaymentmethodFlexiPayDirect')}
                {else if $hpOrder->getPaymentTypeName() == 'alipay'}
                    {__('hpPaymentmethodAlipay')}
                {else if $hpOrder->getPaymentTypeName() == 'wechatpay'}
                    {__('hpPaymentmethodWeChatPay')}
                {else if $hpOrder->getPaymentTypeName() == 'ideal'}
                    {__('hpPaymentmethodiDEAL')}
                {else if $hpOrder->getPaymentTypeName() == 'hire-purchase-direct-debit'}
                    {__('hpPaymentmethodHirePurchaseDirectDebit')}
                {else if $hpOrder->getPaymentTypeName() == 'installment-secured'}
                    {__('hpPaymentmethodHirePurchaseDirectDebit')}
                {else}
                    {$hpOrder->getPaymentTypeName() }
                {/if}
            {/if}

            {if $hpOrder->getPaymentTypeId()}
                <em>({$hpOrder->getPaymentTypeId()})</em>
            {/if}
        </td>
        <td class="hp-order-table-column hp-amount tright">{\JTL\Catalog\Product\Preise::getLocalizedPriceString($hpOrder->getOrder()->fGesamtsumme)}</td>
        <td class="hp-order-table-column hp-date tright">{if isset($hpOrder->getOrder()->dErstellt)}{strtotime($hpOrder->getOrder()->dErstellt)|date_format:"d.m.Y H:i:s"}{else} - {/if}</td>
        <td class="hp-order-table-column hp-order-actions">
            <div class="input-group">
                <div class="btn-group input-group-btn">
                    <button type="button" class="btn btn-xs btn-default" title="Details ansehen" onclick="window.hpOrderManagement.getDetails('{$hpOrder->getId()}');"><i class="fa fas fa-pen fa-pencil" aria-hidden="true"></i></button>
                    {* {if $hpPortalUrl}
                        <a class="btn btn-xs btn-primary" title="Bestellung im hp-Portal anzeigen" href="{$hpPortalUrl}" target="_blank"><i class="fa fas fa-external-link" aria-hidden="true"></i></a>
                    {/if} *}
                </div>
            </div>
        </td>
    </tr>
{/strip}