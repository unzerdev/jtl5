import BaseComponent from './base';

export default class extends BaseComponent {
    mounted() {
        super.mounted();

        this.inputNames.resourceId = 'unzer-payment-type-id';

        const settings = JSON.parse(this.el.dataset.paymentDataRequest) || {};
        const googlePayData = {
            gatewayMerchantId: settings.gatewayMerchantId,
            merchantInfo: settings.merchantInfo,
            transactionInfo: settings.transactionInfo,
            allowedCardNetworks: settings.allowedCardNetworks,
            allowCreditCards: settings.allowCreditCards,
            allowPrepaidCards: settings.allowPrepaidCards,
            buttonOptions: {
                buttonColor: settings.buttonOptions.buttonColor,
                buttonSizeMode: settings.buttonOptions.buttonSize,
            },
            // onPaymentAuthorizedCallback: async (paymentData) => {
            //     return await this.unzerPayment?.submit()
            //         .then(response => this.onPaymentSubmit(response))
            //         .catch(err => this.logError(err));
            // }
        };

        this.unzerPayment.setGooglePayData(googlePayData);
    }
}