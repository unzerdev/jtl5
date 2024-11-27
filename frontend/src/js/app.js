import ApplePay from './payments/applepay';
import ApplePayV2 from './payments/applepay_v2';
import UnzerPayment from "./payments/general";
import GooglePay from './payments/googlepay';
import Installment from "./payments/instalment";

window.HpPayment = UnzerPayment;
window.HpInstalment = Installment;
window.UnzerApplePay = ApplePay;
window.UnzerApplePayV2 = ApplePayV2;
window.UnzerGooglePay = GooglePay;
