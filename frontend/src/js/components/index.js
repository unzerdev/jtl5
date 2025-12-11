/**
 * Unzer UI Components V2
 * @see https://docs.unzer.com/online-payments/ui-component-v2/
 */
import BaseComponent from "./base";
import ErrorHandler from "../utils/errors";
import GooglePayComponent from "./googlepay";
import ApplePayComponent from "./applepay";
import KlarnaComponent from "./klarna";

// Init component
document.addEventListener('DOMContentLoaded', function() {
    /** @type {NodeListOf<HTMLElement>} */
    const components = document.querySelectorAll('[data-unzer-ui-component]');

    if (!components || components.length === 0) {
        return;
    }

    components.forEach(el => {
        try {
            const settings = JSON.parse(el.dataset.unzerUiComponent) || {};

            if (settings.component === 'google-pay') {
                new GooglePayComponent(el, settings);
            } else if (settings.component === 'apple-pay') {
                new ApplePayComponent(el, settings);
            } else if (settings.component === 'klarna') {
                new KlarnaComponent(el, settings);
            } else {
                new BaseComponent(el, settings);
            }
        } catch(err) {
            (new ErrorHandler).show(err);
            console.error(err);
        }
    });
});
