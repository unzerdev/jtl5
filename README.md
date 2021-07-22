# Unzer payment methods (JTL shop 5)

This plugin integrates the following Unzer payment methods in the JTL Shop:

- Alipay
- EPS
- Giropay
- iDEAL
- Credit Card / Debit Card
- PayPal
- Przelewy24
- SOFORT
- WeChat Pay
- Invoice
- Sepa Direct Debit
- Unzer Bank Transfer
- Unzer Instalment
- Unzer Invoice secured
- Unzer Direct Debit secured
- Unzer Prepayment

## Installation / Update
### System requirements
- JTL-Shop 5.0.0+ and its requirements
- PHP 7.1 - 7.4
- The following PHP extensions
  - ext-json
  - ext-curl
- In order to avoid rounding errors when transmitting floating point values to the API we recommend you to set the following value in your `php.ini`, which will select an enhanced algorithm for rounding such numbers.
~~~ini
// php.ini
; When floats & doubles are serialized store serialize_precision significant
; digits after the floating point. The default value ensures that when floats
; are decoded with unserialize, the data will remain the same.
serialize_precision = -1
~~~


### Further requirements
You must be registered with Unzer.

### Plugin installation
The installation of the plugin is done in the standard procedure for JTL-Shop 5 as described [here](https://jtl-devguide.readthedocs.io/projects/jtl-shop/de/latest/shop_plugins/allgemein.html#pluginverwaltung-im-backend-von-jtl-shop).

### Plugin update
For an update, upload the plugin to the `./plugins/` folder or via the plugin manager in the shop backend in the latest version as for an installation (and overwrite all existing plugin files if necessary) or follow the instructions of the Extension Store.
Then go to the plugin manager in the shop backend and press the update button.

## Configuration
After the actual plugin installation, it is necessary to activate the new payment methods and add them to the desired shipping methods.
Further information and configuration can be found in the [Instructions](https://redirect.solution360.de/?r=docsunzerjtl5).