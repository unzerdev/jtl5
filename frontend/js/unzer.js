(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

var _applepay = _interopRequireDefault(require("./payments/applepay"));

var _general = _interopRequireDefault(require("./payments/general"));

var _instalment = _interopRequireDefault(require("./payments/instalment"));

window.HpPayment = _general["default"];
window.HpInstalment = _instalment["default"];
window.UnzerApplePay = _applepay["default"];

},{"./payments/applepay":2,"./payments/general":3,"./payments/instalment":4,"@babel/runtime/helpers/interopRequireDefault":10}],2:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _errors = _interopRequireDefault(require("../utils/errors"));

var _debugging = _interopRequireDefault(require("../utils/debugging"));

/** @type {ApplePaySnippets} */
var ApplePaySnippetsDefaults = {
  NOT_SUPPORTED: "This device does not support Apple Pay!",
  CANCEL_BY_USER: "Canceled payment process by user!"
};

var ApplePay = /*#__PURE__*/function () {
  /**
   * Similiar to the HeidelpayPayment class but slightly differently
   * (apple pay works differently, so yeah, custom class it is :D)
   *
   * @class
   * @param {String} pubKey
   * @param {ApplePayPaymentRequest} applePayPaymentRequest
   * @param {ApplePaySnippets} snippets
   * @param {ApplePaySettings} settings
   */
  function ApplePay(pubKey, applePayPaymentRequest, snippets, settings) {
    (0, _classCallCheck2["default"])(this, ApplePay);

    /** @type {ApplePaySettings} */
    this.settings = settings || {};
    /** @type {ApplePaySnippets} */

    this.snippets = Object.assign(ApplePaySnippetsDefaults, snippets);
    var options = {
      locale: this.settings.locale || 'de-DE'
    };
    /** @type {ApplePayPaymentRequest} */

    this.applePayPaymentRequest = applePayPaymentRequest;
    this.unzerInstance = new unzer(pubKey, options);
    this.errorHandler = new _errors["default"](this.settings.$errorContainer, this.settings.$errorMessage);
    this.debugging = new _debugging["default"]($('.unzerUI'));
    window.UNZER_DEBUG = !!this.unzerInstance._isSandbox || this.unzerInstance.config.hasSandboxKey; // Enable Debugging in sandbox mode

    if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
      this.unsupportedDevice();
      return;
    }
    /** @type {HTMLElement} form Form in which the customer enters additional details */


    this.form = this.settings.form || document.getElementById('form_payment_extra'); // Register Events

    this.initPaymentType = this.initPaymentType.bind(this); // it's a trick! needed in order to overcome the remove event listener

    this.form.addEventListener('submit', this.initPaymentType);
    $('.apple-pay-button').on('click', this.initPaymentType.bind(this));
  }
  /**
   * Init the payment type in this case means starting the apple pay session
   * @param {Event} event
   */


  (0, _createClass2["default"])(ApplePay, [{
    key: "initPaymentType",
    value: function initPaymentType(event) {
      var _this = this;

      event.preventDefault();

      if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
        this.unsupportedDevice();
        return;
      } // We adhere to Apple Pay version 6 to handle the payment request.


      var session = new ApplePaySession(3, this.applePayPaymentRequest);
      this.debugging.log('[> Init Payment Type]', {
        paymentRequest: this.applePayPaymentRequest
      });

      session.onvalidatemerchant = function (event) {
        _this.merchantValidationCallback(event, session);
      };

      session.onpaymentauthorized = function (event) {
        _this.applePayAuthorizedCallback(event, session);
      };

      session.oncancel = function (event) {
        _this.debugging.log('[> Cancel]', {
          event: event
        });

        _this.errorHandler.show(_this.snippets.CANCEL_BY_USER);
      };

      session.begin();
    }
    /**
     * Call the merchant validation in the server-side integration (apple_pay_merchantvalidation)
     * @param {Event} event
     * @param {ApplePaySession} session
     */

  }, {
    key: "merchantValidationCallback",
    value: function merchantValidationCallback(event, session) {
      var _this2 = this;

      var validationUrl = JSON.stringify(event.validationURL);
      this.debugging.log('[> Merchant Validation]', {
        event: event
      });
      $.ajax({
        'url': $.evo.io().options.ioUrl,
        'method': 'POST',
        'dataType': 'json',
        'data': 'io={"name":"apple_pay_merchantvalidation", "params":[' + validationUrl + ']}'
      }).done(function (validationResponse) {
        _this2.debugging.log('[> Merchant Validation Response]', validationResponse);

        try {
          session.completeMerchantValidation(validationResponse);
        } catch (e) {
          alert(e.message);
        }
      }).fail(function (error) {
        _this2.debugging.log('[> Merchant Validation Error]', error);

        _this2.errorHandler.show(JSON.stringify(error.statusText));

        session.abort();
      });
    }
    /**
     * Create Apple Pay resource with unzer and save the resource id to charge it later.
     *
     * We do this here via AJAX instead of the validateAdditional method in the payment method to set the apple pay
     * session state accordingly.
     *
     * @param {Event} event
     * @param {ApplePaySession} session
     */

  }, {
    key: "applePayAuthorizedCallback",
    value: function applePayAuthorizedCallback(event, session) {
      // Get payment data from event.
      // "event.payment" also contains contact information, if they were set via Apple Pay.
      var self = this;
      var unzerApplePayInstance = this.unzerInstance.ApplePay();
      var paymentData = event.payment.token.paymentData;
      this.debugging.log('[> Payment Authorization]', {
        unzerApplePayInstance: unzerApplePayInstance,
        event: event,
        paymentData: paymentData
      }); // Create an Unzer instance with your public key

      unzerApplePayInstance.createResource(paymentData).then(function (createdResource) {
        // Hand over the type ID to your backend.
        var typeId = JSON.stringify(createdResource.id);
        $.ajax({
          'url': $.evo.io().options.ioUrl,
          'method': 'POST',
          'dataType': 'json',
          'data': 'io={"name":"apple_pay_payment_authorized", "params":[' + typeId + ']}'
        }).done(function (result) {
          // Handle the transaction respone from backend.
          self.debugging.log('[> Payment Authorization Response]', {
            result: result,
            typeId: typeId
          });
          var status = result.transactionStatus;

          if (status === 'success' || status === 'pending') {
            session.completePayment({
              status: window.ApplePaySession.STATUS_SUCCESS
            }); // Append Payment Resource Id

            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'paymentData[resourceId]');
            hiddenInput.setAttribute('value', typeId);
            self.form.appendChild(hiddenInput); // Submitting the form

            self.form.removeEventListener('submit', self.initPaymentType);
            self.form.submit();
          } else {
            self.abortPaymentSession(session);
          }
        }).fail(function (error) {
          self.errorHandler.show(error.statusText);
          self.abortPaymentSession(session);
        });
      })["catch"](function (error) {
        self.debugging.log('[> Payment Authorization Error]', error);
        self.errorHandler.show(error.message);
        self.abortPaymentSession(session);
      });
    }
    /**
     * Handle Unsupported devices
     */

  }, {
    key: "unsupportedDevice",
    value: function unsupportedDevice() {
      this.settings.submitButton.disabled = true;
      this.settings.submitButton.ariaDisabled = true;
      this.errorHandler.show(this.snippets.NOT_SUPPORTED);
    }
    /**
     * abort current payment session.
     * @param {ApplePaySession} session
     */

  }, {
    key: "abortPaymentSession",
    value: function abortPaymentSession(session) {
      this.debugging.log('[> Abort Payment Session]', {
        status: window.ApplePaySession.STATUS_FAILURE
      });
      session.completePayment({
        status: window.ApplePaySession.STATUS_FAILURE
      });
      session.abort();
    }
  }]);
  return ApplePay;
}();

