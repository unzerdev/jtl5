import ErrorHandler from "../utils/errors";

export default class
{
    inputNames = {
        'resourceId': 'paymentData[resourceId]',
        'customerId': 'paymentData[customerId]',
        'threatMetrixId': 'paymentData[threatMetrixId]'
    };

    unzerCheckout = null;
    unzerPayment = null;

    /**
     *@param {HTMLElement} el
     * @param {{component: string, autoSubmit: boolean, ?customer: object, ?basket:object, ?submitButton: string, ?isB2B: boolean}} settings
     */
    constructor(el, settings) {
        this.el = el;
        this.settings = settings;
        this.customElement = "unzer-" + settings.component;
        this.submitButton = document.querySelector(settings.submitButton);
        this.errorHandler = new ErrorHandler();

        this.boot();
        this.register();
    }

    boot() {
        const wrapper = document.createElement('unzer-checkout');

        if (this.submitButton) {
            // Wrap Submit Button with <unzer-checkout /> for
            // "automatic handling for enabling/disabling the submit button depending on the current status,
            // and showing/hiding of brand icons."
            wrapper.id = "unzer-checkout-wrapper"
            this.submitButton.parentNode.insertBefore(wrapper, this.submitButton);
            wrapper.appendChild(this.submitButton);
            this.submitButton.id = 'unzerUiComponentCheckoutBtn';
        } else {
            this.el.appendChild(wrapper);
        }
    }

    register() {
        // form
        this.submitButton?.addEventListener('click', e => {
            e.preventDefault();
            // this.el.closest('form').submit();
        });

        // this.#el.closest('form').addEventListener('submit', (e) => {
        //     console.log('on submit');
        //     e.preventDefault();
        //     document.querySelector('unzer-checkout > button[type="submit"]').click();
        // })

        // Wait for ui components to be loaded
        Promise.all([
            customElements.whenDefined("unzer-payment"),
            customElements.whenDefined("unzer-checkout"),
            customElements.whenDefined(this.customElement)
        ]).then(() => {
            this.unzerPayment = document.querySelector('unzer-payment');
            this.unzerCheckout = document.querySelector('unzer-checkout');
            this.mounted();
        })
        .catch((err) => this.logError(err));
    }

    mounted() {
        // this.unzerCheckout.autoDisable = true;
        this.unzerCheckout.onPaymentSubmit = this.onPaymentSubmit.bind(this);

        // Small style fix if needed
        if (this.unzerCheckout.shadowRoot.querySelector('.unzer-checkout')) {
            this.unzerCheckout.shadowRoot.querySelector('.unzer-checkout').style.width = '100%';
        }

        // Set customer data if available
        if (this.settings.customer) {
            if (this.settings.isB2B) {
                this.settings.customer.customerSettings = {'type': 'B2B'};
            }

            this.unzerPayment.setCustomerData(this.settings.customer);
        }

        // Set basket data if available
        if (this.settings.basket) {
            this.unzerPayment.setBasketData(this.settings.basket);
        }

        // Autosubmit form
        if (this.settings.autoSubmit) {
            // document.querySelector('unzer-checkout > button[type="submit"]').click();
            this.submitButton?.click();
        }
    }

    onPaymentSubmit(response) {
        try {
            if (this.settings.component === 'card' && response.submitResponse?.success !== true) {
                if (!response.submitResponse || response.submitResponse.status !== 'SUCCESS') {
                    throw new Error(response.submitResponse.message ?? 'Failed payment response: ' + JSON.stringify(response, null, 2));
                }
            } else {
                if (!response.submitResponse || !response.submitResponse.success) {
                    throw new Error(response.submitResponse.message ?? 'Failed payment response: ' + JSON.stringify(response, null, 2));
                }
            }

            console.log({ response });

            // Append payment resource id
            const resourceIdInput = document.createElement('input');
            resourceIdInput.setAttribute('type', 'hidden');
            resourceIdInput.setAttribute('name', this.inputNames.resourceId);
            resourceIdInput.setAttribute('value', response.submitResponse.data.id);
            this.el.appendChild(resourceIdInput);

            // append customer id
            if (response.customerResponse && response.customerResponse.success) {
                const customerIdInput = document.createElement('input');
                customerIdInput.setAttribute('type', 'hidden');
                customerIdInput.setAttribute('name', this.inputNames.customerId);
                customerIdInput.setAttribute('value', response.customerResponse.data.id);
                this.el.appendChild(customerIdInput);
            }

            // append threatmetrix id
            if (response.threatMetrixId) {
                const threatmetrixIdInput = document.createElement('input');
                threatmetrixIdInput.setAttribute('type', 'hidden');
                threatmetrixIdInput.setAttribute('name', this.inputNames.threatMetrixId);
                threatmetrixIdInput.setAttribute('value', response.threatMetrixId);
                this.el.appendChild(threatmetrixIdInput);
            }

            // submit ids to server side intergration to perform payment transaction
            this.el.closest('form').submit();
        } catch(err) {
            this.logError(err);
        }
    }

    logError(err) {
        this.errorHandler.show(err);
        console.error('Unzer UI Component Error', {err})
    }
}