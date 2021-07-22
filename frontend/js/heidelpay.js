(function ($, window, document, undefined) {
    /**
     * Heidelpay Payment Class
     *
     * @class
     * @constructor
     * @param {string} pubKey Public Key
     * @param {string} type Payment Type
     * @param {{
           $errorHolder: ?jQuery<HTMLElement>,
           $form: ?jQuery<HTMLElement>,
           submitButton: ?HTMLElement,
           locale: ?string,
           customerId: ?string,
           customer: ?object,
           autoSubmit: ?boolean,
           amount: null|string|number,
           currency: ?string,
           effectiveInterest: null|string|number,
           orderDate: ?string,
           styling: {fontSize: string, fontFamily: string, fontColor: string},
           isB2B: ?boolean
       }} settings Settings
     */
    function HeidelpayPayment(pubKey, type, settings) {
        this.settings = settings || {};

        var options = {
            locale: this.settings.locale || 'de-DE'
        };

        /** @type {unzer} unzerInstance */
        this.unzerInstance = new unzer(pubKey, options);

        /** @type {?string} customerId */
        this.customerId = settings.customerId || null;

        /** @type {{createCustomer: Function, updateCustomer: Function}|null} customerResource */
        this.customerResource = null;

        /** @type {{createResource: Function}} paymentType */
        this.paymentType = this.initPaymentType(type);

        /** @type {jQuery<HTMLElement>} $errorContainer Wrapper for Container to display error messages in */
        this.$errorContainer = this.settings.$errorContainer || $('#error-container');

        /** @type {jQuery<HTMLElement>} $errorMessage Container to display error messages in */
        this.$errorMessage = this.settings.$errorMessage || this.$errorContainer.find('.alert');

        /** @type {HTMLElement} form Form in which the customer enters additional details */
        this.form = this.settings.form || document.getElementById('form_payment_extra');

        // Register Events
        this.handleFormSubmit = this.handleFormSubmit.bind(this); // it's a trick! needed in order to overcome the remove event listener
        this.form.addEventListener('submit', this.handleFormSubmit);

        if (this.settings.autoSubmit) {
            // this.form.dispatchEvent(new Event('submit')); // Causes endless redirects in some browsers like FF, so we call the callback directly...
            this.handleFormSubmit(new Event('submit'));
        }
    }

    /**
     * Payment Type Constants
     * @constant
     */
    HeidelpayPayment.PAYMENT_TYPES = {
        ALIPAY: 'Alipay',
        CARD: 'Card',
        EPS: 'EPS',
        FLEXIPAY_DIRECT: 'FlexiPay Direct',
        HIRE_PURCHASE: 'Hire Purchase',
        PAYPAL: 'Paypal',
        INVOICE: 'Invoice',
        INVOICE_FACTORING: 'Invoice Factoring',
        INVOICE_GUARANTEED: 'Invoice Guaranteed',
        SEPA: 'SEPA',
        SEPA_GUARANTEED: 'SEPA (guaranteed)',
        SOFORT: 'SOFORT',
        GIROPAY: 'Giropay',
        PRZELEWY24: 'Przelewy24',
        IDEAL: 'iDEAL',
        PREPAYMENT: 'Prepayment',
        WECHAT_PAY: 'WeChat Pay'
    };

    /**
     * Init Payment Type
     *
     * @param {string} type
     * @returns {object} payment type
     * @throws Error if there is an unkown payment type
     */
    HeidelpayPayment.prototype.initPaymentType = function (type) {
        switch (type) {
            case HeidelpayPayment.PAYMENT_TYPES.CARD:
                return this.createCard();

            case HeidelpayPayment.PAYMENT_TYPES.INVOICE:
                return this.createInvoice();

            case HeidelpayPayment.PAYMENT_TYPES.INVOICE_GUARANTEED:
                return this.createInvoiceGuaranteed();

            case HeidelpayPayment.PAYMENT_TYPES.INVOICE_FACTORING:
                return this.createInvoiceFactoring();

            case HeidelpayPayment.PAYMENT_TYPES.SEPA:
                return this.createSepa();

            case HeidelpayPayment.PAYMENT_TYPES.SEPA_GUARANTEED:
                return this.createSepaGuaranteed();

            case HeidelpayPayment.PAYMENT_TYPES.PAYPAL:
                return this.createPaypal();

            case HeidelpayPayment.PAYMENT_TYPES.SOFORT:
                return this.createSofort();

            case HeidelpayPayment.PAYMENT_TYPES.GIROPAY:
                return this.createGiropay();

            case HeidelpayPayment.PAYMENT_TYPES.PRZELEWY24:
                return this.createPrzelewy24();

            case HeidelpayPayment.PAYMENT_TYPES.IDEAL:
                return this.createIdeal();

            case HeidelpayPayment.PAYMENT_TYPES.PREPAYMENT:
                return this.createPrepayment();

            case HeidelpayPayment.PAYMENT_TYPES.EPS:
                return this.createEPS();

            case HeidelpayPayment.PAYMENT_TYPES.FLEXIPAY_DIRECT:
                return this.createFlexiPayDirect();

            case HeidelpayPayment.PAYMENT_TYPES.ALIPAY:
                return this.createAlipay();

            case HeidelpayPayment.PAYMENT_TYPES.WECHAT_PAY:
                return this.createWeChatPay();

            case HeidelpayPayment.PAYMENT_TYPES.HIRE_PURCHASE:
                return this.createHirePurchase();

            default:
                throw new Error('Unkown Payment Type: ' + type);
        }
    };

    /**
     * Handle the form submit
     *
     * @this {HeidelpayPayment}
     * @param {Event} event Submit Event
     */
    HeidelpayPayment.prototype.handleFormSubmit = function(event) {
        var self = this;
        event.preventDefault();

        // Creating a Payment resource and (optional) Customer Resource
        var resources = [this.paymentType.createResource()];

        if (this.customerResource) {
            resources.push(this.customerId ? this.customerResource.updateCustomer() : this.customerResource.createCustomer());
        }

        Promise.all(resources).then(function (result) {
            // Append Payment Resource Id
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'paymentData[resourceId]');
            hiddenInput.setAttribute('value', result[0].id);
            self.form.appendChild(hiddenInput);

            // Append Customer Id
            if (result.length >= 2) {
                var hiddenCstInput = document.createElement('input');
                hiddenCstInput.setAttribute('type', 'hidden');
                hiddenCstInput.setAttribute('name', 'paymentData[customerId]');
                hiddenCstInput.setAttribute('value', result[1].id);
                self.form.appendChild(hiddenCstInput);
            }

            // Submitting the form
            self.form.removeEventListener('submit', self.handleFormSubmit);
            self.form.submit();
        })
        .catch(function (error) {
            self.$errorContainer.show();
            self.$errorMessage.html(error.message);
        });
    };

    /**
     * Create (or update) customer resource.
     *
     * @see https://docs.heidelpay.com/docs/customer-ui-integration
     * @returns {{createCustomer: Function, updateCustomer: Function}} Customer Resource
     */
    HeidelpayPayment.prototype.createCustomer = function() {
        var Customer = this.settings.isB2B ? this.unzerInstance.B2BCustomer() : this.unzerInstance.Customer();
        var customerObj = this.settings.customer || {};
        var continueButton = this.settings.submitButton || document.getElementById("submit-button");


        if (this.settings.isB2B) {
            // customerObj.firstname = null;
            // customerObj.lastname = null;
        }
        Customer.initFormFields(customerObj);

        Customer.addEventListener('validate', function(e) {
            if (e.success) {
                continueButton.removeAttribute('disabled');
                return;
            }

            continueButton.setAttribute('disabled', true);
        });

        if (this.customerId) {
            var options = {
                containerId: 'customer',
                fields: ['birthdate'], // at least one string required ('birthdate' || 'name' || 'address')
                showInfoBox: false,
                showHeader: false
            };

            if (this.settings.isB2B) {
                options = {containerId: 'customer'};
            }

            Customer.update(this.customerId, options);

            return Customer;
        }

        Customer.create({
            containerId: 'customer',
            showInfoBox: false,
            showHeader: false
        });

        return Customer;
    };

    /**
     * Create a new Card Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/credit-card-ui-integration
     * @returns {{createResource: Function}} Card Payment Type
     */
    HeidelpayPayment.prototype.createCard = function () {
        var Card = this.unzerInstance.Card();
        var styling = {fontSize: null, fontColor: null, fontFamily: null};

        if (this.settings.styling) {
            styling.fontColor = this.settings.styling.fontColor || null;
            styling.fontSize = this.settings.styling.fontSize || null;
            styling.fontFamily = this.settings.styling.fontFamily || null;
        }

        Card.create('number', {
            containerId: 'card-element-id-number',
            onlyIframe: false,
            fontSize: styling.fontSize,
            fontColor: styling.fontColor,
            fontFamily: styling.fontFamily
        });
        Card.create('expiry', {
            containerId: 'card-element-id-expiry',
            onlyIframe: false,
            fontSize: styling.fontSize,
            fontColor: styling.fontColor,
            fontFamily: styling.fontFamily
        });
        Card.create('cvc', {
            containerId: 'card-element-id-cvc',
            onlyIframe: false,
            fontSize: styling.fontSize,
            fontColor: styling.fontColor,
            // fontFamily: styling.fontFamily // messes with hidden font in firefox
        });

        // Enable pay button initially
        var self = this;
        var formFieldValid = {};

        /** @type {HTMLElement} continueButton */
        var continueButton = self.settings.submitButton || document.getElementById("submit-button");
        continueButton.setAttribute('disabled', true);

        var eventHandlerCardInput = function (e) {
            if (e.success) {
                formFieldValid[e.type] = true;
                self.$errorContainer.hide();
                self.$errorMessage.html();
            }

            if (e.error) {
                formFieldValid[e.type] = false;
                self.$errorContainer.show();
                self.$errorMessage.html(e.error);
            }

            if (formFieldValid.number && formFieldValid.expiry && formFieldValid.cvc) {
                continueButton.removeAttribute('disabled');
                return;
            }

            continueButton.setAttribute('disabled', true);
        };

        Card.addEventListener('change', eventHandlerCardInput);

        return Card;
    };

    /**
     * Create a new Invoice Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */
    HeidelpayPayment.prototype.createInvoice = function () {
        return this.unzerInstance.Invoice();
    };

    /**
     * Create a new Invoice Guaranteed Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */
    HeidelpayPayment.prototype.createInvoiceGuaranteed = function () {
        this.customerResource = this.createCustomer();

        return this.unzerInstance.InvoiceSecured();
    };

    /**
     * Create a new Invoice Factoring Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */
    HeidelpayPayment.prototype.createInvoiceFactoring = function () {
        this.customerResource = this.createCustomer();

        return this.unzerInstance.InvoiceSecured();
    };

    /**
     * Create a new SEPA Direct Debit Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/sepa-direct-debit-ui-integration
     * @returns {{createResource: Function}} SEPA Direct Debit Payment Type
     */
    HeidelpayPayment.prototype.createSepa = function () {
        var Sepa = this.unzerInstance.SepaDirectDebit();
        Sepa.create('sepa-direct-debit', {
            containerId: 'sepa-IBAN'
        });

        return Sepa;
    };

    /**
     * Create a new SEPA Direct Debit (guaranteed) Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/sepa-direct-debit-ui-integration
     * @returns {{createResource: Function}} SEPA Direct Debit (guaranteed) Payment Type
     */
    HeidelpayPayment.prototype.createSepaGuaranteed = function () {
        var SepaGuaranteed = this.unzerInstance.SepaDirectDebitSecured();
        SepaGuaranteed.create('sepa-direct-debit-guaranteed', {
            containerId: 'sepa-guaranteed-IBAN'
        });

        this.customerResource = this.createCustomer();

        return SepaGuaranteed;
    };

    /**
     * Create a new PayPal Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/paypal-ui-integration
     * @returns {{createResource: Function}} Papal Payment Type
     */
    HeidelpayPayment.prototype.createPaypal = function () {
        var Paypal = this.unzerInstance.Paypal();
        Paypal.create('email', {
            containerId: 'paypal-element-email'
        });

        return Paypal;
    };

    /**
     * Create a new SOFORT Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#sofort
     * @returns {{createResource: Function}} Sofort Payment Type
     */
    HeidelpayPayment.prototype.createSofort = function () {
        return this.unzerInstance.Sofort();
    };

    /**
     * Create a new Giropay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#giropay
     * @returns {{createResource: Function}} Giropay Payment Type
     */
    HeidelpayPayment.prototype.createGiropay = function () {
        return this.unzerInstance.Giropay();
    };

    /**
     * Create a new Przelewy24 Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#przelewy24
     * @returns {{createResource: Function}} Przelewy24 Payment Type
     */
    HeidelpayPayment.prototype.createPrzelewy24 = function () {
        return this.unzerInstance.Przelewy24();
    };

    /**
     * Create a new iDEAL Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/ideal-ui-integration
     * @returns {{createResource: Function}} iDEAL Payment Type
     */
    HeidelpayPayment.prototype.createIdeal = function () {
        var Ideal = this.unzerInstance.Ideal();

        Ideal.create('ideal', {
            containerId: 'ideal-element'
        });

        return Ideal;
    };

    /**
     * Create a new Prepayment Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/prepayment-ui-integration
     * @returns {{createResource: Function}} Prepayment Payment Type
     */
    HeidelpayPayment.prototype.createPrepayment = function () {
        return this.unzerInstance.Prepayment();
    };

    /**
     * Create a new EPS Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/eps-ui-integration
     * @returns {{createResource: Function}} EPS Payment Type
     */
    HeidelpayPayment.prototype.createEPS = function () {
        var EPS = this.unzerInstance.EPS();

        EPS.create('eps', {
            containerId: 'eps-element'
        });

        return EPS;
    };

    /**
     * Create a new FlexiPay Direct Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#flexipay-direct
     * @returns {{createResource: Function}} Alipay Payment Type
     */
    HeidelpayPayment.prototype.createFlexiPayDirect = function () {
        return this.unzerInstance.FlexiPayDirect();
    };

    /**
     * Create a new Alipay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#alipay
     * @returns {{createResource: Function}} Alipay Payment Type
     */
    HeidelpayPayment.prototype.createAlipay = function () {
        return this.unzerInstance.Alipay();
    };

    /**
     * Create an new WeChat Pay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#wechat-pay
     * @returns {{createResource: Function}} WeChat Pay Payment Type
     */
    HeidelpayPayment.prototype.createWeChatPay = function () {
        return this.unzerInstance.Wechatpay();
    };

    /**
     * Create a new Hire Purchase Payment Type.
     *
     * @see https:://docs.heidelpay.com/docs/hire-purchase-ui-integration
     * @returns {{createResource: Function}} Hire Purchase Payment Type
     */
    HeidelpayPayment.prototype.createHirePurchase = function () {
        var InstallmentSecured = this.unzerInstance.InstallmentSecured();
        var self = this;
        this.customerResource = this.createCustomer();

        /** @type {HTMLElement} continueButton */
        var continueButton = self.settings.submitButton || document.getElementById("submit-button");
        continueButton.setAttribute('style', 'display: none');
        continueButton.setAttribute('disabled', true);

        InstallmentSecured.create({
            containerId: 'hire-purchase-element',
            amount: this.settings.amount || null,
            currency: this.settings.currency || null,
            effectiveInterest: this.settings.effectiveInterest || null,
            orderDate: this.settings.orderDate || null
        }).then(function (data) {
            // if successful, notify the user that the list of installments was fetched successfully
            // in case you were using a loading element during the fetching process,
            // you can remove it inside this callback function
        })
        .catch(function (response) {
            // sent an error message to the user (fetching installment list failed)
            var msg = '';
            console.error(response.message);

            response.error.details.forEach(function(err) {
                console.error('API-Error: ' + err.code);
                msg += err.customerMessage;
            });

            self.$errorContainer.show();
            self.$errorMessage.html(msg);
        });


        // Listen to UI events
        InstallmentSecured.addEventListener('installmentSecuredEvent', function (e) {
            if (e.action === 'validate') {
                if (e.success) {
                    continueButton.removeAttribute('disabled');
                    return;
                }

                continueButton.setAttribute('disabled', true);
            }

            if (e.action === 'change-step') {
                if (e.currentSteep === 'plan-list') {
                    continueButton.setAttribute('style', 'display: none');
                    continueButton.setAttribute('disabled', true);
                    return;
                }

                continueButton.setAttribute('style', 'display: inline-block');
            }
        });

        return InstallmentSecured;
    };

    window.HpPayment = HeidelpayPayment;

    /**
     * Heidelpay Installment Modal Window Handler
     *
     * @param {string} modalSelector
     * @param {HTMLElement} btn Submit Trigger
     * @param {JQuery<HTMLElement>} $form
     */
    window.HpInstalment = function(modalSelector, btn, $form) {
        var modal = $(modalSelector);

        btn.addEventListener('click', function() {
            $form.trigger('submit');
        });

        $form.on('submit', function(e) {
            if (!modal.is(':visible')) {
                e.preventDefault();
                modal.modal('show');
                return false;
            }

            return true;
        });
    };
})(jQuery, window, window.document);