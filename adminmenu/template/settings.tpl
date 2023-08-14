{include file="{$hpAdmin.adminTemplatePath}partials/_header.tpl"}

<div class="hp-admin-content">
    <div class="row">
        <div class="col-xs-12 col-12">
            <form action="{$hpSettings.formAction}" method="post">
                {$jtl_token}

                {* API Auth *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpSettingsAPIAccessData')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* Private Key *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-privateKey">{__('hpSettingsPrivateKeyLabel')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="privateKey" id="hpSettings-privateKey" placeholder="{__('hpSettingsPrivateKeyPlaceholder')}" value="{if isset($hpSettings.config.privateKey)}{$hpSettings.config.privateKey}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPrivateKeyHelp')}</small>
                            </div>
                        </div>

                        {* Public Key *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-publicKey">{__('hpSettingsPublicKeyLabel')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="publicKey" id="hpSettings-publicKey" placeholder="{__('hpSettingsPublicKeyPlaceholder')}" value="{if isset($hpSettings.config.publicKey)}{$hpSettings.config.publicKey}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPublicKeyHelp')}</small>
                            </div>
                        </div>

                        {* hIP / Insight Merchant ID *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-merchantId">{__('hpSettingsMerchantIdLabel')}</small></label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="merchantId" id="hpSettings-merchantId" value="{if isset($hpSettings.config.merchantId)}{$hpSettings.config.merchantId}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsMerchantIdHelp')}</small>
                            </div>
                        </div>
                    </div>
                </div>

                {* Styles *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpSettingsStyles')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* Font Size *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-fontSize">{__('hpSettingsFontSize')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="fontSize" id="hpSettings-fontSize" placeholder="{__('hpSettingsFontSizePlaceholder')}" value="{if isset($hpSettings.config.fontSize)}{$hpSettings.config.fontSize}{/if}" />
                            </div>
                        </div>

                        {* Font Color *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-fontColor">{__('hpSettingsFontColor')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control form-colored" name="fontColor" id="hpSettings-fontColor" placeholder="{__('hpSettingsFontColorPlaceholder')}" value="{if isset($hpSettings.config.fontColor)}{$hpSettings.config.fontColor}{/if}" />
                            </div>
                        </div>

                        {* Font Family *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-fontFamily">{__('hpSettingsFontFamily')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="fontFamily" id="hpSettings-fontFamily" placeholder="{__('hpSettingsFontFamilyPlaceholder')}" value="{if isset($hpSettings.config.fontFamily)}{$hpSettings.config.fontFamily}{/if}" />
                            </div>
                        </div>
                    </div>
                </div>

                {* Advanced Settings *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpSettingsExpert')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* Add Incming Payments *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-addIncomingPayments">{__('hpSettingsAddIncomingPayments')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="checkbox" class="checkbox" name="addIncomingPayments" id="hpSettings-addIncomingPayments" {if !isset($hpSettings.config.addIncomingPayments) || $hpSettings.config.addIncomingPayments}checked{/if} />
                                {__('hpSettingsActive')}
                                <small class="form-text help-block text-muted">{__('hpSettingsAddIncomingPaymentsHelp')}</small>
                            </div>
                        </div>


                        {* PQ Selector Submit Button *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-selectorSubmitButton">{__('hpSettingsSelectorSubmitButton')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="selectorSubmitButton" id="hpSettings-selectorSubmitButton" placeholder="#form_payment_extra .submit, #form_payment_extra .submit_once" value="{if isset($hpSettings.config.selectorSubmitButton)}{$hpSettings.config.selectorSubmitButton}{else}#form_payment_extra .submit, #form_payment_extra .submit_once{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsSelectorSubmitButtonHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector Change Payment Method *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorChangePaymentMethod">{__('hpSettingsPqSelectorChangePaymentMethod')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorChangePaymentMethod" id="hpSettings-pqSelectorChangePaymentMethod" placeholder="#order-additional-payment" value="{if isset($hpSettings.config.pqSelectorChangePaymentMethod)}{$hpSettings.config.pqSelectorChangePaymentMethod}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorChangePaymentMethodHelp')}</small>
                            </div>
                        </div>

                        {* PQ Method Change Payment Method *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqMethodChangePaymentMethod">{__('hpSettingsPqMethodChangePaymentMethod')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <select class="form-control" name="pqMethodChangePaymentMethod" id="hpSettings-pqMethodChangePaymentMethod">
                                    <option value="append" {if isset($hpSettings.config.pqMethodChangePaymentMethod) && $hpSettings.config.pqMethodChangePaymentMethod == 'append'}selected{/if}>{__('hpSettingsAppend')}</option>
                                    <option value="prepend" {if isset($hpSettings.config.pqMethodChangePaymentMethod) && $hpSettings.config.pqMethodChangePaymentMethod == 'prepend'}selected{/if}>{__('hpSettingsPrepend')}</option>
                                    <option value="before" {if isset($hpSettings.config.pqMethodChangePaymentMethod) && $hpSettings.config.pqMethodChangePaymentMethod == 'before'}selected{/if}>{__('hpSettingsBefore')}</option>
                                    <option value="after" {if isset($hpSettings.config.pqMethodChangePaymentMethod) && $hpSettings.config.pqMethodChangePaymentMethod == 'after'}selected{/if}>{__('hpSettingsAfter')}</option>
                                    <option value="replaceWith" {if isset($hpSettings.config.pqMethodChangePaymentMethod) && $hpSettings.config.pqMethodChangePaymentMethod == 'replaceWith'}selected{/if}>{__('hpSettingsReplace')}</option>
                                </select>
                                <small class="form-text help-block text-muted">{__('hpSettingsPqMethodChangePaymentMethodHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector Errors *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorErrors">{__('hpSettingsPqSelectorErrors')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorErrors" id="hpSettings-pqSelectorErrors" placeholder="#result-wrapper, .basket_wrapper, .order-completed" value="{if isset($hpSettings.config.pqSelectorErrors)}{$hpSettings.config.pqSelectorErrors}{else}#result-wrapper, .basket_wrapper{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorErrorsHelp')}</small>
                            </div>
                        </div>

                        {* PQ Method Errors *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqMethodErrors">{__('hpSettingsPqMethodErrors')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <select class="form-control" name="pqMethodErrors" id="hpSettings-pqMethodErrors">
                                    <option value="prepend" {if isset($hpSettings.config.pqMethodErrors) && $hpSettings.config.pqMethodErrors == 'prepend'}selected{/if}>{__('hpSettingsPrepend')}</option>
                                    <option value="append" {if isset($hpSettings.config.pqMethodErrors) && $hpSettings.config.pqMethodErrors == 'append'}selected{/if}>{__('hpSettingsAppend')}</option>
                                    <option value="before" {if isset($hpSettings.config.pqMethodErrors) && $hpSettings.config.pqMethodErrors == 'before'}selected{/if}>{__('hpSettingsBefore')}</option>
                                    <option value="after" {if isset($hpSettings.config.pqMethodErrors) && $hpSettings.config.pqMethodErrors == 'after'}selected{/if}>{__('hpSettingsAfter')}</option>
                                    <option value="replaceWith" {if isset($hpSettings.config.pqMethodErrors) && $hpSettings.config.pqMethodErrors == 'replaceWith'}selected{/if}>{__('hpSettingsReplace')}</option>
                                </select>
                                <small class="form-text help-block text-muted">{__('hpSettingsPqMethodErrorsHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector ReviewStep *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorReviewStep">{__('hpSettingsPqSelectorReviewStep')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorReviewStep" id="hpSettings-pqSelectorReviewStep" placeholder="#order-confirm" value="{if isset($hpSettings.config.pqSelectorReviewStep)}{$hpSettings.config.pqSelectorReviewStep}{else}#order-confirm{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorReviewStepHelp')}</small>
                            </div>
                        </div>

                        {* PQ Method ReviewStep *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqMethodReviewStep">{__('hpSettingsPqMethodReviewStep')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <select class="form-control" name="pqMethodReviewStep" id="hpSettings-pqMethodReviewStep">
                                    <option value="prepend" {if isset($hpSettings.config.pqMethodReviewStep) && $hpSettings.config.pqMethodReviewStep == 'prepend'}selected{/if}>{__('hpSettingsPrepend')}</option>
                                    <option value="append" {if isset($hpSettings.config.pqMethodReviewStep) && $hpSettings.config.pqMethodReviewStep == 'append'}selected{/if}>{__('hpSettingsAppend')}</option>
                                    <option value="before" {if isset($hpSettings.config.pqMethodReviewStep) && $hpSettings.config.pqMethodReviewStep == 'before'}selected{/if}>{__('hpSettingsBefore')}</option>
                                    <option value="after" {if isset($hpSettings.config.pqMethodReviewStep) && $hpSettings.config.pqMethodReviewStep == 'after'}selected{/if}>{__('hpSettingsAfter')}</option>
                                    <option value="replaceWith" {if isset($hpSettings.config.pqMethodReviewStep) && $hpSettings.config.pqMethodReviewStep == 'replaceWith'}selected{/if}>{__('hpSettingsReplace')}</option>
                                </select>
                                <small class="form-text help-block text-muted">{__('hpSettingsPqMethodReviewStepHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector Payment Information *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorPaymentInformation">{__('hpSettingsPqSelectorPaymentInformation')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorPaymentInformation" id="hpSettings-pqSelectorPaymentInformation" placeholder="#order-confirmation .card-body" value="{if isset($hpSettings.config.pqSelectorPaymentInformation)}{$hpSettings.config.pqSelectorPaymentInformation}{else}#order-confirmation .card-body{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorPaymentInformationHelp')}</small>
                            </div>
                        </div>

                        {* PQ Method Payment Information *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqMethodPaymentInformation">{__('hpSettingsPqMethodPaymentInformation')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <select class="form-control" name="pqMethodPaymentInformation" id="hpSettings-pqMethodPaymentInformation">
                                    <option value="append" {if isset($hpSettings.config.pqMethodPaymentInformation) && $hpSettings.config.pqMethodPaymentInformation == 'append'}selected{/if}>{__('hpSettingsAppend')}</option>
                                    <option value="prepend" {if isset($hpSettings.config.pqMethodPaymentInformation) && $hpSettings.config.pqMethodPaymentInformation == 'prepend'}selected{/if}>{__('hpSettingsPrepend')}</option>
                                    <option value="before" {if isset($hpSettings.config.pqMethodPaymentInformation) && $hpSettings.config.pqMethodPaymentInformation == 'before'}selected{/if}>{__('hpSettingsBefore')}</option>
                                    <option value="after" {if isset($hpSettings.config.pqMethodPaymentInformation) && $hpSettings.config.pqMethodPaymentInformation == 'after'}selected{/if}>{__('hpSettingsAfter')}</option>
                                    <option value="replaceWith" {if isset($hpSettings.config.pqMethodPaymentInformation) && $hpSettings.config.pqMethodPaymentInformation == 'replaceWith'}selected{/if}>{__('hpSettingsReplace')}</option>
                                </select>
                                <small class="form-text help-block text-muted">{__('hpSettingsPqMethodPaymentInformationHelp')}</small>
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
                                    <button class="btn btn-primary" type="submit" name="saveSettings" value="1"><i class="fa fa-save"></i>&nbsp; {__('hpSettingsSave')}</button>

                                    {if isset($hpSettings.webhooks) && $hpSettings.webhooks}
                                        <button class="btn btn-info pull-right float-right" type="submit" name="registerWebhooks" value="1"><i class="fa fa-refresh"></i>&nbsp; {__('hpSettingsRegisterWebhooks')}</button>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>