exports["default"] = ApplePay;

},{"../utils/debugging":5,"../utils/errors":6,"@babel/runtime/helpers/classCallCheck":7,"@babel/runtime/helpers/createClass":8,"@babel/runtime/helpers/interopRequireDefault":10}],3:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _errors = _interopRequireDefault(require("../utils/errors"));

var UnzerPayment = /*#__PURE__*/function () {
  /**
   * Heidelpay Payment Class
   *
   * @param {string} pubKey Public Key
   * @param {string} type Payment Type
   * @param {PaymentSettings} settings
   */
  function UnzerPayment(pubKey, type, settings) {
    (0, _classCallCheck2["default"])(this, UnzerPayment);

    /** @type {PaymentSettings} */
    this.settings = settings || {};
    var options = {
      locale: this.settings.locale || 'de-DE'
    };
    this.unzerInstance = new unzer(pubKey, options);
    this.errorHandler = new _errors["default"](this.settings.$errorContainer, this.settings.$errorMessage);
    /** @type {?string} customerId */

    this.customerId = settings.customerId || null;
    /** @type {{createCustomer: Function, updateCustomer: Function}|null} customerResource */

    this.customerResource = null;
    /** @type {{createResource: Function}} paymentType */

    this.paymentType = this.initPaymentType(type);
    /** @type {HTMLElement} form Form in which the customer enters additional details */

    this.form = this.settings.form || document.getElementById('form_payment_extra'); // Register Events

    this.handleFormSubmit = this.handleFormSubmit.bind(this); // it's a trick! needed in order to overcome the remove event listener

    this.form.addEventListener('submit', this.handleFormSubmit);

    if (this.settings.autoSubmit) {
      // this.form.dispatchEvent(new Event('submit')); // Causes endless redirects in some browsers like FF, so we call the callback directly...
      this.handleFormSubmit(new Event('submit'));
    }
  }
  /**
   * Init Payment Type
   *
   * @param {string} type
   * @returns {object} payment type
   * @throws Error if there is an unkown payment type
   */


  (0, _createClass2["default"])(UnzerPayment, [{
    key: "initPaymentType",
    value: function initPaymentType(type) {
      switch (type) {
        case UnzerPayment.PAYMENT_TYPES.CARD:
          return this.createCard();

        case UnzerPayment.PAYMENT_TYPES.INVOICE:
          return this.createInvoice();

        case UnzerPayment.PAYMENT_TYPES.INVOICE_GUARANTEED:
          return this.createInvoiceGuaranteed();

        case UnzerPayment.PAYMENT_TYPES.INVOICE_FACTORING:
          return this.createInvoiceFactoring();

        case UnzerPayment.PAYMENT_TYPES.SEPA:
          return this.createSepa();

        case UnzerPayment.PAYMENT_TYPES.SEPA_GUARANTEED:
          return this.createSepaGuaranteed();

        case UnzerPayment.PAYMENT_TYPES.PAYPAL:
          return this.createPaypal();

        case UnzerPayment.PAYMENT_TYPES.SOFORT:
          return this.createSofort();

        case UnzerPayment.PAYMENT_TYPES.GIROPAY:
          return this.createGiropay();

        case UnzerPayment.PAYMENT_TYPES.PRZELEWY24:
          return this.createPrzelewy24();

        case UnzerPayment.PAYMENT_TYPES.IDEAL:
          return this.createIdeal();

        case UnzerPayment.PAYMENT_TYPES.PREPAYMENT:
          return this.createPrepayment();

        case UnzerPayment.PAYMENT_TYPES.EPS:
          return this.createEPS();

        case UnzerPayment.PAYMENT_TYPES.FLEXIPAY_DIRECT:
          return this.createFlexiPayDirect();

        case UnzerPayment.PAYMENT_TYPES.ALIPAY:
          return this.createAlipay();

        case UnzerPayment.PAYMENT_TYPES.WECHAT_PAY:
          return this.createWeChatPay();

        case UnzerPayment.PAYMENT_TYPES.HIRE_PURCHASE:
          return this.createHirePurchase();

        case UnzerPayment.PAYMENT_TYPES.PAYLATER_INVOICE:
          return this.createPaylaterInvoice();

        case UnzerPayment.PAYMENT_TYPES.BANCONTACT:
          return this.createBancontact();

        default:
          throw new Error('Unkown Payment Type: ' + type);
      }
    }
    /**
     * Handle the form submit
     *
     * @param {Event} event Submit Event
     */

  }, {
    key: "handleFormSubmit",
    value: function handleFormSubmit(event) {
      var self = this;
      event.preventDefault(); // Creating a Payment resource and (optional) Customer Resource

      var resources = [this.paymentType.createResource()];

      if (this.customerResource) {
        resources.push(this.customerId ? this.customerResource.updateCustomer() : this.customerResource.createCustomer());
      }

      Promise.all(resources).then(function (result) {
        // Append Payment Resource Id
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'paymentData[resourceId]');
        hiddenInput.setAttribute('value', result[0].id);
        self.form.appendChild(hiddenInput); // Append Customer Id

        if (result.length >= 2) {
          var hiddenCstInput = document.createElement('input');
          hiddenCstInput.setAttribute('type', 'hidden');
          hiddenCstInput.setAttribute('name', 'paymentData[customerId]');
          hiddenCstInput.setAttribute('value', result[1].id);
          self.form.appendChild(hiddenCstInput);
        } // Submitting the form


        self.form.removeEventListener('submit', self.handleFormSubmit);
        self.form.submit();
      })["catch"](function (error) {
        self.errorHandler.show(error.message);
      });
    }
    /**
     * Create (or update) customer resource.
     *
     * @param {?String} paymentTypeName
     * @see https://docs.heidelpay.com/docs/customer-ui-integration
     * @returns {{createCustomer: Function, updateCustomer: Function}} Customer Resource
     */

  }, {
    key: "createCustomer",
    value: function createCustomer() {
      var paymentTypeName = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      var Customer = this.settings.isB2B ? this.unzerInstance.B2BCustomer() : this.unzerInstance.Customer();
      var customerObj = this.settings.customer || {};
      var continueButton = this.settings.submitButton || document.getElementById("submit-button");
      var options = {
        containerId: 'customer',
        showInfoBox: false,
        showHeader: false
      };

      if (paymentTypeName) {
        options.paymentTypeName = paymentTypeName;
      }

      Customer.initFormFields(customerObj);
      Customer.addEventListener('validate', function (e) {
        if (e.success) {
          continueButton.removeAttribute('disabled');
          return;
        }

        continueButton.setAttribute('disabled', true);
      });

      if (this.customerId) {
        options.fields = ['name', 'birthdate']; // if (this.settings.isB2B) {
        //     options = {containerId: 'customer'};
        // }

        Customer.update(this.customerId, options);
        return Customer;
      }

      Customer.create(options);
      return Customer;
    }
    /**
     * Create Paylayter Invoice Payment Type
     *
     * @see https://docs.unzer.com/payment-methods/unzer-invoice-upl/accept-unzer-invoice-upl-ui-component/
     * @returns {{createResource: Function}}
     */

  }, {
    key: "createPaylaterInvoice",
    value: function createPaylaterInvoice() {
      this.customerResource = this.createCustomer('paylater-invoice');
      var continueButton = this.settings.submitButton || document.getElementById("submit-button");
      var paylaterInvoice = this.unzerInstance.PaylaterInvoice();
      paylaterInvoice.create({
        containerId: 'paylater-invoice',
        customerType: this.settings.isB2B ? 'B2B' : 'B2C'
      });
      paylaterInvoice.addEventListener('change', function (e) {
        if (e.success) {
          continueButton.removeAttribute('disabled');
          return;
        }

        continueButton.setAttribute('disabled', true);
      });
      return paylaterInvoice;
    }
    /**
     * Create Bancontact Payment Type
     *
     * @see https://docs.unzer.com/payment-methods/bancontact/accept-bancontact-ui-component/
     * @returns {{createResource: Function}}
     */

  }, {
    key: "createBancontact",
    value: function createBancontact() {
      var bancontact = this.unzerInstance.Bancontact();
      var styling = {
        fontSize: null,
        fontColor: null,
        fontFamily: null
      };

      if (this.settings.styling) {
        styling.fontColor = this.settings.styling.fontColor || null;
        styling.fontSize = this.settings.styling.fontSize || null;
        styling.fontFamily = this.settings.styling.fontFamily || null;
      }

      bancontact.create('holder', {
        containerId: 'bancontact-holder',
        fontSize: styling.fontSize,
        fontColor: styling.fontColor,
        fontFamily: styling.fontFamily
      });
      return bancontact;
    }
    /**
     * Create a new Card Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/credit-card-ui-integration
     * @returns {{createResource: Function}} Card Payment Type
     */

  }, {
    key: "createCard",
    value: function createCard() {
      var Card = this.unzerInstance.Card();
      var styling = {
        fontSize: null,
        fontColor: null,
        fontFamily: null
      };

      if (this.settings.styling) {
        styling.fontColor = this.settings.styling.fontColor || null;
        styling.fontSize = this.settings.styling.fontSize || null;
        styling.fontFamily = this.settings.styling.fontFamily || null;
      }

      Card.create('number', {
        containerId: 'card-element-id-number',
        onlyIframe: false,
        fontSize: styling.fontSize,
        fontColor: styling.fontColor,
        fontFamily: styling.fontFamily
      });
      Card.create('expiry', {
        containerId: 'card-element-id-expiry',
        onlyIframe: false,
        fontSize: styling.fontSize,
        fontColor: styling.fontColor,
        fontFamily: styling.fontFamily
      });
      Card.create('cvc', {
        containerId: 'card-element-id-cvc',
        onlyIframe: false,
        fontSize: styling.fontSize,
        fontColor: styling.fontColor // fontFamily: styling.fontFamily // messes with hidden font in firefox

      }); // Enable pay button initially

      var self = this;
      var formFieldValid = {};
      /** @type {HTMLElement} continueButton */

      var continueButton = self.settings.submitButton || document.getElementById("submit-button");
      continueButton.setAttribute('disabled', true);

      var eventHandlerCardInput = function eventHandlerCardInput(e) {
        if (e.success) {
          formFieldValid[e.type] = true;
          self.errorHandler.hide();
        }

        if (e.error) {
          formFieldValid[e.type] = false;
          self.errorHandler.show(e.error);
        }

        if (formFieldValid.number && formFieldValid.expiry && formFieldValid.cvc) {
          continueButton.removeAttribute('disabled');
          return;
        }

        continueButton.setAttribute('disabled', true);
      };

      Card.addEventListener('change', eventHandlerCardInput);
      return Card;
    }
    /**
     * Create a new Invoice Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */

  }, {
    key: "createInvoice",
    value: function createInvoice() {
      return this.unzerInstance.Invoice();
    }
    /**
     * Create a new Invoice Guaranteed Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */

  }, {
    key: "createInvoiceGuaranteed",
    value: function createInvoiceGuaranteed() {
      this.customerResource = this.createCustomer();
      return this.unzerInstance.InvoiceSecured();
    }
    /**
     * Create a new Invoice Factoring Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/invoice-ui-integration
     * @returns {{createResource: Function}} Invoice Payment Type
     */

  }, {
    key: "createInvoiceFactoring",
    value: function createInvoiceFactoring() {
      this.customerResource = this.createCustomer();
      return this.unzerInstance.InvoiceSecured();
    }
    /**
     * Create a new SEPA Direct Debit Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/sepa-direct-debit-ui-integration
     * @returns {{createResource: Function}} SEPA Direct Debit Payment Type
     */

  }, {
    key: "createSepa",
    value: function createSepa() {
      var _this = this;

      var Sepa = this.unzerInstance.SepaDirectDebit();
      Sepa.create('sepa-direct-debit', {
        containerId: 'sepa-IBAN'
      });
      /** @type {HTMLElement} continueButton */

      var continueButton = this.settings.submitButton || document.getElementById("submit-button");
      continueButton.setAttribute('disabled', true);
      Sepa.addEventListener('change', function (e) {
        if (e.success) {
          continueButton.removeAttribute('disabled');

          _this.errorHandler.hide();

          return;
        }

        continueButton.setAttribute('disabled', true);
      });
      return Sepa;
    }
    /**
     * Create a new SEPA Direct Debit (guaranteed) Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/sepa-direct-debit-ui-integration
     * @returns {{createResource: Function}} SEPA Direct Debit (guaranteed) Payment Type
     */

  }, {
    key: "createSepaGuaranteed",
    value: function createSepaGuaranteed() {
      var _this2 = this;

      var SepaGuaranteed = this.unzerInstance.SepaDirectDebitSecured();
      SepaGuaranteed.create('sepa-direct-debit-guaranteed', {
        containerId: 'sepa-guaranteed-IBAN'
      });
      /** @type {HTMLElement} continueButton */

      var continueButton = this.settings.submitButton || document.getElementById("submit-button");
      continueButton.setAttribute('disabled', true);
      SepaGuaranteed.addEventListener('change', function (e) {
        if (e.success) {
          continueButton.removeAttribute('disabled');

          _this2.errorHandler.hide();

          return;
        }

        continueButton.setAttribute('disabled', true);
      });
      this.customerResource = this.createCustomer();
      return SepaGuaranteed;
    }
    /**
     * Create a new PayPal Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/paypal-ui-integration
     * @returns {{createResource: Function}} Papal Payment Type
     */

  }, {
    key: "createPaypal",
    value: function createPaypal() {
      var Paypal = this.unzerInstance.Paypal();
      Paypal.create('email', {
        containerId: 'paypal-element-email'
      });
      return Paypal;
    }
    /**
     * Create a new SOFORT Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#sofort
     * @returns {{createResource: Function}} Sofort Payment Type
     */

  }, {
    key: "createSofort",
    value: function createSofort() {
      return this.unzerInstance.Sofort();
    }
    /**
     * Create a new Giropay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#giropay
     * @returns {{createResource: Function}} Giropay Payment Type
     */

  }, {
    key: "createGiropay",
    value: function createGiropay() {
      return this.unzerInstance.Giropay();
    }
    /**
     * Create a new Przelewy24 Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#przelewy24
     * @returns {{createResource: Function}} Przelewy24 Payment Type
     */

  }, {
    key: "createPrzelewy24",
    value: function createPrzelewy24() {
      return this.unzerInstance.Przelewy24();
    }
    /**
     * Create a new iDEAL Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/ideal-ui-integration
     * @returns {{createResource: Function}} iDEAL Payment Type
     */

  }, {
    key: "createIdeal",
    value: function createIdeal() {
      var _this3 = this;

      var Ideal = this.unzerInstance.Ideal();
      Ideal.create('ideal', {
        containerId: 'ideal-element'
      });
      /** @type {HTMLElement} continueButton */

      var continueButton = this.settings.submitButton || document.getElementById("submit-button");
      continueButton.setAttribute('disabled', true);
      Ideal.addEventListener('change', function (e) {
        if (e.value) {
          continueButton.removeAttribute('disabled');

          _this3.errorHandler.hide();

          return;
        }

        continueButton.setAttribute('disabled', true);
      });
      return Ideal;
    }
    /**
     * Create a new Prepayment Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/prepayment-ui-integration
     * @returns {{createResource: Function}} Prepayment Payment Type
     */

  }, {
    key: "createPrepayment",
    value: function createPrepayment() {
      return this.unzerInstance.Prepayment();
    }
    /**
     * Create a new EPS Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/eps-ui-integration
     * @returns {{createResource: Function}} EPS Payment Type
     */

  }, {
    key: "createEPS",
    value: function createEPS() {
      var EPS = this.unzerInstance.EPS();
      EPS.create('eps', {
        containerId: 'eps-element'
      });
      return EPS;
    }
    /**
     * Create a new FlexiPay Direct Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#flexipay-direct
     * @returns {{createResource: Function}} Alipay Payment Type
     */

  }, {
    key: "createFlexiPayDirect",
    value: function createFlexiPayDirect() {
      return this.unzerInstance.FlexiPayDirect();
    }
    /**
     * Create a new Alipay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#alipay
     * @returns {{createResource: Function}} Alipay Payment Type
     */

  }, {
    key: "createAlipay",
    value: function createAlipay() {
      return this.unzerInstance.Alipay();
    }
    /**
     * Create an new WeChat Pay Payment Type.
     *
     * @see https://docs.heidelpay.com/docs/redirect-ui-integration#wechat-pay
     * @returns {{createResource: Function}} WeChat Pay Payment Type
     */

  }, {
    key: "createWeChatPay",
    value: function createWeChatPay() {
      return this.unzerInstance.Wechatpay();
    }
    /**
     * Create a new Hire Purchase Payment Type.
     *
     * @see https:://docs.heidelpay.com/docs/hire-purchase-ui-integration
     * @returns {{createResource: Function}} Hire Purchase Payment Type
     */

  }, {
    key: "createHirePurchase",
    value: function createHirePurchase() {
      var InstallmentSecured = this.unzerInstance.InstallmentSecured();
      var self = this;
      this.customerResource = this.createCustomer();
      /** @type {HTMLElement} continueButton */

      var continueButton = self.settings.submitButton || document.getElementById("submit-button");
      continueButton.setAttribute('style', 'display: none');
      continueButton.setAttribute('disabled', true);
      InstallmentSecured.create({
        containerId: 'hire-purchase-element',
        amount: this.settings.amount || null,
        currency: this.settings.currency || null,
        effectiveInterest: this.settings.effectiveInterest || null,
        orderDate: this.settings.orderDate || null
      }).then(function (data) {// if successful, notify the user that the list of installments was fetched successfully
        // in case you were using a loading element during the fetching process,
        // you can remove it inside this callback function
      })["catch"](function (response) {
        // sent an error message to the user (fetching installment list failed)
        var msg = '';
        console.error(response.message);
        response.error.details.forEach(function (err) {
          console.error('API-Error: ' + err.code);
          msg += err.customerMessage;
        });
        self.errorHandler.show(msg);
      }); // Listen to UI events

      InstallmentSecured.addEventListener('installmentSecuredEvent', function (e) {
        if (e.action === 'validate') {
          if (e.success) {
            continueButton.removeAttribute('disabled');
            return;
          }

          continueButton.setAttribute('disabled', true);
        }

        if (e.action === 'change-step') {
          if (e.currentSteep === 'plan-list') {
            continueButton.setAttribute('style', 'display: none');
            continueButton.setAttribute('disabled', true);
            return;
          }

          continueButton.setAttribute('style', 'display: inline-block');
        }
      });
      return InstallmentSecured;
    }
  }]);
  return UnzerPayment;
}();

