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
                        {* Hide last 16 chars to prevent misuse from unauthorized users *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-privateKey">
                                    {__('hpSettingsPrivateKeyLabel')}
                                    {if isset($hpSettings.config.privateKey)}
                                        <br/><small>{__('current')}: {substr($hpSettings.config.privateKey, 0, -16)}{str_repeat('&bull;', 16)}</small>
                                    {/if}
                                </label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="password" class="form-control" name="privateKey" id="hpSettings-privateKey" placeholder="{__('hpSettingsPrivateKeyPlaceholder')}" value="{if isset($hpSettings.config.privateKey)}{password_hash($hpSettings.config.privateKey, PASSWORD_BCRYPT)}{/if}" />
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

                {* UI Components *}
                <div class="panel panel-default card mb-3">
                    <div class="panel-heading card-title mx-4 mt-4">
                        <h3 class="panel-title">{__('hpSettingsUIComponents')}</h3>
                    </div>

                    <div class="panel-body card-body">
                        {* Font Family *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-fontFamily">{__('hpSettingsFontFamily')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="ui-font-family" id="hpSettings-ui-fontFamily" placeholder="{__('hpSettingsFontFamilyPlaceholder')}" value="{if isset($hpSettings.config.ui_fontFamily)}{$hpSettings.config.ui_fontFamily}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsFontFamilyHelp')}</small>
                            </div>
                        </div>

                        {* Text Color *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-TextColor">{__('hpSettingsTextColor')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="ui-text-color" id="hpSettings-ui-TextColor" placeholder="{__('hpSettingsTextColorPlaceholder')}" value="{if isset($hpSettings.config.ui_textColor)}{$hpSettings.config.ui_textColor}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsTextColorHelp')}</small>
                            </div>
                        </div>

                        {* Brand Color *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-BrandColor">{__('hpSettingsBrandColor')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="ui-brand-color" id="hpSettings-ui-BrandColor" placeholder="{__('hpSettingsBrandColorPlaceholder')}" value="{if isset($hpSettings.config.ui_brandColor)}{$hpSettings.config.ui_brandColor}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsBrandColorHelp')}</small>
                            </div>
                        </div>

                        {* Background Color *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-BackgroundColor">{__('hpSettingsBackgroundColor')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="ui-background-color" id="hpSettings-ui-BackgroundColor" placeholder="{__('hpSettingsBackgroundColorPlaceholder')}" value="{if isset($hpSettings.config.ui_backgroundColor)}{$hpSettings.config.ui_backgroundColor}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsBackgroundColorHelp')}</small>
                            </div>
                        </div>

                        {* Link Color *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-LinkColor">{__('hpSettingsLinkColor')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="ui-link-color" id="hpSettings-ui-LinkColor" placeholder="{__('hpSettingsLinkColorPlaceholder')}" value="{if isset($hpSettings.config.ui_linkColor)}{$hpSettings.config.ui_linkColor}{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsLinkColorHelp')}</small>
                            </div>
                        </div>

                        {* Corner Radius *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-CornerRadius">{__('hpSettingsCornerRadius')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="checkbox" class="checkbox" name="ui-corner-radius" id="hpSettings-ui-CornerRadius" {if isset($hpSettings.config.ui_cornerRadius)}checked{/if} />
                                <small class="form-text help-block text-muted">{__('hpSettingsCornerRadiusHelp')}</small>
                            </div>
                        </div>

                        {* Shadows *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-ui-Shadows">{__('hpSettingsShadows')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="checkbox" class="checkbox" name="ui-shadows" id="hpSettings-ui-Shadows" {if isset($hpSettings.config.ui_shadows)}checked{/if} />
                                <small class="form-text help-block text-muted">{__('hpSettingsShadowsHelp')}</small>
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

                        {* PQ Selector Instalment Information *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorInstalmentInfo">{__('hpSettingsPqSelectorInstalmentInfo')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorInstalmentInfo" id="hpSettings-pqSelectorInstalmentInfo" placeholder="#complete-order-button" value="{if isset($hpSettings.config.pqSelectorInstalmentInfo)}{$hpSettings.config.pqSelectorInstalmentInfo}{else}#complete-order-button{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorInstalmentInfoHelp')}</small>
                            </div>
                        </div>

                        {* PQ Method Instalment Information *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqMethodInstalmentInfo">{__('hpSettingsPqMethodInstalmentInfo')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <select class="form-control" name="pqMethodInstalmentInfo" id="hpSettings-pqMethodInstalmentInfo">
                                    <option value="append" {if isset($hpSettings.config.pqMethodInstalmentInfo) && $hpSettings.config.pqMethodInstalmentInfo == 'append'}selected{/if}>{__('hpSettingsAppend')}</option>
                                    <option value="prepend" {if isset($hpSettings.config.pqMethodInstalmentInfo) && $hpSettings.config.pqMethodInstalmentInfo == 'prepend'}selected{/if}>{__('hpSettingsPrepend')}</option>
                                    <option value="before" {if isset($hpSettings.config.pqMethodInstalmentInfo) && $hpSettings.config.pqMethodInstalmentInfo == 'before' || !isset($hpSettings.config.pqMethodInstalmentInfo)}selected{/if}>{__('hpSettingsBefore')}</option>
                                    <option value="after" {if isset($hpSettings.config.pqMethodInstalmentInfo) && $hpSettings.config.pqMethodInstalmentInfo == 'after'}selected{/if}>{__('hpSettingsAfter')}</option>
                                    <option value="replaceWith" {if isset($hpSettings.config.pqMethodInstalmentInfo) && $hpSettings.config.pqMethodInstalmentInfo == 'replaceWith'}selected{/if}>{__('hpSettingsReplace')}</option>
                                </select>
                                <small class="form-text help-block text-muted">{__('hpSettingsPqMethodInstalmentInfoHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector Bestellabschluss Form *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorOrderConfirmForm">{__('hpSettingsPqSelectorOrderConfirmForm')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorOrderConfirmForm" id="hpSettings-pqSelectorOrderConfirmForm" placeholder="#complete_order" value="{if isset($hpSettings.config.pqSelectorOrderConfirmForm)}{$hpSettings.config.pqSelectorOrderConfirmForm}{else}#complete_order{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorOrderConfirmFormHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector buy button *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorPlaceOrderButton">{__('hpSettingsPqSelectorPlaceOrderButton')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorPlaceOrderButton" id="hpSettings-pqSelectorPlaceOrderButton" placeholder="#complete-order-button" value="{if isset($hpSettings.config.pqSelectorPlaceOrderButton)}{$hpSettings.config.pqSelectorPlaceOrderButton}{else}#complete-order-button{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingsPqSelectorPlaceOrderButtonHelp')}</small>
                            </div>
                        </div>

                        {* PQ Selector Bestellabschluss Form *}
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqSelectorSavedPaymentData">{__('hpSettingspqSelectorSavedPaymentData')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <input type="text" class="form-control" name="pqSelectorSavedPaymentData" id="hpSettings-pqSelectorSavedPaymentData" placeholder=".account-data-item-orders" value="{if isset($hpSettings.config.pqSelectorSavedPaymentData)}{$hpSettings.config.pqSelectorSavedPaymentData}{else}.account-data-item-orders{/if}" />
                                <small class="form-text help-block text-muted">{__('hpSettingspqSelectorSavedPaymentDataHelp')}</small>
                            </div>
                        </div>
                        <div class="hp-admin-option row mb-2">
                            <div class="hp-admin-option__title col-xs-3 col-3">
                                <label for="hpSettings-pqMethodSavedPaymentData">{__('hpSettingspqMethodSavedPaymentData')}</label>
                            </div>
                            <div class="hp-admin-option__input col-xs-9 col-9">
                                <select class="form-control" name="pqMethodSavedPaymentData" id="hpSettings-pqMethodSavedPaymentData">
                                    <option value="append" {if isset($hpSettings.config.pqMethodSavedPaymentData) && $hpSettings.config.pqMethodSavedPaymentData == 'append'}selected{/if}>{__('hpSettingsAppend')}</option>
                                    <option value="prepend" {if isset($hpSettings.config.pqMethodSavedPaymentData) && $hpSettings.config.pqMethodSavedPaymentData == 'prepend'}selected{/if}>{__('hpSettingsPrepend')}</option>
                                    <option value="before" {if isset($hpSettings.config.pqMethodSavedPaymentData) && $hpSettings.config.pqMethodSavedPaymentData == 'before'}selected{/if}>{__('hpSettingsBefore')}</option>
                                    <option value="after" {if isset($hpSettings.config.pqMethodSavedPaymentData) && $hpSettings.config.pqMethodSavedPaymentData == 'after' || !isset($hpSettings.config.pqMethodSavedPaymentData)}selected{/if}>{__('hpSettingsAfter')}</option>
                                    <option value="replaceWith" {if isset($hpSettings.config.pqMethodSavedPaymentData) && $hpSettings.config.pqMethodSavedPaymentData == 'replaceWith'}selected{/if}>{__('hpSettingsReplace')}</option>
                                </select>
                                <small class="form-text help-block text-muted">{__('hpSettingspqMethodSavedPaymentDataHelp')}</small>
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