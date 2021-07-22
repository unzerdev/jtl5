<div class="heidelpay-admin-header">
    <div class="row">
        {if !empty($hpErrors)}
            {foreach $hpErrors as $error}
                <div class="col-xs-12 col-12">
                    <div class="alert alert-danger"><b>{__('hpError')}:</b> {$error}</div>
                </div>
            {/foreach}
        {/if}
        {if !empty($hpWarnings)}
            {foreach $hpWarnings as $warning}
                <div class="col-xs-12 col-12">
                    <div class="alert alert-warning"><b>{__('hpWarning')}:</b> {$warning}</div>
                </div>
            {/foreach}
        {/if}
        {if !empty($hpMessages)}
            {foreach $hpMessages as $message}
                <div class="col-xs-12 col-12">
                    <div class="alert alert-info"><b>{__('hpInfo')}:</b> {$message}</div>
                </div>
            {/foreach}
        {/if}
        {if !empty($hpSuccesses)}
            {foreach $hpSuccesses as $success}
                <div class="col-xs-12 col-12">
                    <div class="alert alert-success"><b>{__('hpSuccess')}:</b> {$success}</div>
                </div>
            {/foreach}
        {/if}
    </div>
</div>