exports["default"] = UnzerPayment;
(0, _defineProperty2["default"])(UnzerPayment, "PAYMENT_TYPES", {
  ALIPAY: 'Alipay',
  CARD: 'Card',
  EPS: 'EPS',
  FLEXIPAY_DIRECT: 'FlexiPay Direct',
  HIRE_PURCHASE: 'Hire Purchase',
  PAYPAL: 'Paypal',
  INVOICE: 'Invoice',
  INVOICE_FACTORING: 'Invoice Factoring',
  INVOICE_GUARANTEED: 'Invoice Guaranteed',
  SEPA: 'SEPA',
  SEPA_GUARANTEED: 'SEPA (guaranteed)',
  SOFORT: 'SOFORT',
  GIROPAY: 'Giropay',
  PRZELEWY24: 'Przelewy24',
  IDEAL: 'iDEAL',
  PREPAYMENT: 'Prepayment',
  WECHAT_PAY: 'WeChat Pay',
  PAYLATER_INVOICE: 'Paylater Invoice',
  BANCONTACT: 'Bancontact'
});

},{"../utils/errors":6,"@babel/runtime/helpers/classCallCheck":7,"@babel/runtime/helpers/createClass":8,"@babel/runtime/helpers/defineProperty":9,"@babel/runtime/helpers/interopRequireDefault":10}],4:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

