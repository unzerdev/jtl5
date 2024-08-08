(function ($, window, undefined) {
    $(function () {
        // Setup
        const admin = window.hpAdmin;
        const $keyPairs = $('.hp-keypairs');
        const $modal = $('#hp-keypair-modal');
        const $addTrigger = $('#hp-keypairs-add');

        /**
         *
         * @param {{template: ?String}} response
         */
        const setModalContent = (response) => {
            if (response && response.template) {
                const $body = $modal.find('.modal-body');

                var show = () => {
                    $body.html(response.template);
                    $modal.modal('show');
                };

                if ($modal.hasClass('modal-loaded')) {
                    $modal.modal('hide');
                    show();
                } else {
                    show();
                    $modal.addClass('modal-loaded');
                }
            }
        };

        /**
         * @param {JQuery.Event} e
         */
        const onDelete = (e) => {
            const form = $(e.target);
            const data = {
                id: form.closest('tr').data('id'),
                jtl_token: form.find('.jtl_token').val()
            };

            e.preventDefault();
            admin.doAjaxCall('KeyPairs:delete', data, (response) => {
                if (response && response.listing) {
                    $('#modal-footer-delete-confirm').modal('hide');
                    $keyPairs.html(response.listing);
                    deleteConfirmation();
                }
            });
        };

        /**
         * @param {JQuery.Event} e
         */
        const onAdd = (e) => {
            e.preventDefault();
            admin.doAjaxCall('KeyPairs:edit', { id: null }, setModalContent);
        };

        /**
         * @param {JQuery.Event} e
         */
        const onSave = (e) => {
            e.preventDefault();

            const fields = $(e.target).find('input,select');
            const data = {};
            fields.each((i, el) => {
                data[el.name] = $(el).is(':checkbox') ? $(el).is(':checked') : $(el).val();
            });

            admin.doAjaxCall('KeyPairs:save', data, (response) => {
                if (response.status !== 'success') {
                    return;
                }

                if (response.template) {
                    $modal.find('.modal-body').html(response.template);
                }

                if (response.listing) {
                    $keyPairs.html(response.listing);
                    deleteConfirmation();
                }
            });
        };

        /**
         * @param {JQuery.Event} e
         */
        const onEdit = (e) => {
            e.preventDefault();
            admin.doAjaxCall('KeyPairs:edit', { id: $(e.target).closest('tr').data('id') }, setModalContent);
        };

        // Register Events
        $keyPairs.on('submit', '.keypair-action-form', onDelete);
        $addTrigger.on('click', onAdd);
        $modal.find('form').on('submit', onSave);
        $keyPairs.on('click', '[data-edit]', onEdit);
    });
})(jQuery, window);