import ErrorHandler from "../utils/errors";

export default class GooglePay
{
    /**
     * @param {string} pubKey
     * @param {GooglePaySettings} settings
     */
    constructor(pubKey, settings) {
        /** @type {GooglePaySettings} */
        this.settings = settings || {};

        /** @type {HTMLFormElement} */
        this.form = this.settings.form || document.getElementById('complete_order');

        const options = {
            locale: this.settings.locale || 'de-DE'
        };

        this.unzerInstance = new unzer(pubKey, options);
        this.errorHandler = new ErrorHandler(this.settings.$errorContainer, this.settings.$errorMessage);

        this.createPaymentTypeResource();
    }

    createPaymentTypeResource() {
        // Creating a Google Pay instance
        const googlepayInstance = this.unzerInstance.Googlepay();

        const paymentData = googlepayInstance.initPaymentDataRequestObject({
            gatewayMerchantId: this.settings.googlepay.gatewayMerchantId,
            merchantInfo: this.settings.googlepay.merchantInfo,
            transactionInfo: this.settings.googlepay.transactionInfo,
            allowedCardNetworks: this.settings.googlepay.allowedCardNetworks,
            allowCreditCards: this.settings.googlepay.allowCreditCards,
            allowPrepaidCards: this.settings.googlepay.allowPrepaidCards,
            buttonOptions: {
                buttonColor: this.settings.googlepay.buttonOptions.buttonColor,
                buttonSizeMode: this.settings.googlepay.buttonOptions.buttonSize,
            },
            onPaymentAuthorizedCallback:  (paymentData) => googlepayInstance.createResource(paymentData)
                .then(this.onAuthorizedSuccess.bind(this))
                .catch(this.onAuthorizedFailed.bind(this))
        });

        googlepayInstance.create(
            { containerId: 'googlepay-holder' },
            paymentData
        );
    }

    /**
     * @param {{id: string}} result
     * @returns {{ status: string }}
     */
    onAuthorizedSuccess(result) {
        // Submit the ID to your server-side integration
        const hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'unzer-payment-type-id');
        hiddenInput.setAttribute('value', result.id);

        this.form.appendChild(hiddenInput);
        this.form.submit();

        return { status: 'success' };
    }

    /**
     * @param {{customerMessage?: string, message?: string, data?: {errors?: Array<{customerMessage?: string}>}}} error
     * @returns {{ status: string, message: string }}
     */
    onAuthorizedFailed(error) {
        let errorMessage = error.customerMessage || error.message || 'Error';
        if (error.data && Array.isArray(error.data.errors) && error.data.errors[0]) {
            errorMessage = error.data.errors[0].customerMessage || 'Error';
        }

        this.errorHandler.show(errorMessage);

        return {
            status: 'error',
            message: errorMessage || 'Unexpected error'
        };
    }
}