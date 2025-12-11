(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _get2 = _interopRequireDefault(require("@babel/runtime/helpers/get"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _base = _interopRequireDefault(require("./base"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2["default"])(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

var _default = /*#__PURE__*/function (_BaseComponent) {
  (0, _inherits2["default"])(_default, _BaseComponent);

  var _super = _createSuper(_default);

  function _default() {
    (0, _classCallCheck2["default"])(this, _default);
    return _super.apply(this, arguments);
  }

  (0, _createClass2["default"])(_default, [{
    key: "mounted",
    value: function mounted() {
      (0, _get2["default"])((0, _getPrototypeOf2["default"])(_default.prototype), "mounted", this).call(this);
      this.inputNames.resourceId = 'unzer-payment-type-id';
      var applePayData = JSON.parse(this.el.dataset.paymentDataRequest) || {}; // applePayData.onPaymentAuthorizedCallback = async(paymentData) => {
      //     return await this.unzerPayment?.submit()
      //         .then(response => this.onPaymentSubmit(response))
      //         .catch(err => this.logError(err));
      // };

      this.unzerPayment.setApplePayData(applePayData);
    }
  }]);
  return _default;
}(_base["default"]);

exports["default"] = _default;

},{"./base":2,"@babel/runtime/helpers/classCallCheck":8,"@babel/runtime/helpers/createClass":9,"@babel/runtime/helpers/get":11,"@babel/runtime/helpers/getPrototypeOf":12,"@babel/runtime/helpers/inherits":13,"@babel/runtime/helpers/interopRequireDefault":14,"@babel/runtime/helpers/possibleConstructorReturn":15}],2:[function(require,module,exports){
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

var _default = /*#__PURE__*/function () {
  /**
   *@param {HTMLElement} el
   * @param {{component: string, autoSubmit: boolean, ?customer: object, ?basket:object, ?submitButton: string, ?isB2B: boolean}} settings
   */
  function _default(el, settings) {
    (0, _classCallCheck2["default"])(this, _default);
    (0, _defineProperty2["default"])(this, "inputNames", {
      'resourceId': 'paymentData[resourceId]',
      'customerId': 'paymentData[customerId]',
      'threatMetrixId': 'paymentData[threatMetrixId]'
    });
    (0, _defineProperty2["default"])(this, "unzerCheckout", null);
    (0, _defineProperty2["default"])(this, "unzerPayment", null);
    this.el = el;
    this.settings = settings;
    this.customElement = "unzer-" + settings.component;
    this.submitButton = document.querySelector(settings.submitButton);
    this.errorHandler = new _errors["default"]();
    this.boot();
    this.register();
  }

  (0, _createClass2["default"])(_default, [{
    key: "boot",
    value: function boot() {
      var wrapper = document.createElement('unzer-checkout');

      if (this.submitButton) {
        // Wrap Submit Button with <unzer-checkout /> for
        // "automatic handling for enabling/disabling the submit button depending on the current status,
        // and showing/hiding of brand icons."
        wrapper.id = "unzer-checkout-wrapper";
        this.submitButton.parentNode.insertBefore(wrapper, this.submitButton);
        wrapper.appendChild(this.submitButton);
        this.submitButton.id = 'unzerUiComponentCheckoutBtn';
      } else {
        this.el.appendChild(wrapper);
      }
    }
  }, {
    key: "register",
    value: function register() {
      var _this$submitButton,
          _this = this;

      // form
      (_this$submitButton = this.submitButton) === null || _this$submitButton === void 0 ? void 0 : _this$submitButton.addEventListener('click', function (e) {
        e.preventDefault(); // this.el.closest('form').submit();
      }); // this.#el.closest('form').addEventListener('submit', (e) => {
      //     console.log('on submit');
      //     e.preventDefault();
      //     document.querySelector('unzer-checkout > button[type="submit"]').click();
      // })
      // Wait for ui components to be loaded

      Promise.all([customElements.whenDefined("unzer-payment"), customElements.whenDefined("unzer-checkout"), customElements.whenDefined(this.customElement)]).then(function () {
        _this.unzerPayment = document.querySelector('unzer-payment');
        _this.unzerCheckout = document.querySelector('unzer-checkout');

        _this.mounted();
      })["catch"](function (err) {
        return _this.logError(err);
      });
    }
  }, {
    key: "mounted",
    value: function mounted() {
      // this.unzerCheckout.autoDisable = true;
      this.unzerCheckout.onPaymentSubmit = this.onPaymentSubmit.bind(this); // Small style fix if needed

      if (this.unzerCheckout.shadowRoot.querySelector('.unzer-checkout')) {
        this.unzerCheckout.shadowRoot.querySelector('.unzer-checkout').style.width = '100%';
      } // Set customer data if available


      if (this.settings.customer) {
        if (this.settings.isB2B) {
          this.settings.customer.customerSettings = {
            'type': 'B2B'
          };
        }

        this.unzerPayment.setCustomerData(this.settings.customer);
      } // Set basket data if available


      if (this.settings.basket) {
        this.unzerPayment.setBasketData(this.settings.basket);
      } // Autosubmit form


      if (this.settings.autoSubmit) {
        var _this$submitButton2;

        // document.querySelector('unzer-checkout > button[type="submit"]').click();
        (_this$submitButton2 = this.submitButton) === null || _this$submitButton2 === void 0 ? void 0 : _this$submitButton2.click();
      }
    }
  }, {
    key: "onPaymentSubmit",
    value: function onPaymentSubmit(response) {
      try {
        var _response$submitRespo;

        if (this.settings.component === 'card' && ((_response$submitRespo = response.submitResponse) === null || _response$submitRespo === void 0 ? void 0 : _response$submitRespo.success) !== true) {
          if (!response.submitResponse || response.submitResponse.status !== 'SUCCESS') {
            var _response$submitRespo2;

            throw new Error((_response$submitRespo2 = response.submitResponse.message) !== null && _response$submitRespo2 !== void 0 ? _response$submitRespo2 : 'Failed payment response: ' + JSON.stringify(response, null, 2));
          }
        } else {
          if (!response.submitResponse || !response.submitResponse.success) {
            var _response$submitRespo3;

            throw new Error((_response$submitRespo3 = response.submitResponse.message) !== null && _response$submitRespo3 !== void 0 ? _response$submitRespo3 : 'Failed payment response: ' + JSON.stringify(response, null, 2));
          }
        }

        console.log({
          response: response
        }); // Append payment resource id

        var resourceIdInput = document.createElement('input');
        resourceIdInput.setAttribute('type', 'hidden');
        resourceIdInput.setAttribute('name', this.inputNames.resourceId);
        resourceIdInput.setAttribute('value', response.submitResponse.data.id);
        this.el.appendChild(resourceIdInput); // append customer id

        if (response.customerResponse && response.customerResponse.success) {
          var customerIdInput = document.createElement('input');
          customerIdInput.setAttribute('type', 'hidden');
          customerIdInput.setAttribute('name', this.inputNames.customerId);
          customerIdInput.setAttribute('value', response.customerResponse.data.id);
          this.el.appendChild(customerIdInput);
        } // append threatmetrix id


        if (response.threatMetrixId) {
          var threatmetrixIdInput = document.createElement('input');
          threatmetrixIdInput.setAttribute('type', 'hidden');
          threatmetrixIdInput.setAttribute('name', this.inputNames.threatMetrixId);
          threatmetrixIdInput.setAttribute('value', response.threatMetrixId);
          this.el.appendChild(threatmetrixIdInput);
        } // submit ids to server side intergration to perform payment transaction


        this.el.closest('form').submit();
      } catch (err) {
        this.logError(err);
      }
    }
  }, {
    key: "logError",
    value: function logError(err) {
      this.errorHandler.show(err);
      console.error('Unzer UI Component Error', {
        err: err
      });
    }
  }]);
  return _default;
}();

exports["default"] = _default;

},{"../utils/errors":6,"@babel/runtime/helpers/classCallCheck":8,"@babel/runtime/helpers/createClass":9,"@babel/runtime/helpers/defineProperty":10,"@babel/runtime/helpers/interopRequireDefault":14}],3:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _get2 = _interopRequireDefault(require("@babel/runtime/helpers/get"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _base = _interopRequireDefault(require("./base"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2["default"])(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

var _default = /*#__PURE__*/function (_BaseComponent) {
  (0, _inherits2["default"])(_default, _BaseComponent);

  var _super = _createSuper(_default);

  function _default() {
    (0, _classCallCheck2["default"])(this, _default);
    return _super.apply(this, arguments);
  }

  (0, _createClass2["default"])(_default, [{
    key: "mounted",
    value: function mounted() {
      (0, _get2["default"])((0, _getPrototypeOf2["default"])(_default.prototype), "mounted", this).call(this);
      this.inputNames.resourceId = 'unzer-payment-type-id';
      var settings = JSON.parse(this.el.dataset.paymentDataRequest) || {};
      var googlePayData = {
        gatewayMerchantId: settings.gatewayMerchantId,
        merchantInfo: settings.merchantInfo,
        transactionInfo: settings.transactionInfo,
        allowedCardNetworks: settings.allowedCardNetworks,
        allowCreditCards: settings.allowCreditCards,
        allowPrepaidCards: settings.allowPrepaidCards,
        buttonOptions: {
          buttonColor: settings.buttonOptions.buttonColor,
          buttonSizeMode: settings.buttonOptions.buttonSize
        } // onPaymentAuthorizedCallback: async (paymentData) => {
        //     return await this.unzerPayment?.submit()
        //         .then(response => this.onPaymentSubmit(response))
        //         .catch(err => this.logError(err));
        // }

      };
      this.unzerPayment.setGooglePayData(googlePayData);
    }
  }]);
  return _default;
}(_base["default"]);

exports["default"] = _default;

},{"./base":2,"@babel/runtime/helpers/classCallCheck":8,"@babel/runtime/helpers/createClass":9,"@babel/runtime/helpers/get":11,"@babel/runtime/helpers/getPrototypeOf":12,"@babel/runtime/helpers/inherits":13,"@babel/runtime/helpers/interopRequireDefault":14,"@babel/runtime/helpers/possibleConstructorReturn":15}],4:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

var _base = _interopRequireDefault(require("./base"));

var _errors = _interopRequireDefault(require("../utils/errors"));

var _googlepay = _interopRequireDefault(require("./googlepay"));

var _applepay = _interopRequireDefault(require("./applepay"));

var _klarna = _interopRequireDefault(require("./klarna"));

/**
 * Unzer UI Components V2
 * @see https://docs.unzer.com/online-payments/ui-component-v2/
 */
// Init component
document.addEventListener('DOMContentLoaded', function () {
  /** @type {NodeListOf<HTMLElement>} */
  var components = document.querySelectorAll('[data-unzer-ui-component]');

  if (!components || components.length === 0) {
    return;
  }

  components.forEach(function (el) {
    try {
      var settings = JSON.parse(el.dataset.unzerUiComponent) || {};

      if (settings.component === 'google-pay') {
        new _googlepay["default"](el, settings);
      } else if (settings.component === 'apple-pay') {
        new _applepay["default"](el, settings);
      } else if (settings.component === 'klarna') {
        new _klarna["default"](el, settings);
      } else {
        new _base["default"](el, settings);
      }
    } catch (err) {
      new _errors["default"]().show(err);
      console.error(err);
    }
  });
});

},{"../utils/errors":6,"./applepay":1,"./base":2,"./googlepay":3,"./klarna":5,"@babel/runtime/helpers/interopRequireDefault":14}],5:[function(require,module,exports){
"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _get2 = _interopRequireDefault(require("@babel/runtime/helpers/get"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _base = _interopRequireDefault(require("./base"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2["default"])(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

var _default = /*#__PURE__*/function (_BaseComponent) {
  (0, _inherits2["default"])(_default, _BaseComponent);

  var _super = _createSuper(_default);

  function _default() {
    (0, _classCallCheck2["default"])(this, _default);
    return _super.apply(this, arguments);
  }

  (0, _createClass2["default"])(_default, [{
    key: "mounted",
    value: function mounted() {
      (0, _get2["default"])((0, _getPrototypeOf2["default"])(_default.prototype), "mounted", this).call(this);
      var locale = new Intl.Locale(navigator.language || navigator.userLanguage || 'en-GB');
      this.unzerPayment.updateCustomerData({
        "language": locale.language
      });
    }
  }]);
  return _default;
}(_base["default"]);

exports["default"] = _default;

},{"./base":2,"@babel/runtime/helpers/classCallCheck":8,"@babel/runtime/helpers/createClass":9,"@babel/runtime/helpers/get":11,"@babel/runtime/helpers/getPrototypeOf":12,"@babel/runtime/helpers/inherits":13,"@babel/runtime/helpers/interopRequireDefault":14,"@babel/runtime/helpers/possibleConstructorReturn":15}],6:[function(require,module,exports){
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

},{"@babel/runtime/helpers/classCallCheck":8,"@babel/runtime/helpers/createClass":9,"@babel/runtime/helpers/interopRequireDefault":14}],7:[function(require,module,exports){
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],8:[function(require,module,exports){
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],9:[function(require,module,exports){
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
},{}],10:[function(require,module,exports){
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
},{}],11:[function(require,module,exports){
var superPropBase = require("./superPropBase.js");

function _get() {
  if (typeof Reflect !== "undefined" && Reflect.get) {
    module.exports = _get = Reflect.get.bind(), module.exports.__esModule = true, module.exports["default"] = module.exports;
  } else {
    module.exports = _get = function _get(target, property, receiver) {
      var base = superPropBase(target, property);
      if (!base) return;
      var desc = Object.getOwnPropertyDescriptor(base, property);

      if (desc.get) {
        return desc.get.call(arguments.length < 3 ? target : receiver);
      }

      return desc.value;
    }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  }

  return _get.apply(this, arguments);
}

module.exports = _get, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{"./superPropBase.js":17}],12:[function(require,module,exports){
function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],13:[function(require,module,exports){
var setPrototypeOf = require("./setPrototypeOf.js");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  Object.defineProperty(subClass, "prototype", {
    writable: false
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{"./setPrototypeOf.js":16}],14:[function(require,module,exports){
function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {
    "default": obj
  };
}

module.exports = _interopRequireDefault, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],15:[function(require,module,exports){
var _typeof = require("./typeof.js")["default"];

var assertThisInitialized = require("./assertThisInitialized.js");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  } else if (call !== void 0) {
    throw new TypeError("Derived constructors may only return object or undefined");
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{"./assertThisInitialized.js":7,"./typeof.js":18}],16:[function(require,module,exports){
function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}],17:[function(require,module,exports){
var getPrototypeOf = require("./getPrototypeOf.js");

function _superPropBase(object, property) {
  while (!Object.prototype.hasOwnProperty.call(object, property)) {
    object = getPrototypeOf(object);
    if (object === null) break;
  }

  return object;
}

module.exports = _superPropBase, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{"./getPrototypeOf.js":12}],18:[function(require,module,exports){
function _typeof(obj) {
  "@babel/helpers - typeof";

  return (module.exports = _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports), _typeof(obj);
}

module.exports = _typeof, module.exports.__esModule = true, module.exports["default"] = module.exports;
},{}]},{},[4])
//# sourceMappingURL=unzer-ui-components.js.map
