import BaseComponent from './base';

export default class extends BaseComponent {
    mounted() {
        super.mounted();
        this.inputNames.resourceId = 'unzer-payment-type-id';

        const applePayData = JSON.parse(this.el.dataset.paymentDataRequest) || {};
        // applePayData.onPaymentAuthorizedCallback = async(paymentData) => {
        //     return await this.unzerPayment?.submit()
        //         .then(response => this.onPaymentSubmit(response))
        //         .catch(err => this.logError(err));
        // };

        this.unzerPayment.setApplePayData(applePayData);
    }
}