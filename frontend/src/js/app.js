import ApplePay from './payments/applepay';
import UnzerPayment from "./payments/general";
import Installment from "./payments/instalment";

window.HpPayment = UnzerPayment;
window.HpInstalment = Installment;
window.UnzerApplePay = ApplePay;
