(function ($, window, undefined) {
    /**
     * Get overlay text color based on background color.
     * @param {String} color
     */
    var overlayColor = function (color) {
        // if only first half of color is defined, repeat it
        if (color.length < 5) {
            color += color.slice(1);
        }
        return (color.replace('#', '0x')) > (0xffffff / 1.5) ? '#555' : '#fff';
    };

    /**
     * Change Background color to preview hex value.
     */
    var previewColor = function () {
        var $input = $(this);
        var val = $input.val();

        if (val.length != 4 && val.length != 7) {
            return;
        }

        $input.css({
            'background': val,
            'color': overlayColor(val)
        });
    };

    /**
     * Init Heidelpay Admin
     * @param {String} ajaxUrl URL to admin ajax controller
     */
    var HeidelpayAdmin = function (ajaxUrl) {
        this.ajaxUrl = ajaxUrl;
        this.$content = $('.hp-admin-content');
    };

    /**
     * Do an ajax call
     * @param {String} action Name of the action that should be called
     * @param {Array} parameters Parameters that are submitted to the
     * @param {Function} successCallback Called if the request returns a successfull result
     * @param {Function} failureCallback Called if the request returns an error result
     */
    HeidelpayAdmin.prototype.doAjaxCall = function (action, parameters, successCallback, failureCallback) {
        this.$content.addClass('hp-ajax-loading');
        var self = this;
        var routing = action.split(':');
        var controller = routing.length == 2 ? routing[0] : 'OrderManagement';
        var _action = routing.length == 2 ? routing[1] : routing[0];

        var request = $.ajax({
            url: this.ajaxUrl + '&controller=' + controller + '&action=' + _action,
            type: "post",
            dataType: "json",
            data: parameters
        });

        request.done(function (data) {
            if (data.status === 'success') {
                if (typeof successCallback === 'function') {
                    successCallback(data);
                }
            } else {
                if (typeof failureCallback === 'function') {
                    failureCallback(data);
                }

                if (data.messages) {
                    var msg = '';
                    data.messages.forEach(function (value) {
                        msg += '<br/>' + value;
                    });

                    self.renderAlert('<b>' + window._HP_SNIPPETS_.error + ':</b> ' + msg, 'danger');
                }
            }
        });

        request.fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Failed: ' + jqXHR + "," + textStatus + "," + errorThrown);
        });

        request.always(function () {
            self.$content.removeClass('hp-ajax-loading');
        });
    };

    /**
     * Render an alert on the page
     * @param {String} message
     * @param {String} type Either Info, Warning, Danger, Success
     */
    HeidelpayAdmin.prototype.renderAlert = function (message, type) {
        if (type == undefined) {
            type = 'info';
        }

        $('.alert-dismissable-container').remove();
        $('.tab-content .tab-pane.active .heidelpay-admin-header > .row').append(
            '<div class="col-xs-12 col-12 alert-dismissable-container">' +
                '<div class="alert alert-' + type + ' alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
                        message +
                '</div>' +
            '</div>'
        );
    };

    $(function () {
        // Colored Input
        $('.form-colored').each(previewColor).on('change keyup blur input focus', previewColor);
        window.hpAdmin = new HeidelpayAdmin(window.hpAdminAjaxUrl);

        $('.hp-admin-content .custom-file-input').on('change',function(e) {
            $(this).next('.custom-file-label').html(e.target.files[0].name);
        });
    });
})(jQuery, window);