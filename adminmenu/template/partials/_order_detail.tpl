{strip}
    <div class="hp-order-detail-wrapper">
        {* <div class="row">
            <div class="col-12 col-xs-12">
                <div class="input-group">
                    <div class="btn-group input-group-btn">
                        {if $hpPortalUrl}
                            {if $hpPayment->getPaymentType()->getResourceName() === 'paylater-invoice'}
                                <a class="btn btn-primary" title="{__('hpOpenInPaylaterPortal')}" href="{$hpPortalUrl}" target="_blank">
                                    {__('hpOpenInPaylaterPortal')}
                                </a>
                            {else}
                                <a class="btn btn-primary" title="{__('hpOpenInPortal')}" href="{$hpPortalUrl}" target="_blank">
                                    {__('hpOpenInPortal')}
                                </a>
                            {/if}
                        {/if}
                    </div>
                </div>
            </div>
        </div> *}

        <div class="row">
            <div class="col-12 col-md-6 col-xs-12">
                <div class="card panel panel-default">
                    <div class="card-header panel-heading">
                        <h4 class="panel-title">{__('hpDelivieryAddress')}</h4>
                    </div>
                    <div class="card-body panel-body">
                        {if $hpOrder->Lieferadresse}
                            {if !empty($hpOrder->Lieferadresse->cFirma)}
                                {$hpOrder->Lieferadresse->cFirma} <br/>
                            {/if}
                            {$hpOrder->Lieferadresse->cTitel} {$hpOrder->Lieferadresse->cVorname} {$hpOrder->Lieferadresse->cNachname}
                            {if !empty($hpOrder->Lieferadresse->cStrasse)}
                                <br/>{$hpOrder->Lieferadresse->cStrasse} {$hpOrder->Lieferadresse->cHausnummer}
                            {/if}
                            {if !empty($hpOrder->Lieferadresse->cAdressZusatz)}
                                <br/>{$hpOrder->Lieferadresse->cAdressZusatz}
                            {/if}
                            {if !empty($hpOrder->Lieferadresse->cPLZ) || !empty($hpOrder->Lieferadresse->cOrt)}
                                <br/>{$hpOrder->Lieferadresse->cPLZ} {$hpOrder->Lieferadresse->cOrt}
                            {/if}
                            {if !empty($hpOrder->Lieferadresse->cBundesland)}
                                <br/>{$hpOrder->Lieferadresse->cBundesland}
                            {/if}
                            {if !empty($hpOrder->Lieferadresse->cLand)}
                                <br/>{$hpOrder->Lieferadresse->cLand}<br/>
                            {/if}
                            {if !empty($hpOrder->Lieferadresse->cTel)}
                                <br/>{__('hpTelephoneLabel')} {$hpOrder->Lieferadresse->cTel}<br/>
                            {/if}
                            {if !empty($hpOrder->Lieferadresse->cMail)}
                                <br/>{__('hpEmailAddressLabel')} <a href="mailto: {$hpOrder->Lieferadresse->cMail}">{$hpOrder->Lieferadresse->cMail}</a>
                            {/if}
                        {elseif $hpOrder->oRechnungsadresse}
                            {if !empty($hpOrder->oRechnungsadresse->cFirma)}
                                {$hpOrder->oRechnungsadresse->cFirma} <br/>
                            {/if}
                            {$hpOrder->oRechnungsadresse->cTitel} {$hpOrder->oRechnungsadresse->cVorname} {$hpOrder->oRechnungsadresse->cNachname}
                            {if !empty($hpOrder->oRechnungsadresse->cStrasse)}
                                <br/>{$hpOrder->oRechnungsadresse->cStrasse} {$hpOrder->oRechnungsadresse->cHausnummer}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cAdressZusatz)}
                                <br/>{$hpOrder->oRechnungsadresse->cAdressZusatz}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cPLZ) || !empty($hpOrder->oRechnungsadresse->cOrt)}
                                <br/>{$hpOrder->oRechnungsadresse->cPLZ} {$hpOrder->oRechnungsadresse->cOrt}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cBundesland)}
                                <br/>{$hpOrder->oRechnungsadresse->cBundesland}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cLand)}
                                <br/>{$hpOrder->oRechnungsadresse->cLand}<br/>
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cTel)}
                                <br/>{__('hpTelephoneLabel')} {$hpOrder->oRechnungsadresse->cTel}<br/>
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cMail)}
                                <br/>{__('hpEmailAddressLabel')} <a href="mailto: {$hpOrder->oRechnungsadresse->cMail}">{$hpOrder->oRechnungsadresse->cMail}</a>
                            {/if}
                        {/if}
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xs-12">
                <div class="card panel panel-default">
                    <div class="card-header panel-heading">
                        <h4 class="panel-title">{__('hpBillingAddress')}</h4>
                    </div>
                    <div class="card-body panel-body">
                        {if $hpOrder->oRechnungsadresse}
                            {if !empty($hpOrder->oRechnungsadresse->cFirma)}
                                {$hpOrder->oRechnungsadresse->cFirma} <br/>
                            {/if}
                            {$hpOrder->oRechnungsadresse->cTitel} {$hpOrder->oRechnungsadresse->cVorname} {$hpOrder->oRechnungsadresse->cNachname}
                            {if !empty($hpOrder->oRechnungsadresse->cStrasse)}
                                <br/>{$hpOrder->oRechnungsadresse->cStrasse} {$hpOrder->oRechnungsadresse->cHausnummer}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cAdressZusatz)}
                                <br/>{$hpOrder->oRechnungsadresse->cAdressZusatz}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cPLZ) || !empty($hpOrder->oRechnungsadresse->cOrt)}
                                <br/>{$hpOrder->oRechnungsadresse->cPLZ} {$hpOrder->oRechnungsadresse->cOrt}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cBundesland)}
                                <br/>{$hpOrder->oRechnungsadresse->cBundesland}
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cLand)}
                                <br/>{$hpOrder->oRechnungsadresse->cLand}<br/>
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cTel)}
                                <br/>{__('hpTelephoneLabel')} {$hpOrder->oRechnungsadresse->cTel}<br/>
                            {/if}
                            {if !empty($hpOrder->oRechnungsadresse->cMail)}
                                <br/>{__('hpEmailAddressLabel')} <a href="mailto: {$hpOrder->oRechnungsadresse->cMail}">{$hpOrder->oRechnungsadresse->cMail}</a>
                            {/if}
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xs-12">
                <div class="card panel panel-default">
                    <div class="card-header panel-heading">
                        <h4 class="panel-title">{__('hpStatusInformation')}</h4>
                    </div>
                    <div class="card-body panel-body">
                        <div class="row">
                            <div class="col-12 col-xs-12">
                                <dl class="dl-horizontal row">
                                    <dt class="col-5">{__('hpStatus')}</dt>
                                    <dd class="col-7">
                                        {if $hpPayment->getState() === \UnzerSDK\Constants\PaymentState::STATE_PENDING}
                                            {__('hpStatePending')}
                                        {elseif $hpPayment->getState() === \UnzerSDK\Constants\PaymentState::STATE_COMPLETED}
                                            {__('hpStateCompleted')}
                                        {elseif $hpPayment->getState() === \UnzerSDK\Constants\PaymentState::STATE_CANCELED}
                                            {__('hpStateCanceled')}
                                        {elseif $hpPayment->getState() === \UnzerSDK\Constants\PaymentState::STATE_PARTLY}
                                            {__('hpStatePartly')}
                                        {elseif $hpPayment->getState() === \UnzerSDK\Constants\PaymentState::STATE_PAYMENT_REVIEW}
                                            {__('hpStatePaymentReview')}
                                        {elseif $hpPayment->getState() === \UnzerSDK\Constants\PaymentState::STATE_CHARGEBACK}
                                            {__('hpStateChargeback')}
                                        {else}
                                            {\UnzerSDK\Constants\PaymentState::mapStateCodeToName($hpPayment->getState())}
                                        {/if}
                                    </dd>

                                    {if $hpPayment->getInvoiceId()}
                                        <dt class="col-5">{__('hpInvoiceNumber')}</dt>
                                        <dd class="col-7">
                                            {$hpPayment->getInvoiceId()}
                                        </dd>
                                    {elseif $hpOrderMapping->getInvoiceId()}
                                        <dt class="col-5">{__('hpInvoiceNumber')}</dt>
                                        <dd class="col-7">
                                            {$hpOrderMapping->getInvoiceId()}
                                        </dd>
                                    {/if}

                                    <dt class="col-5">{__('hpPaymentId')}</dt>
                                    <dd class="col-7">{$hpPayment->getId()}</dd>

                                    <dt class="col-5">{__('hpPaymentMethod')}</dt>
                                    <dd class="col-7">
                                        {if $hpPayment->getPaymentType()->getResourceName() == 'card'}
                                            {__('hpPaymentmethodCard')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'sepa-direct-debit'}
                                            {__('hpPaymentmethodSEPA')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'sepa-direct-debit-guaranteed'}
                                            {__('hpPaymentmethodSEPAGuaranteed')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'sepa-direct-debit-secured'}
                                            {__('hpPaymentmethodSEPAGuaranteed')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'invoice'}
                                            {__('hpPaymentmethodInvoice')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'invoice-guaranteed'}
                                            {__('hpPaymentmethodInvoiceGuaranteed')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'invoice-secured'}
                                            {__('hpPaymentmethodInvoiceGuaranteed')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'invoice-factoring'}
                                            {__('hpPaymentmethodInvoiceFactoring')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'paypal'}
                                            {__('hpPaymentmethodPayPal')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'sofort'}
                                            {__('hpPaymentmethodSOFORT')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'giropay'}
                                            {__('hpPaymentmethodGiropay')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'prepayment'}
                                            {__('hpPaymentmethodPrepayment')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'eps'}
                                            {__('hpPaymentmethodEPS')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'pis'}
                                            {__('hpPaymentmethodFlexiPayDirect')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'alipay'}
                                            {__('hpPaymentmethodAlipay')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'wechatpay'}
                                            {__('hpPaymentmethodWeChatPay')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'ideal'}
                                            {__('hpPaymentmethodiDEAL')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'hire-purchase-direct-debit'}
                                            {__('hpPaymentmethodHirePurchaseDirectDebit')}
                                        {elseif $hpPayment->getPaymentType()->getResourceName() == 'installment-secured'}
                                            {__('hpPaymentmethodHirePurchaseDirectDebit')}
                                        {else}
                                            {__($hpPayment->getPaymentType()->getResourceName())}
                                        {/if}

                                        <em>({$hpPayment->getPaymentType()->getId()})</em>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xs-12">
                <div class="card panel panel-default">
                    <div class="card-header panel-heading">
                        <h4 class="panel-title">{__('hpTransactions')}</h4>
                    </div>
                    {if !empty($hpPayment->getAuthorization() || !empty($hpPayment->getCharges()))}
                        <table class="list table table-striped">
                            <thead>
                            <tr>
                                <th class="tleft">{__('hpID')}</th>
                                <th class="tleft">{__('hpShortID')}</th>
                                <th class="tleft">{__('hpStatus')}</th>
                                <th class="tleft">{__('hpAmount')}</th>
                            </tr>
                            </thead>
                            <tbody>
                                {if !empty($hpPayment->getAuthorization())}
                                    <tr style="margin-top:10px;" class="{if $hpPayment->getAuthorization()->isError()}danger{elseif $hpPayment->getAuthorization()->isPending()}warning{else}success{/if}">
                                        <td>{$hpPayment->getAuthorization()->getId()}</td>
                                        <td class="hp-short-id">{$hpPayment->getAuthorization()->getShortId()}</td>
                                        <td class="hp-status">
                                            {if $hpPayment->getAuthorization()->isPending()}
                                                {__('hpStatePending')}
                                            {elseif $hpPayment->getAuthorization()->isError()}
                                                {__('hpStateFailure')}
                                            {elseif $hpPayment->getAuthorization()->isSuccess()}
                                                {__('hpStateSuccessful')}
                                            {else}
                                                -
                                            {/if}

                                            {if $hpPayment->getAuthorization()->getMessage()}
                                                &nbsp; <i class="fa fas fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="{$hpPayment->getAuthorization()->getMessage()->getMerchant()}"></i>
                                            {/if}
                                        </div>
                                        <td class="hp-amount">{if isset($hpPayment->getAuthorization()->getAmount())}{number_format($hpPayment->getAuthorization()->getAmount(), 2)}{else} - {/if}{if isset($hpPayment->getAuthorization()->getCurrency())} {$hpPayment->getAuthorization()->getCurrency()}{/if}</td>
                                    </tr>
                                {/if}

                                {if !empty($hpPayment->getCharges())}
                                    {foreach from=$hpPayment->getCharges() item='charge' name='charges'}
                                        <tr {if !$smarty.foreach.charges.first} style="margin-top:10px;"{/if} class="{if $charge->isError()}danger{elseif $charge->isPending()}warning{else}success{/if}">
                                            <td>{$charge->getId()}</td>
                                            <td class="hp-short-id">{$charge->getShortId()}</td>
                                            <td class="hp-status">
                                                {if $charge->isPending()}
                                                    {__('hpStatePending')}
                                                {elseif $charge->isError()}
                                                    {__('hpStateFailure')}
                                                {elseif $charge->isSuccess()}
                                                    {__('hpStateSuccessful')}
                                                {else}
                                                    -
                                                {/if}

                                                {if $charge->getMessage()}
                                                    &nbsp; <i class="fa fas fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="{$charge->getMessage()->getMerchant()}"></i>
                                                {/if}
                                            </div>
                                            <td class="hp-amount">{if isset($charge->getAmount())}{number_format($charge->getAmount(), 2)}{else} - {/if}{if isset($charge->getCurrency())} {$charge->getCurrency()}{/if}</td>
                                        </tr>
                                    {/foreach}
                                {/if}
                            </tbody>
                        </table>
                    {else}
                        <div class="card-body panel-body text-center"><em>{__('hpEmptyResult')}</em></div>
                    {/if}
                </div>
            </div>

            {if !empty($hpPayment->getShipments())}
                <div class="col-12 col-xs-12">
                    <div class="card panel panel-default">
                        <div class="card-header panel-heading">
                            <h4 class="panel-title">Versandmeldungen</h4>
                        </div>

                        {* <div class="table-responsive"> *}
                            <table class="list table table-striped">
                                <thead>
                                <tr>
                                    <th class="tleft">{__('hpID')}</th>
                                    <th class="tleft">{__('hpShortID')}</th>
                                    <th class="tleft">{__('hpStatus')}</th>
                                    <th class="tleft">{__('hpInvoiceNumber')}</th>
                                </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$hpPayment->getShipments() item='shipment' name='shipments'}
                                        <tr {if !$smarty.foreach.shipments.first} style="margin-top:10px;"{/if} class="{if $shipment->isError()}danger{elseif $shipment->isPending()}warning{else}success{/if}">
                                            <td>{$shipment->getId()}</td>
                                            <td class="hp-short-id">{$shipment->getShortId()}</td>
                                            <td class="hp-status">
                                                {if $shipment->isPending()}
                                                    {__('hpStatePending')}
                                                {elseif $shipment->isError()}
                                                    {__('hpStateFailure')}
                                                {elseif $shipment->isSuccess()}
                                                    {__('hpStateSuccessful')}
                                                {else}
                                                    -
                                                {/if}

                                                {if $shipment->getMessage()}
                                                    &nbsp; <i class="fa fas fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="{$shipment->getMessage()->getMerchant()}"></i>
                                                {/if}
                                            </div>
                                            <td class="hp-invoice-id">{if isset($shipment->getInvoiceId())}{$shipment->getInvoiceId()}{else} - {/if}</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        {* </div> *}
                </div>
            {/if}

            {if !empty($hpCancellations)}
                <div class="col-12 col-xs-12">
                    <div class="card panel panel-default">
                        <div class="card-header panel-heading">
                            <h4 class="panel-title">{__('hpCancellations')}</h4>
                        </div>

                        {* <div class="table-responsive"> *}
                            <table class="list table table-striped">
                                <thead>
                                <tr>
                                    <th class="tleft">{__('hpID')}</th>
                                    <th class="tleft">{__('hpShortID')}</th>
                                    <th class="tleft">{__('hpStatus')}</th>
                                    <th class="tleft">{__('hpReference')}</th>
                                    <th class="tleft">{__('hpAmount')}</th>
                                </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$hpCancellations item='cancelation' name='cancelations'}
                                        <tr {if !$smarty.foreach.cancelations.first} style="margin-top:10px;"{/if} class="{if $cancelation->isError()}danger{elseif $cancelation->isPending()}warning{else}success{/if}">
                                            <td>{$cancelation->getId()}</td>
                                            <td class="hp-short-id">{$cancelation->getShortId()}</td>
                                            <td class="hp-status">
                                                {if $cancelation->isPending()}
                                                    {__('hpStatePending')}
                                                {elseif $cancelation->isError()}
                                                    {__('hpStateFailure')}
                                                {elseif $cancelation->isSuccess()}
                                                    {__('hpStateSuccessful')}
                                                {else}
                                                    -
                                                {/if}

                                                {if $cancelation->getMessage()}
                                                    &nbsp; <i class="fa fas fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="{$cancelation->getMessage()->getMerchant()}"></i>
                                                {/if}
                                            </div>
                                            <td class="hp-payment-ref">{if isset($cancelation->getPaymentReference())}{$cancelation->getPaymentReference()}{else} - {/if}</td>
                                            <td class="hp-invoice-id">{if isset($cancelation->getAmount())}{number_format($cancelation->getAmount(), 2)}{else} - {/if}</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        {* </div> *}
                </div>
            {/if}
        </div>
    </div>
{/strip}