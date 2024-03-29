{strip}
    <div class="hp-order-detail-wrapper">
        <div class="row">
            <div class="col-12 col-xs-12">
                <div class="input-group">
                    <div class="btn-group input-group-btn">
                        {if $hpPortalUrl}
                            <a class="btn btn-primary" title="{__('hpOpenInPortal')}" href="{$hpPortalUrl}" target="_blank">
                                {__('hpOpenInPortal')}
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

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

                                    <dt class="col-5">{__('hpInvoiceNumber')}</dt>
                                    <dd class="col-7">{$hpPayment->getInvoiceId()}</dd>

                                    <dt class="col-5">{__('hpPaymentId')}</dt>
                                    <dd class="col-7">{$hpPayment->getId()}</dd>

                                    <dt class="col-5">{__('hpPaymentMethod')}</dt>
                                    <dd class="col-7">
                                        {if $hpPayment->getPaymentType()->getResourceName() == 'card'}
                                            {__('hpPaymentmethodCard')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'sepa-direct-debit'}
                                            {__('hpPaymentmethodSEPA')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'sepa-direct-debit-guaranteed'}
                                            {__('hpPaymentmethodSEPAGuaranteed')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'sepa-direct-debit-secured'}
                                            {__('hpPaymentmethodSEPAGuaranteed')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'invoice'}
                                            {__('hpPaymentmethodInvoice')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'invoice-guaranteed'}
                                            {__('hpPaymentmethodInvoiceGuaranteed')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'invoice-secured'}
                                            {__('hpPaymentmethodInvoiceGuaranteed')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'invoice-factoring'}
                                            {__('hpPaymentmethodInvoiceFactoring')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'paypal'}
                                            {__('hpPaymentmethodPayPal')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'sofort'}
                                            {__('hpPaymentmethodSOFORT')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'giropay'}
                                            {__('hpPaymentmethodGiropay')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'prepayment'}
                                            {__('hpPaymentmethodPrepayment')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'eps'}
                                            {__('hpPaymentmethodEPS')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'pis'}
                                            {__('hpPaymentmethodFlexiPayDirect')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'alipay'}
                                            {__('hpPaymentmethodAlipay')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'wechatpay'}
                                            {__('hpPaymentmethodWeChatPay')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'ideal'}
                                            {__('hpPaymentmethodiDEAL')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'hire-purchase-direct-debit'}
                                            {__('hpPaymentmethodHirePurchaseDirectDebit')}
                                        {else if $hpPayment->getPaymentType()->getResourceName() == 'installment-secured'}
                                            {__('hpPaymentmethodHirePurchaseDirectDebit')}
                                        {else}
                                            {$hpPayment->getPaymentType()->getResourceName()}
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

                    {if !empty($hpPayment->getCharges())}
                    {* <div class="table-responsive"> *}
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
                                        <td class="hp-amount">{if isset($charge->getAmount())}{($charge->getAmount())|number_format:2}{else} - {/if}{if isset($charge->getCurrency())} {$charge->getCurrency()}{/if}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {* </div> *}
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

            {if !empty($hpPayment->getCancellations())}
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
                                    {foreach from=$hpPayment->getCancellations() item='cancelation' name='cancelations'}
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
                                            <td class="hp-invoice-id">{if isset($cancelation->getAmount())}{($cancelation->getAmount())|number_format:2}{else} - {/if}</td>
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