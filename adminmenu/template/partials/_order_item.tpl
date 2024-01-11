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
        <td class="hp-order-table-column hp-payment-id">{if $hpOrder->getPaymentId()}{$hpOrder->getPaymentId()}{else} - {/if}</td>
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
                {elseif $hpOrder->getPaymentTypeName() == 'sepa-direct-debit'}
                    {__('hpPaymentmethodSEPA')}
                {elseif $hpOrder->getPaymentTypeName() == 'sepa-direct-debit-guaranteed'}
                    {__('hpPaymentmethodSEPAGuaranteed')}
                {elseif $hpOrder->getPaymentTypeName() == 'sepa-direct-debit-secured'}
                    {__('hpPaymentmethodSEPAGuaranteed')}
                {elseif $hpOrder->getPaymentTypeName() == 'invoice'}
                    {__('hpPaymentmethodInvoice')}
                {elseif $hpOrder->getPaymentTypeName() == 'invoice-guaranteed'}
                    {__('hpPaymentmethodInvoiceGuaranteed')}
                {elseif $hpOrder->getPaymentTypeName() == 'invoice-secured'}
                    {__('hpPaymentmethodInvoiceGuaranteed')}
                {elseif $hpOrder->getPaymentTypeName() == 'invoice-factoring'}
                    {__('hpPaymentmethodInvoiceFactoring')}
                {elseif $hpOrder->getPaymentTypeName() == 'paypal'}
                    {__('hpPaymentmethodPayPal')}
                {elseif $hpOrder->getPaymentTypeName() == 'sofort'}
                    {__('hpPaymentmethodSOFORT')}
                {elseif $hpOrder->getPaymentTypeName() == 'giropay'}
                    {__('hpPaymentmethodGiropay')}
                {elseif $hpOrder->getPaymentTypeName() == 'prepayment'}
                    {__('hpPaymentmethodPrepayment')}
                {elseif $hpOrder->getPaymentTypeName() == 'eps'}
                    {__('hpPaymentmethodEPS')}
                {elseif $hpOrder->getPaymentTypeName() == 'pis'}
                    {__('hpPaymentmethodFlexiPayDirect')}
                {elseif $hpOrder->getPaymentTypeName() == 'alipay'}
                    {__('hpPaymentmethodAlipay')}
                {elseif $hpOrder->getPaymentTypeName() == 'wechatpay'}
                    {__('hpPaymentmethodWeChatPay')}
                {elseif $hpOrder->getPaymentTypeName() == 'ideal'}
                    {__('hpPaymentmethodiDEAL')}
                {elseif $hpOrder->getPaymentTypeName() == 'hire-purchase-direct-debit'}
                    {__('hpPaymentmethodHirePurchaseDirectDebit')}
                {elseif $hpOrder->getPaymentTypeName() == 'installment-secured'}
                    {__('hpPaymentmethodHirePurchaseDirectDebit')}
                {else}
                    {__($hpOrder->getPaymentTypeName())}
                {/if}
            {/if}

            {if $hpOrder->getPaymentTypeId()}
                <em>({$hpOrder->getPaymentTypeId()})</em>
            {/if}
        </td>
        <td class="hp-order-table-column hp-amount tright">
            {\JTL\Catalog\Product\Preise::getLocalizedPriceString($hpOrder->getOrder()->fGesamtsumme, $hpOrder->getOrder()->Waehrung)}
        </td>
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