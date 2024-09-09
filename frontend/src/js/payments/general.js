import ErrorHandler from "../utils/errors";

export default class UnzerPayment {
    static PAYMENT_TYPES = {
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
        WECHAT_PAY: 'WeChat Pay',
        PAYLATER_INVOICE: 'Paylater Invoice',
        BANCONTACT: 'Bancontact',
        PAYLATER_INSTALLMENT: 'Paylater Installment',
        PAYLATER_DIRECT_DEBIT: 'Paylater Direct Debit',
        TWINT: 'Twint',
    };

    /**
     * Heidelpay Payment Class
     *
     * @param {string} pubKey Public Key
     * @param {string} type Payment Type
     * @param {PaymentSettings} settings
     */
    constructor(pubKey, type, settings) {
        /** @type {PaymentSettings} */
        this.settings = settings || {};

        var options = {
            locale: this.settings.locale || 'de-DE'
        };

        this.unzerInstance = new unzer(pubKey, options);
        this.errorHandler = new ErrorHandler(this.settings.$errorContainer, this.settings.$errorMessage);

        /** @type {?string} customerId */
        this.customerId = settings.customerId || null;

        /** @type {{createCustomer: Function, updateCustomer: Function}|null} customerResource */
        this.customerResource = null;

        /** @type {{createResource: Function}} paymentType */
        this.paymentType = this.initPaymentType(type);

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
     * Init Payment Type
     *
     * @param {string} type
     * @returns {object} payment type
     * @throws Error if there is an unkown payment type
     */
    initPaymentType(type) {
        switch (type) {
            case UnzerPayment.PAYMENT_TYPES.CARD:
                return this.createCard();

            case UnzerPayment.PAYMENT_TYPES.INVOICE:
                return this.createInvoice();

            case UnzerPayment.PAYMENT_TYPES.INVOICE_GUARANTEED:
                return this.createInvoiceGuaranteed();

            case UnzerPayment.PAYMENT_TYPES.INVOICE_FACTORING:
                return this.createInvoiceFactoring();

            case UnzerPayment.PAYMENT_TYPES.SEPA:
                return this.createSepa();

            case UnzerPayment.PAYMENT_TYPES.SEPA_GUARANTEED:
                return this.createSepaGuaranteed();

            case UnzerPayment.PAYMENT_TYPES.PAYPAL:
                return this.createPaypal();

            case UnzerPayment.PAYMENT_TYPES.SOFORT:
                return this.createSofort();

            case UnzerPayment.PAYMENT_TYPES.GIROPAY:
                return this.createGiropay();

            case UnzerPayment.PAYMENT_TYPES.PRZELEWY24:
                return this.createPrzelewy24();

            case UnzerPayment.PAYMENT_TYPES.IDEAL:
                return this.createIdeal();

            case UnzerPayment.PAYMENT_TYPES.PREPAYMENT:
                return this.createPrepayment();

            case UnzerPayment.PAYMENT_TYPES.EPS:
                return this.createEPS();

            case UnzerPayment.PAYMENT_TYPES.FLEXIPAY_DIRECT:
                return this.createFlexiPayDirect();

            case UnzerPayment.PAYMENT_TYPES.ALIPAY:
                return this.createAlipay();

            case UnzerPayment.PAYMENT_TYPES.TWINT:
                return this.createTwint();

            case UnzerPayment.PAYMENT_TYPES.WECHAT_PAY:
                return this.createWeChatPay();

            case UnzerPayment.PAYMENT_TYPES.HIRE_PURCHASE:
                return this.createHirePurchase();

            case UnzerPayment.PAYMENT_TYPES.PAYLATER_INVOICE:
                return this.createPaylaterInvoice();

            case UnzerPayment.PAYMENT_TYPES.BANCONTACT:
                return this.createBancontact();

            case UnzerPayment.PAYMENT_TYPES.PAYLATER_INSTALLMENT:
                return this.createPaylaterInstallment();

            case UnzerPayment.PAYMENT_TYPES.PAYLATER_DIRECT_DEBIT:
                return this.createPaylaterDirectDebit();

            default:
                throw new Error('Unkown Payment Type: ' + type);
        }
    }

    /**
     * Handle the form submit
     *
     * @param {Event} event Submit Event
     */
    handleFormSubmit(event) {
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
            self.errorHandler.show(error.message);
        });
    }

    /**
     * Handle Paylater Input Validation
     * @param {Event} event
     * @param {boolean} isValid
     * @param {String} paymentMethodName
     * @param {HTMLElement} continueButton
     * @returns
     */
    onPaylaterInputValidation(event, isValid, paymentMethodName, continueButton) {
        // console.log(paymentMethodName, { event, isValid, continueButton, 's360-valid': continueButton.getAttribute('data-s360-valid') });

        if (isValid) {
            // everything is still valid
            if (continueButton.getAttribute('data-s360-valid') == 'all') {
                continueButton.removeAttribute('disabled');
                return;
            }

            // Customer is already valid -> everything is valid
            if (continueButton.getAttribute('data-s360-valid') == 'customer') {
                continueButton.setAttribute('data-s360-valid', 'all');
                continueButton.removeAttribute('disabled');
                return;
            }

            // mark payment method as valid
            continueButton.setAttribute('data-s360-valid', paymentMethodName);
            return;
        }

        continueButton.setAttribute('disabled', true);

        // only invalidate if the paymentMethodName was valid before
        if (continueButton.getAttribute('data-s360-valid') == paymentMethodName) {
            continueButton.setAttribute('data-s360-valid', 0);
        } else if (continueButton.getAttribute('data-s360-valid') == 'all') {
            continueButton.setAttribute('data-s360-valid', 'customer');
        }
    }

    /**
     * Create (or update) customer resource.
     *
     * @param {?String} paymentTypeName
     * @param {?String} multipleValidation
     * @see https://docs.heidelpay.com/docs/customer-ui-integration
     * @returns {{createCustomer: Function, updateCustomer: Function}} Customer Resource
     */
    createCustomer(paymentTypeName = null, multipleValidation = false) {
        var Customer = this.settings.isB2B ? this.unzerInstance.B2BCustomer() : this.unzerInstance.Customer();
        var customerObj = this.settings.customer || {};
        var continueButton = this.settings.submitButton || document.getElementById("submit-button");
        let options = {
            containerId: 'customer',
            showInfoBox: false,
            showHeader: false,
            fields: ['name', 'birthdate']
        };

        if (paymentTypeName) {
            options.paymentTypeName = paymentTypeName;
        }

        Customer.initFormFields(customerObj);
        if (multipleValidation) {
            continueButton.setAttribute('data-s360-valid', 0);

            Customer.addEventListener('validate', (e) => {
                // console.log('customer validate', e, continueButton, continueButton.getAttribute('data-s360-valid'));
                continueButton.setAttribute('disabled', true);

                if (e.success) {
                    // everything is still valid
                    if (continueButton.getAttribute('data-s360-valid') == 'all') {
                        continueButton.removeAttribute('disabled');
                        return;
                    }

                    // payment method is already valid -> everything is valid
                    if (continueButton.getAttribute('data-s360-valid') == paymentTypeName) {
                        continueButton.setAttribute('data-s360-valid', 'all');
                        continueButton.removeAttribute('disabled');
                        return;
                    }

                    continueButton.setAttribute('data-s360-valid', 'customer');
                    return;
                }

                // only invalidate if the customer was valid before
                if (continueButton.getAttribute('data-s360-valid') == 'customer') {
                    continueButton.setAttribute('data-s360-valid', 0);
                } else if (continueButton.getAttribute('data-s360-valid') == 'all') {
                    continueButton.setAttribute('data-s360-valid', paymentTypeName);
                }
            });
        } else {
            Customer.addEventListener('validate', (e) => {
                if (e.success) {
                    continueButton.removeAttribute('disabled');
                    return;
                }

                continueButton.setAttribute('disabled', true);
            });
        }

        if (this.settings.isB2B) {
            options.fields = ['companyInfo'];
            // options = {containerId: 'customer'};
        }

        if (this.customerId) {
            Customer.update(this.customerId, options);
            return Customer;
        }

        Customer.create(options);

        return Customer;
    }

    /**
     * Hide form fields from unzer ui component because they are already filled by the shop
     * @param {string} paymentMethodName
     */
    hideFormFields(paymentMethodName) {
        const field = $('#customer');

        field.find('.field').filter(
            '.city, .company, :has(.country), .street, .zip, .firstname, .lastname'
        ).hide();
        field.find('.salutation-customer').hide();
        field.find('.firstname, .lastname').parent('.fields').hide();
        field.find('.unzerUI.divider-horizontal:eq(0)').hide();
        field.find('.unzerUI.message.downArrow').hide();

        if (paymentMethodName) {
            field.find('.field').filter('.checkbox-billingAddress, .email').hide();
            field.find('.field').filter(
                '.billing-name, .billing-street, .billing-zip, .billing-city, :has(.billing-country)'
            ).hide();
            field.find('.unzerUI.form>.checkboxLabel').hide();
            field.find('.unzerUI.form>.salutation-unzer-' + paymentMethodName + '-customer').hide();
        }
    }

    /**
     * Create Paylayter Installment Payment Type
     *
     * @see https://docs.unzer.com/payment-methods/unzer-installment-upl/accept-unzer-installment-ui-component/
     * @returns {{createResource: Function}}
     */
    createPaylaterInstallment() {
        this.customerResource = this.createCustomer('paylater-installment', true);
        const continueButton = this.settings.submitButton || document.getElementById("submit-button");
        const paylaterInstallment = this.unzerInstance.PaylaterInstallment();

        this.hideFormFields('paylater-installment');

        paylaterInstallment.create({
            containerId: 'paylater-installment',
            amount: this.settings.amount,
            currency: this.settings.currency,
            country: this.settings.country
        });

        paylaterInstallment.addEventListener('paylaterInstallmentEvent', (e) => {
            switch (e.currentStep) {
                case 'plan-list':
                    continueButton.setAttribute('disabled', true);
                    break;

                case 'plan-detail':
                    continueButton.setAttribute('disabled', false);
                    break;

                default:
                    break;
            }

            const isValid = e.action === 'validate' && e.success;
            this.onPaylaterInputValidation(e, isValid, 'paylater-installment', continueButton);
        });

        return paylaterInstallment;
    }

    /**
     * Create Paylayter Invoice Payment Type
     *
     * @see https://docs.unzer.com/payment-methods/unzer-invoice-upl/accept-unzer-invoice-upl-ui-component/
     * @returns {{createResource: Function}}
     */
    createPaylaterInvoice() {
        this.customerResource = this.createCustomer('paylater-invoice', true);
        const continueButton = this.settings.submitButton || document.getElementById("submit-button");
        const paylaterInvoice = this.unzerInstance.PaylaterInvoice();

        this.hideFormFields('paylater-invoice');

        paylaterInvoice.create({
            containerId: 'paylater-invoice',
            customerType: this.settings.isB2B ? 'B2B' : 'B2C'
        });

        paylaterInvoice.addEventListener('change', (e) =>
            this.onPaylaterInputValidation(
                e,
                e.success,
                'paylater-invoice',
                continueButton
            )
        );

        return paylaterInvoice;
    }

    /**
     * Create Paylayter Invoice Payment Type
     *
     * @see https://docs.unzer.com/payment-methods/direct-debit-secured/accept-direct-debit-secured-ui-component/
     * @returns {{createResource: Function}}
     */
    createPaylaterDirectDebit() {
        this.customerResource = this.createCustomer('paylater-direct-debit', true);
        const continueButton = this.settings.submitButton || document.getElementById("submit-button");
        const paylaterDirectDebit = this.unzerInstance.PaylaterDirectDebit();

        this.hideFormFields('paylater-direct-debit');

        paylaterDirectDebit.create('paylater-direct-debit', {
            containerId: 'paylater-direct-debit',
            customerType: this.settings.isB2B ? 'B2B' : 'B2C'
        });

        paylaterDirectDebit.addEventListener('change', (e) =>
            this.onPaylaterInputValidation(
                e,
                e.success,
                'paylater-direct-debit',
                continueButton
            )
        );

        return paylaterDirectDebit;
    }

    /**
     * Create Bancontact Payment Type
     *
     * @see https://docs.unzer.com/payment-methods/bancontact/accept-bancontact-ui-component/
     * @returns {{createResource: Function}}
     */
    createBancontact() {
        const bancontact = this.unzerInstance.Bancontact();
        const styling = { fontSize: null, fontColor: null, fontFamily: null };

        if (this.settings.styling) {
            styling.fontColor = this.settings.styling.fontColor || null;
            styling.fontSize = this.settings.styling.fontSize || null;
            styling.fontFamily = this.settings.styling.fontFamily || null;
        }

        bancontact.create('holder', {
            containerId: 'bancontact-holder',
            fontSize: styling.fontSize,
            fontColor: styling.fontColor,
            fontFamily: styling.fontFamily
        });

        return bancontact;
    }

    /**
     * Create a new Card Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/credit-card-ui-integration
     * @returns {{createResource: Function}} Card Payment Type
     */
    createCard() {
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
        Card.create('holder', {
            containerId: 'card-element-id-holder',
            onlyIframe: false,
            fontSize: styling.fontSize,
            fontColor: styling.fontColor,
            fontFamily: styling.fontFamily
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
                self.errorHandler.hide();
            }

            if (e.error) {
                formFieldValid[e.type] = false;
                self.errorHandler.show(e.error);
            }

            if (formFieldValid.number && formFieldValid.expiry && formFieldValid.cvc) {
                continueButton.removeAttribute('disabled');
                return;
            }

            continueButton.setAttribute('disabled', true);
        };

        Card.addEventListener('change', eventHandlerCardInput);

        return Card;
    }

    /**
     * Create a new Invoice Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */
    createInvoice() {
        return this.unzerInstance.Invoice();
    }

    /**
     * Create a new Invoice Guaranteed Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */
    createInvoiceGuaranteed() {
        this.customerResource = this.createCustomer();

        return this.unzerInstance.InvoiceSecured();
    }

    /**
     * Create a new Invoice Factoring Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */
    createInvoiceFactoring() {
        this.customerResource = this.createCustomer();

        return this.unzerInstance.InvoiceSecured();
    }

    /**
     * Create a new SEPA Direct Debit Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/sepa-direct-debit-ui-integration
     * @returns {{createResource: Function}} SEPA Direct Debit Payment Type
     */
    createSepa() {
        var Sepa = this.unzerInstance.SepaDirectDebit();
        Sepa.create('sepa-direct-debit', {
            containerId: 'sepa-IBAN'
        });

        /** @type {HTMLElement} continueButton */
        const continueButton = this.settings.submitButton || document.getElementById("submit-button");
        continueButton.setAttribute('disabled', true);

        Sepa.addEventListener('change', (e) => {
            if (e.success) {
                continueButton.removeAttribute('disabled');
                this.errorHandler.hide();
                return;
            }

            continueButton.setAttribute('disabled', true);
        });

        return Sepa;
    }

    /**
     * Create a new SEPA Direct Debit (guaranteed) Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/sepa-direct-debit-ui-integration
     * @returns {{createResource: Function}} SEPA Direct Debit (guaranteed) Payment Type
     */
    createSepaGuaranteed() {
        var SepaGuaranteed = this.unzerInstance.SepaDirectDebitSecured();
        SepaGuaranteed.create('sepa-direct-debit-guaranteed', {
            containerId: 'sepa-guaranteed-IBAN'
        });

        /** @type {HTMLElement} continueButton */
        const continueButton = this.settings.submitButton || document.getElementById("submit-button");
        continueButton.setAttribute('disabled', true);

        SepaGuaranteed.addEventListener('change', (e) => {
            if (e.success) {
                continueButton.removeAttribute('disabled');
                this.errorHandler.hide();
                return;
            }

            continueButton.setAttribute('disabled', true);
        });

        this.customerResource = this.createCustomer();

        return SepaGuaranteed;
    }

    /**
     * Create a new PayPal Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/paypal-ui-integration
     * @returns {{createResource: Function}} Papal Payment Type
     */
   createPaypal() {
        var Paypal = this.unzerInstance.Paypal();
        Paypal.create('email', {
            containerId: 'paypal-element-email'
        });

        return Paypal;
    }

    /**
     * Create a new SOFORT Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#sofort
     * @returns {{createResource: Function}} Sofort Payment Type
     */
    createSofort() {
        return this.unzerInstance.Sofort();
    }

    /**
     * Create a new Giropay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#giropay
     * @returns {{createResource: Function}} Giropay Payment Type
     */
    createGiropay () {
        return this.unzerInstance.Giropay();
    }

    /**
     * Create a new Przelewy24 Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#przelewy24
     * @returns {{createResource: Function}} Przelewy24 Payment Type
     */
    createPrzelewy24() {
        return this.unzerInstance.Przelewy24();
    }

    /**
     * Create a new iDEAL Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/ideal-ui-integration
     * @returns {{createResource: Function}} iDEAL Payment Type
     */
    createIdeal() {
        var Ideal = this.unzerInstance.Ideal();

        Ideal.create('ideal', {
            containerId: 'ideal-element'
        });

        /** @type {HTMLElement} continueButton */
        const continueButton = this.settings.submitButton || document.getElementById("submit-button");
        continueButton.setAttribute('disabled', true);

        Ideal.addEventListener('change', (e) => {
            if (e.value) {
                continueButton.removeAttribute('disabled');
                this.errorHandler.hide();
                return;
            }

            continueButton.setAttribute('disabled', true);
        });

        return Ideal;
    }

    /**
     * Create a new Prepayment Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/prepayment-ui-integration
     * @returns {{createResource: Function}} Prepayment Payment Type
     */
    createPrepayment() {
        return this.unzerInstance.Prepayment();
    }

    /**
     * Create a new EPS Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/eps-ui-integration
     * @returns {{createResource: Function}} EPS Payment Type
     */
    createEPS() {
        return this.unzerInstance.EPS();
    }

    /**
     * Create a new FlexiPay Direct Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#flexipay-direct
     * @returns {{createResource: Function}} Alipay Payment Type
     */
    createFlexiPayDirect() {
        return this.unzerInstance.FlexiPayDirect();
    }

    /**
     * Create a new Alipay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#alipay
     * @returns {{createResource: Function}} Alipay Payment Type
     */
    createAlipay() {
        return this.unzerInstance.Alipay();
    }

    /**
     * Create a new TWINT Payment Type.
     *
     * @see https://docs.unzer.com/payment-methods/twint/accept-twint-ui-component/
     * @returns {{createResource: Function}} Twint Payment Type
     */
    createTwint() {
        return this.unzerInstance.Twint();
    }

    /**
     * Create an new WeChat Pay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#wechat-pay
     * @returns {{createResource: Function}} WeChat Pay Payment Type
     */
    createWeChatPay() {
        return this.unzerInstance.Wechatpay();
    }

    /**
     * Create a new Hire Purchase Payment Type.
     *
     * @see https:://docs.heidelpay.com/docs/hire-purchase-ui-integration
     * @returns {{createResource: Function}} Hire Purchase Payment Type
     */
   createHirePurchase() {
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

            self.errorHandler.show(msg);
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
    }
}