/**
 * Heidelpay Installment Modal Window Handler
 *
 * @param {string} modalSelector
 * @param {HTMLElement} btn Submit Trigger
 * @param {JQuery<HTMLElement>} $form
 */
var Installment = function Installment(modalSelector, btn, $form) {
  var modal = $(modalSelector);
  btn.addEventListener('click', function () {
    $form.trigger('submit');
  });
  $form.on('submit', function (e) {
    if (!modal.is(':visible')) {
      e.preventDefault();
      modal.modal('show');
      return false;
    }

    return true;
  });
};

var _default = Installment;
exports["default"] = _default;

},{}],5:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var Debugging = /*#__PURE__*/function () {
  function Debugging($container) {
    (0, _classCallCheck2["default"])(this, Debugging);
    this.$container = $container;
    this.$log = null;

    if (!window.UNZER_DEBUG) {
      return;
    }

    this.createLogTemplate();
  }

  (0, _createClass2["default"])(Debugging, [{
    key: "log",
    value: function log(context) {
      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

      if (!window.UNZER_DEBUG) {
        return;
      }

      if (!this.$log) {
        this.createLogTemplate();
      }

      this.$log.append('<li><strong>' + context + '</strong><pre>' + JSON.stringify(data, null, '  ') + '</pre></li>');
    }
  }, {
    key: "createLogTemplate",
    value: function createLogTemplate() {
      this.$container.append($('<div class="debug-log card card-body bg-light"><ul class="list-unstyled"></ul></div>'));
      this.$log = this.$container.find('.debug-log > ul');
    }
  }]);
  return Debugging;
}();

