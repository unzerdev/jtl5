(function ($, window, undefined) {
    /**
     * Load Orders Callback.
     *
     * @this {OrderManagement}
     * @param {Object} response response Objects with orders
     * @param {Array} response.data List of orders
     */
    var _loadOrdersCallback = function (response) {
        if (response.data && response.data.length) {
            this._renderOrders(response.data);
            this._updateCurrentPageIndicator();
        } else if (response.data && !response.data.length && this.currentPageIndex > 0) {
            // end reached, go back. (Check for > 0 is needed to avoid endless loop for globally zero orders)
            this.prevPage();
        }
    };

    /**
     * Search Orders Callback.
     *
     * @this {OrderManagement}
     * @param {String} parameter The search string
     * @param {Object} response response Object with Orders
     * @param {Array} response.data List of orders
     */
    var _searchOrdersCallback = function (parameter, response) {
        if (response.data && response.data.length) {
            this._renderOrders(response.data);
        } else {
            this.admin.renderAlert(window._HP_SNIPPETS_.empty_search_result.replace('%searchTerm%', parameter), 'warning');
        }
    };

    /**
     * Display the order view
     * @this {OrderManagement}
     * @param {Object} response response Object with Orders
     * @param {Object} response.data The order details to display
     * @param {String} response.data.kBestellung The order id
     * @param {Object} response.data.order The mapped order
     * @param {String} response.data.order.jtlOrderNumber The order number
     * @param {String} response.data.view The order detail view
     */
    var _getDetailsCallback = function (response) {
        if (response.data && response.data.view) {
            var $title = this.$detailsContainer.find('.modal-title');
            var $body = this.$detailsContainer.find('.modal-body');
            var $container = this.$detailsContainer;
            var show = function () {
                $title.find('em').html(response.data.order.jtlOrderNumber);
                $title.html($title.html().replace('%orderId%', response.data.order.jtlOrderNumber));
                $body.html(response.data.view);
                $container.modal('show');
            };

            if ($container.hasClass('modal-loaded')) {
                $container.modal('hide');

                if (!$container.hasClass('is_hidden')) {
                    $container.one('hidden.bs.modal', show);
                } else {
                    show();
                }
            } else {
                show();
                $container.addClass('modal-loaded');
            }
        }
    };

    /**
     * Handles the order management
     *
     * @class
     * @constructor
     * @param {HeidelpayAdmin} admin Admin Class
     */
    function OrderManagement(admin) {
        var self = this;
        this.admin = admin;

        /** @property {number} currentPageIndex */
        this.currentPageIndex = 0;

        /** @property {jQuery<HTMLElement>} $currentPageIndicator */
        this.$currentPageIndicator = $('.hp-current-page-indicator');

        /** @property {jQuery<HTMLElement>} $ordersContainer */
        this.$ordersContainer = $('.hp-orders');

        /** @property {jQuery<HTMLElement>} $detailsContainer */
        this.$detailsContainer = $('#hp-order-detail-modal');

        /** @property {bool} $detailsContainer */
        this.onDetailPage = false;

        /** @property {{pageLimit: number}} options */
        this.options = {
            pageLimit: 100
        };

        /**
         * Modal instantly sets focus to the bs modal if focus is given to an element that isn't inside it
         * -> disable it
         */
        this.$detailsContainer.on('shown.bs.modal', function () {
            $(document).off('focusin.modal'); // Bootstarap 3
            $.fn.modal.Constructor.prototype._enforceFocus = function () { }; // Bootstrap 4
            self.onDetailPage = true;
            self.$detailsContainer.removeClass('is_hidden');
        });

        this.$detailsContainer.on('hidden.bs.modal', function () {
            self.onDetailPage = false;
            self.$detailsContainer.addClass('is_hidden');
            self._loadOrders();
        });

        this._loadOrders();
    };

    /**
    * Go to the next page.
    */
    OrderManagement.prototype.nextPage = function () {
        this.currentPageIndex = this.currentPageIndex + 1;
        this._loadOrders();
    };

    /**
     * Go to the previous page.
     */
    OrderManagement.prototype.prevPage = function () {
        this.currentPageIndex = Math.max(this.currentPageIndex - 1, 0);
        this._loadOrders();
    };

    /**
     * Go to the first page.
     */
    OrderManagement.prototype.firstPage = function () {
        this.currentPageIndex = 0;
        this._loadOrders();
    };

    /**
     * Update the current page indicator.
     */
    OrderManagement.prototype._updateCurrentPageIndicator = function () {
        this.$currentPageIndicator.text(this.currentPageIndex + 1);
    };

    /**
     * Loads detail data for the given order id.
     * @param {String} orderId
     */
    OrderManagement.prototype.getDetails = function (orderId) {
        this.admin.doAjaxCall('OrderManagement:getOrderDetails', { orderId: orderId }, _getDetailsCallback.bind(this));
    };

    /**
     * Search orders by either JTL order number or heidelpay payment id.
     * @param {String} parameter
     */
    OrderManagement.prototype.search = function (parameter) {
        // Show all orders when searchParameter is not set
        if (!parameter || parameter === '') {
            this._loadOrders();
            return;
        }

        var params = {
            search: parameter
        };

        this.admin.doAjaxCall('OrderManagement:loadOrders', params, _searchOrdersCallback.bind(this, parameter), null);
    };

    /**
     * Load orders and update the view.
     */
    OrderManagement.prototype._loadOrders = function () {
        var offset = Math.floor(this.currentPageIndex * this.options.pageLimit);
        var params = {
            offset: offset,
            limit: this.options.pageLimit
        };

        this.admin.doAjaxCall('OrderManagement:loadOrders', params, _loadOrdersCallback.bind(this), null);
    };

    /**
     * Render the orders to the page
     * @param {Array<{view: string}>} orders
     */
    OrderManagement.prototype._renderOrders = function (orders) {
        var self = this;
        this.$ordersContainer.html('');
        orders.forEach(function (order) {
            self.$ordersContainer.append(order.view || '');
        });
    };

    // Init Order Management
    $(document).ready(function () {
        window.hpOrderManagement = new OrderManagement(window.hpAdmin);
    });
})(jQuery, window);