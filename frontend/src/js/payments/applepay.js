import ErrorHandler from "../utils/errors";
import Debugging from "../utils/debugging";

/** @type {ApplePaySnippets} */
const ApplePaySnippetsDefaults = {
    NOT_SUPPORTED: "This device does not support Apple Pay!",
    CANCEL_BY_USER: "Canceled payment process by user!"
};

export default class ApplePay {
    /**
     * Similiar to the HeidelpayPayment class but slightly differently
     * (apple pay works differently, so yeah, custom class it is :D)
     *
     * @class
     * @param {String} pubKey
     * @param {ApplePayPaymentRequest} applePayPaymentRequest
     * @param {ApplePaySnippets} snippets
     * @param {ApplePaySettings} settings
     */
    constructor(pubKey, applePayPaymentRequest, snippets, settings) {
        /** @type {ApplePaySettings} */
        this.settings = settings || {};

        /** @type {ApplePaySnippets} */
        this.snippets = Object.assign(ApplePaySnippetsDefaults, snippets);

        var options = {
            locale: this.settings.locale || 'de-DE'
        };

        /** @type {ApplePayPaymentRequest} */
        this.applePayPaymentRequest = applePayPaymentRequest;
        this.unzerInstance = new unzer(pubKey, options);
        this.errorHandler = new ErrorHandler(this.settings.$errorContainer, this.settings.$errorMessage);
        this.debugging = new Debugging($('.unzerUI'));
        window.UNZER_DEBUG = !!this.unzerInstance._isSandbox || this.unzerInstance.config.hasSandboxKey; // Enable Debugging in sandbox mode

        if (!window.ApplePaySession|| !window.ApplePaySession.canMakePayments()) {
            this.unsupportedDevice();
            return;
        }

        /** @type {HTMLElement} form Form in which the customer enters additional details */
        this.form = this.settings.form || document.getElementById('form_payment_extra');

        // Register Events
        this.initPaymentType = this.initPaymentType.bind(this); // it's a trick! needed in order to overcome the remove event listener
        this.form.addEventListener('submit', this.initPaymentType);
        $('.apple-pay-button').on('click', this.initPaymentType.bind(this));
    }

    /**
     * Init the payment type in this case means starting the apple pay session
     * @param {Event} event
     */
    initPaymentType(event) {
        event.preventDefault();

        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            this.unsupportedDevice();
            return;
        }

        // We adhere to Apple Pay version 6 to handle the payment request.
        const session = new ApplePaySession(3, this.applePayPaymentRequest);
        this.debugging.log('[> Init Payment Type]', {paymentRequest: this.applePayPaymentRequest});

        session.onvalidatemerchant = (event) => {
            this.merchantValidationCallback(event, session);
        };

        session.onpaymentauthorized = (event) => {
            this.applePayAuthorizedCallback(event, session);
        };

        session.oncancel = (event) => {
            this.debugging.log('[> Cancel]', {event});
            this.errorHandler.show(this.snippets.CANCEL_BY_USER);
        };

        session.begin();
    }

    /**
     * Call the merchant validation in the server-side integration (apple_pay_merchantvalidation)
     * @param {Event} event
     * @param {ApplePaySession} session
     */
    merchantValidationCallback(event, session) {
        var validationUrl = JSON.stringify(event.validationURL);

        this.debugging.log('[> Merchant Validation]', {event});

        $.ajax({
            'url': $.evo.io().options.ioUrl,
            'method': 'POST',
            'dataType': 'json',
            'data': 'io={"name":"apple_pay_merchantvalidation", "params":[' + validationUrl + ']}',
        }).done((validationResponse) => {
            this.debugging.log('[> Merchant Validation Response]', validationResponse);

            try {
                session.completeMerchantValidation(validationResponse);
            } catch (e) {
                alert(e.message);
            }
        })
        .fail((error) => {
            this.debugging.log('[> Merchant Validation Error]', error);
            this.errorHandler.show(JSON.stringify(error.statusText));
            session.abort();
        });
    }

    /**
     * Create Apple Pay resource with unzer and save the resource id to charge it later.
     *
     * We do this here via AJAX instead of the validateAdditional method in the payment method to set the apple pay
     * session state accordingly.
     *
     * @param {Event} event
     * @param {ApplePaySession} session
     */
    applePayAuthorizedCallback(event, session) {
        // Get payment data from event.
        // "event.payment" also contains contact information, if they were set via Apple Pay.
        var self = this;
        var unzerApplePayInstance = this.unzerInstance.ApplePay();
        var paymentData = event.payment.token.paymentData;

        this.debugging.log('[> Payment Authorization]', {unzerApplePayInstance, event, paymentData});

        // Create an Unzer instance with your public key
        unzerApplePayInstance.createResource(paymentData)
            .then(function (createdResource) {
                // Hand over the type ID to your backend.
                var typeId = JSON.stringify(createdResource.id);
                $.ajax({
                    'url': $.evo.io().options.ioUrl,
                    'method': 'POST',
                    'dataType': 'json',
                    'data': 'io={"name":"apple_pay_payment_authorized", "params":[' + typeId + ']}',
                }).done(function (result) {
                    // Handle the transaction respone from backend.
                    self.debugging.log('[> Payment Authorization Response]', {result, typeId});
                    var status = result.transactionStatus;
                    if (status === 'success' || status === 'pending') {
                        session.completePayment({ status: window.ApplePaySession.STATUS_SUCCESS });

                        // Append Payment Resource Id
                        const hiddenInput = document.createElement('input');
                        hiddenInput.setAttribute('type', 'hidden');
                        hiddenInput.setAttribute('name', 'paymentData[resourceId]');
                        hiddenInput.setAttribute('value', typeId);
                        self.form.appendChild(hiddenInput);

                        // Submitting the form
                        self.form.removeEventListener('submit', self.initPaymentType);
                        self.form.submit();
                    } else {
                        self.abortPaymentSession(session);
                    }
                }).fail(function (error) {
                    self.errorHandler.show(error.statusText);
                    self.abortPaymentSession(session);
                });
            })
            .catch(function (error) {
                self.debugging.log('[> Payment Authorization Error]', error);
                self.errorHandler.show(error.message);
                self.abortPaymentSession(session);
            });
    }

    /**
     * Handle Unsupported devices
     */
    unsupportedDevice() {
        this.settings.submitButton.disabled = true;
        this.settings.submitButton.ariaDisabled = true;
        this.errorHandler.show(this.snippets.NOT_SUPPORTED);
    }

    /**
     * abort current payment session.
     * @param {ApplePaySession} session
     */
    abortPaymentSession(session) {
        this.debugging.log('[> Abort Payment Session]', { status: window.ApplePaySession.STATUS_FAILURE });
        session.completePayment({ status: window.ApplePaySession.STATUS_FAILURE });
        session.abort();
    }
}
