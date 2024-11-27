import ErrorHandler from "../utils/errors";
import Debugging from "../utils/debugging";

/** @type {ApplePaySnippets} */
const ApplePaySnippetsDefaults = {
    NOT_SUPPORTED: "This device does not support Apple Pay!",
    CANCEL_BY_USER: "Canceled payment process by user!"
};

export default class ApplePayV2 {
    /** @type {ApplePaySettings} */
    settings;

    /** @type {ApplePaySnippets} */
    snippets;

    /** @type {HTMLFormElement} */
    form;

    /** @type {ApplePayPaymentRequest} */
    applePayPaymentRequest;

    #errorHandler = new ErrorHandler();
    #debugging = new Debugging($('.unzerUI'));

    /**
     * @class
     * @param {String} pubKey
     * @param {ApplePayPaymentRequest} applePayPaymentRequest
     * @param {ApplePaySnippets} snippets
     * @param {ApplePaySettings} settings
     */
    constructor(pubKey, applePayPaymentRequest, snippets, settings) {
        this.settings = settings;
        this.snippets = Object.assign(ApplePaySnippetsDefaults, snippets);
        this.form = this.settings.form || document.getElementById('complete_order');
        this.applePayPaymentRequest = applePayPaymentRequest;
        this.unzerInstance = new unzer(pubKey, {
            locale: this.settings.locale || 'de-DE'
        });
        window.UNZER_DEBUG = !!this.unzerInstance._isSandbox || this.unzerInstance.config.hasSandboxKey; // Enable Debugging in sandbox mode

        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            this.#unsupportedDevice();
            return;
        }

        // Register Events
        this.initPaymentType = this.initPaymentType.bind(this); // it's a trick! needed in order to overcome the remove event listener
        this.form.addEventListener('submit', this.initPaymentType);
        $('.apple-pay-button').on('click', this.initPaymentType.bind(this));
    }

    /**
     * @param {Event} event
     */
    initPaymentType(event) {
        event.preventDefault();
        const applePayInstance = this.unzerInstance.ApplePay();
        const session = applePayInstance.initApplePaySession(this.applePayPaymentRequest); // setup session with default merchant validation

        this.#debugging.log('[> Init Payment Type]', { paymentRequest: this.applePayPaymentRequest });

        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            this.#unsupportedDevice();
            return;
        }

        /** @param {Event} event */
        session.onpaymentauthorized = (event) => {
            this.#applePayAuthorizedCallback(event, applePayInstance, session);
        };

        /** @param {Event} event */
        session.oncancel = (event) => {
            this.#debugging.log('[> Cancel]', {event});
            this.#errorHandler.show(this.snippets.CANCEL_BY_USER);
        };

        session.begin();
    }

    /**
     * Create Apple Pay resource with unzer and save the resource id to charge it later.
     *
     * @param {Event & {payment: {token: {paymentData: object}}}} event
     * @param {Object} applePayInstance
     * @param {ApplePaySession} session
     */
    #applePayAuthorizedCallback(event, applePayInstance, session) {
        const paymentData = event.payment.token.paymentData;

        this.#debugging.log('[> Payment Authorization]', { applePayInstance, event, paymentData });

        // Create an Unzer instance with your public key
        applePayInstance.createResource(paymentData)
            .then((createdResource) => {
                this.#debugging.log('[> Payment Authorization Resource]', { createdResource });
                session.completePayment({ status: window.ApplePaySession.STATUS_SUCCESS });

                // Hand over the payment type ID (createdResource.id)
                const typeId = JSON.stringify(createdResource.id);
                const hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'unzer-payment-type-id');
                hiddenInput.setAttribute('value', typeId);

                this.form.appendChild(hiddenInput);
                this.form.removeEventListener('submit', this.initPaymentType);
                this.form.submit();
            })
            .catch(error => {
                this.#debugging.log('[> Payment Authorization Error]', error);
                this.#errorHandler.show(error.message);
                this.#abortPaymentSession(session);
            })
    }

    /**
     * abort current payment session.
     * @param {ApplePaySession} session
     */
    #abortPaymentSession(session) {
        this.#debugging.log('[> Abort Payment Session]', { status: window.ApplePaySession.STATUS_FAILURE });
        session.completePayment({ status: window.ApplePaySession.STATUS_FAILURE });
        session.abort();
    }

    /**
     * Handle Unsupported devices
     */
    #unsupportedDevice() {
        this.#errorHandler.show(this.snippets.NOT_SUPPORTED);
        console.error(this.snippets.NOT_SUPPORTED);
    }
}