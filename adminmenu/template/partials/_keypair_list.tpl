{foreach from=$unzerKeypairs.items item=$item}
    <tr data-id="{$item->getId()}">
        <td>
            {* Hide last 16 chars to prevent misuse from unauthorized users *}
            {substr($item->getPrivateKey(), 0, -16)}{str_repeat('&bull;', 16)}
        </td>
        <td>
            {$item->getPublicKey()}
        </td>
        <td>
            {if $item->isB2B()}
                <i class="fal fa-check text-success"></i>
            {else}
                <i class="fal fa-times text-danger"></i>
            {/if}
        </td>
        <td>
            {if isset($unzerKeypairs.currencies[$item->getCurrencyId()])}
                {$unzerKeypairs.currencies[$item->getCurrencyId()].name}
            {/if}
        </td>
        <td>
            {if isset($unzerKeypairs.paymentMethods[$item->getPaymentMethodId()])}
                {__($unzerKeypairs.paymentMethods[$item->getPaymentMethodId()]->getName())}
            {/if}
        </td>
        <td>
            <form method="POST" class="keypair-action-form">
                {$jtl_token}

                <button type="button" class="btn btn-xs btn-default" data-edit="true" data-toggle="tooltip" title="{__('edit')}">
                    <i class="fa fas fa-pen fa-pencil" aria-hidden="true"></i>
                </button>

                <button type="submit" class="btn btn-xs btn-danger delete-confirm"
                    data-modal-body="{__('Wollen Sie den Eintrag wirklich löschen?')}"
                    data-delete="true"
                    data-toggle="tooltip"
                    title="{__('delete')}"
                >
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>
            </form>
        </td>
    </tr>
{foreachelse}
    <tr>
        <td class="text-center" colspan="6">{__('noData')}</td>
    </tr>
{/foreach}