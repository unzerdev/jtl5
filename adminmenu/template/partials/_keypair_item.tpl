
<div class="panel-body card-body">

{include file="./_header.tpl"}

    <input type="hidden" name="id" value="{if isset($unzerKeypairs.item)}{$unzerKeypairs.item->getId()}{/if}" />

    {* Private Key *}
    <div class="hp-admin-option row mb-2">
        <div class="hp-admin-option__title col-xs-3 col-3">
            <label for="hpKeypair-privateKey">{__('hpSettingsPrivateKeyLabel')}</label>
        </div>
        <div class="hp-admin-option__input col-xs-9 col-9">
            {* Hash current private key to avoid leaking it *}
            <input type="password" class="form-control" name="privateKey" id="hpKeypair-privateKey"
                placeholder="{__('hpSettingsPrivateKeyPlaceholder')}"
                value="{if isset($unzerKeypairs.item)}{password_hash($unzerKeypairs.item->getPrivateKey(), PASSWORD_BCRYPT)}{/if}"
                required
            />
            <small class="form-text help-block text-muted">{__('hpSettingsPrivateKeyHelp')}</small>
        </div>
    </div>

    {* Public Key *}
    <div class="hp-admin-option row mb-2">
        <div class="hp-admin-option__title col-xs-3 col-3">
            <label for="hpKeypair-publicKey">{__('hpSettingsPublicKeyLabel')}</label>
        </div>
        <div class="hp-admin-option__input col-xs-9 col-9">
            <input type="text" class="form-control" name="publicKey" id="hpKeypair-publicKey"
                placeholder="{__('hpSettingsPublicKeyPlaceholder')}"
                value="{if isset($unzerKeypairs.item)}{$unzerKeypairs.item->getPublicKey()|escape}{/if}"
                required
            />
            <small class="form-text help-block text-muted">{__('hpSettingsPublicKeyHelp')}</small>
        </div>
    </div>

    {* Is B2B *}
    <div class="hp-admin-option row mb-2">
        <div class="hp-admin-option__title col-xs-3 col-3">
            <label for="hpKeypair-isB2B">{__('hpKeypairIsB2B')}</label>
        </div>
        <div class="hp-admin-option__input col-xs-9 col-9">
            <input type="checkbox" class="checkbox" name="isB2B" id="hpKeypair-isB2B" {if isset($unzerKeypairs.item) && $unzerKeypairs.item->isB2B()}checked{/if} />
            {__('yes')}
            <small class="form-text help-block text-muted">{__('hpKeypairIsB2BHelp')}</small>
        </div>
    </div>

    {* Currency *}
    <div class="hp-admin-option row mb-2">
        <div class="hp-admin-option__title col-xs-3 col-3">
            <label for="hpKeypair-currency">{__('currency')}</label>
        </div>
        <div class="hp-admin-option__input col-xs-9 col-9">
            <select class="form-control" name="currency" id="hpKeypair-currency">
                {foreach from=$unzerKeypairs.currencies item=item}
                    <option value="{$item.id}" {if isset($unzerKeypairs.item) && $unzerKeypairs.item->getCurrencyId() == $item.id}selected{/if}>{$item.name}</option>
                {/foreach}
            </select>
            <small class="form-text help-block text-muted">{__('hpKeypairCurrencyHelp')}</small>
        </div>
    </div>

    {* Payment Methods *}
    <div class="hp-admin-option row mb-2">
        <div class="hp-admin-option__title col-xs-3 col-3">
            <label for="hpKeypair-payment-methods">{__('hpPaymentMethod')}</label>
        </div>
        <div class="hp-admin-option__input col-xs-9 col-9">
            <select class="form-control" name="paymentMethod" id="hpKeypair-payment-methods">
                {foreach from=$unzerKeypairs.paymentMethods item=item}
                    {if $item->getActive()}
                        <option value="{$item->getMethodID()}" {if isset($unzerKeypairs.item) && $unzerKeypairs.item->getPaymentMethodId() == $item->getMethodID()}selected{/if}>{__($item->getName())}</option>
                    {/if}
                {/foreach}
            </select>
            <small class="form-text help-block text-muted">{__('hpKeypairPaymentMethodsHelp')}</small>
        </div>
    </div>
</div>