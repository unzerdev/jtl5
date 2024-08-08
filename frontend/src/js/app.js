import ApplePay from './payments/applepay';
import UnzerPayment from "./payments/general";
import GooglePay from './payments/googlepay';
import Installment from "./payments/instalment";

window.HpPayment = UnzerPayment;
window.HpInstalment = Installment;
window.UnzerApplePay = ApplePay;
window.UnzerGooglePay = GooglePay;
