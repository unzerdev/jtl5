import BaseComponent from './base';

export default class extends BaseComponent {
    mounted() {
        super.mounted();

        const locale = new Intl.Locale(navigator.language || navigator.userLanguage || 'en-GB');

        this.unzerPayment.updateCustomerData({
            "language": locale.language
        });
    }
}