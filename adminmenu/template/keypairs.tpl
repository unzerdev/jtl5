{include file="{$hpAdmin.adminTemplatePath}partials/_header.tpl"}

<div class="hp-admin-content">
    <div class="table-responsive">
        <table class="list table table-striped">
            <thead>
                <tr>
                    <th class="tleft">{__('hpSettingsPrivateKeyLabel')}</th>
                    <th class="tleft">{__('hpSettingsPublicKeyLabel')}</th>
                    <th class="tleft">{__('hpKeypairIsB2B')}</th>
                    <th class="tleft">{__('currency')}</th>
                    <th class="tleft">{__('hpPaymentMethod')}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="hp-keypairs">
                {include file="./partials/_keypair_list.tpl"}
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="hp-keypair-modal" data-backdrop="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-xl" role="document">
        <form class="modal-content" action="POST">
            <div class="modal-header">
                <h4 class="modal-title">{__('hpKeyPairEdit')}</h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">{__('save')}</button>
                {$jtl_token}
            </div>
        </form>
    </div>
</div>

<div class="save-wrapper">
    <div class="row">
        <div class="ml-auto col-xl-auto">
            <button id="hp-keypairs-add" class="btn btn-primary btn-block">
                <i class="far fa-plus"></i> {__('Hinzufügen')}
            </button>
        </div>
    </div>
</div>