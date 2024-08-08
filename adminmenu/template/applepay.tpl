{include file="{$hpAdmin.adminTemplatePath}partials/_header.tpl"}

<div class="hp-admin-content">
    <div class="row">
        <div class="col-xs-12 col-12">
            <form action="{$hpSettings.formAction}" method="post" enctype="multipart/form-data">
                {$jtl_token}

                {* General Config Data *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpApplePayPaymentGeneral')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* Merchant ID *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-merchant-id">{__('hpApplePayPaymentGeneralMerchantId')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="hpSettings-applepay-merchant-id" id="hpSettings-applepay-merchant-id" value="{if isset($hpSettings.merchantId)}{$hpSettings.merchantId}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpApplePayPaymentGeneralMerchantIdHelp')}</small>
                            </div>
                        </div>

                        {* Merchant Domain *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-merchant-domain">{__('hpApplePayPaymentGeneralMerchantDomain')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="hpSettings-applepay-merchant-domain" id="hpSettings-applepay-merchant-domain" value="{if isset($hpSettings.merchantDomain)}{$hpSettings.merchantDomain}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpApplePayPaymentGeneralMerchantDomainHelp')}</small>
                            </div>
                        </div>
                    </div>
                </div>

                {* Payment Processing *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpApplePayPaymentProcessingCertificate')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* CSR*}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-payment-csr">{__('hpApplePayPaymentProcessingCertificateCSR')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-4">
                                <textarea class="form-control" rows="5" disabled id="hpSettings-applepay-payment-csr">{if isset($hpSettings.certs.applepay_payment_csr)}{$hpSettings.certs.applepay_payment_csr}{/if}</textarea>
                                <small class="form-text help-block text-muted">{__('hpApplePayPaymentProcessingCertificateCSRHelp')}</small>
                            </div>
                            <div class="hp-admin-option__actions col-xs-3 col-5">
                                <button type="submit" name="refreshPaymentProcessing" value="1" class="btn btn-info">
                                    <i class="fa fa-refresh"></i>&nbsp; {__('hpApplePayRefreshCert')}
                                </button>
                                <a href="{$hpSettings.pluginUrl}&download=payment_processing_certification" target="_blank" class="btn btn-info">
                                    <i class="fa fa-download"></i>&nbsp; {__('hpApplePayDownloadCert')}
                                </a>
                            </div>
                        </div>

                        {* Apple Cert *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-payment-cert">{__('hpApplePayPaymentProcessingCertificateApple')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-4">
                                <textarea class="form-control" rows="5" disabled id="hpSettings-applepay-payment-cert">{if isset($hpSettings.certs.applepay_payment_signed_pem)}{$hpSettings.certs.applepay_payment_signed_pem}{/if}</textarea>
                                <small class="form-text help-block text-muted">{__('hpApplePayPaymentProcessingCertificateAppleHelp')}</small>
                            </div>
                            <div class="hp-admin-option__actions col-xs-3 col-5">
                                <div class="input-group mb-3">
                                    <div class="custom-file">
                                        <input type="file" accept=".cer" name="hpSettings-applepay-payment-upload" class="custom-file-input" id="hpSettings-applepay-payment-upload-field" aria-describedby="hpSettings-applepay-payment-upload" />
                                        <label class="custom-file-label" for="hpSettings-applepay-payment-upload-field">{__('hpApplePayUploadCert')}</label>
                                    </div>

                                    <div class="input-group-append">
                                        <label for="hpSettings-applepay-payment-upload-field" class="input-group-text bg-primary text-white" id="hpSettings-applepay-payment-upload">
                                            <i class="fas fa-upload"></i>&nbsp; {__('hpApplePayUpload')}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {* Unzer IDs *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-unzer-ids">{__('hpApplePayUnzerIDs')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-4">
                                <input type="text" class="form-control mb-3" disabled name="hpSettings-applepay-unzer-cert-id" id="hpSettings-applepay-unzer-cert-id" value="{if isset($hpSettings.unzerCertificateId)}{$hpSettings.unzerCertificateId}{/if}" />
                                <input type="text" class="form-control mb-3" disabled name="hpSettings-applepay-unzer-privkey-id" id="hpSettings-applepay-unzer-privkey-id" value="{if isset($hpSettings.unzerPrivateKeyId)}{$hpSettings.unzerPrivateKeyId}{/if}" />
                            </div>
                            <div class="hp-admin-option__actions col-xs-3 col-5">
                                <button type="submit" name="activateCertificate" value="1" class="btn btn-info">
                                    <i class="fa fa-refresh"></i>&nbsp; {__('hpApplePayUpdateCert')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {* Merchant Identity *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpApplePayMerchantIdentityCertificate')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* CSR *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-merchant-csr">{__('hpApplePayMerchantIdentityCertificateCSR')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-4">
                                <textarea class="form-control" rows="5" disabled id="hpSettings-applepay-merchant-csr">{if isset($hpSettings.certs.applepay_merchant_csr)}{$hpSettings.certs.applepay_merchant_csr}{/if}</textarea>
                                <small class="form-text help-block text-muted">{__('hpApplePayMerchantIdentityCertificateCSRHelp')}</small>
                            </div>
                            <div class="hp-admin-option__actions col-xs-3 col-5">
                                <button type="submit" name="refreshMerchantValidation" value="1" class="btn btn-info">
                                    <i class="fa fa-refresh"></i>&nbsp; {__('hpApplePayRefreshCert')}
                                </button>
                                <a href="{$hpSettings.pluginUrl}&download=merchant_identity_certification" target="_blank" class="btn btn-info">
                                    <i class="fa fa-download"></i>&nbsp; {__('hpApplePayDownloadCert')}
                                </a>
                            </div>
                        </div>

                        {* Apple Cert *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-applepay-merchant-cert">{__('hpApplePayMerchantIdentityCertificateApple')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-4">
                                <textarea class="form-control" rows="5" disabled id="hpSettings-applepay-merchant-cert">{if isset($hpSettings.certs.applepay_merchant_signed_pem)}{$hpSettings.certs.applepay_merchant_signed_pem}{/if}</textarea>
                                <small class="form-text help-block text-muted">{__('hpApplePayMerchantIdentityCertificateAppleHelp')}</small>
                            </div>
                            <div class="hp-admin-option__actions col-xs-3 col-5">
                                <div class="input-group mb-3">
                                    <div class="custom-file">
                                        <input type="file" accept=".cer" name="hpSettings-applepay-merchant-upload" class="custom-file-input" id="hpSettings-applepay-merchant-upload-field" aria-describedby="hpSettings-applepay-merchant-upload" />
                                        <label class="custom-file-label" for="hpSettings-applepay-merchant-upload-field">{__('hpApplePayUploadCert')}</label>
                                    </div>

                                    <div class="input-group-append">
                                        <label for="hpSettings-applepay-merchant-upload-field" class="input-group-text bg-primary text-white" id="hpSettings-applepay-merchant-upload">
                                            <i class="fas fa-upload"></i>&nbsp; {__('hpApplePayUpload')}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {* Save Button *}
                <div class="panel panel-default card mb-3">
                    <div class="card-body">
                        <div class="panel-body card-body">
                            <div class="hp-admin-option row mb-2">
                                <div class="hp-admin-option__title col-xs-12 col-12">
                                    <button class="btn btn-primary" type="submit" name="saveApplePaySettings" value="1"><i class="fa fa-save"></i>&nbsp; {__('hpSettingsSave')}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>