exports["default"] = Debugging;

},{"@babel/runtime/helpers/classCallCheck":7,"@babel/runtime/helpers/createClass":8,"@babel/runtime/helpers/interopRequireDefault":10}],6:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var ErrorHandler = /*#__PURE__*/function () {
  /**
   * @param {JQuery<HTMLElement>|null} $wrapper Wrapper for Container to display error messages in
   * @param {JQuery<HTMLElement>|null} $holder Container to display error messages in
   */
  function ErrorHandler($wrapper, $holder) {
    (0, _classCallCheck2["default"])(this, ErrorHandler);
    this.$wrapper = $wrapper || $('#error-container');
    this.$holder = $holder || this.$wrapper.find('.alert');
  }
  /**
   * Show Error message
   * @param {String} message
   */


  (0, _createClass2["default"])(ErrorHandler, [{
    key: "show",
    value: function show(message) {
      this.$wrapper.show();
      this.$holder.html(message);
    }
    /**
     * Hide error message
     */

  }, {
    key: "hide",
    value: function hide() {
      this.$wrapper.hide();
      this.$holder.html();
    }
  }]);
  return ErrorHandler;
}();

exports["default"] = ErrorHandler;

},{"@babel/runtime/helpers/classCallCheck":7,"@babel/runtime/helpers/createClass":8,"@babel/runtime/helpers/interopRequireDefault":10}],7:[function(require,module,exports){
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],8:[function(require,module,exports){
function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  Object.defineProperty(Constructor, "prototype", {
    writable: false
  });
  return Constructor;
}

module.exports = _createClass, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],9:[function(require,module,exports){
function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],10:[function(require,module,exports){
function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {
    "default": obj
  };
}

module.exports = _interopRequireDefault, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}]},{},[1])
//# sourceMappingURL=unzer.js.map
