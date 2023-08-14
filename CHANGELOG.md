# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.3.0] (August 2023)
### Added
- Added LICENSE and NOTICE

### Changed
- Changed Payment Method name from Unzer Invoice to Unzer Invoice (Paylater)

### Fixed
- Clear Plugin Session on the order status page in case that a user aborted its payment process

### Removed
- Removed deprecated Payment Method **Unzer Instalment**/**Unzer Ratenkauf**
- Removed deprecated Payment Method **Unzer Bank Transfer**/**Unzer Direktüberweisung**

## [1.2.1] (February 2023)
### Added
- Added compatability for **JTL 5.2 and PHP 8.1**
- Added debug logs for the shipping API call
- Added Reference Text for cancellations

## Changed
- Show Invoice ID in Order Detail only if either local invoice id or invoice id from the API response is available
- Hide Unzer Insight Portal Button as the correct link to the order cannot be determined reliably

### Fixed
- Locale Mapping for Unzer UI Component
- Fixed unzer applepay debugging when in sandbox mode
- Fixed issue with submit button staying disabled on invalid input on the additional checkout step
- Fixed rounding issue in total gross amount API field

## [1.2.0] (November 2022)
### Added
- Added Bancontact as payment method
- Added Unzer Rechnung (Buy Now, Pay Later) as payment method
- Added option to disable automatic setting of incoming payments

### Changed
- Unzer SDK version updated to 1.2.0.0

## [1.1.0] (July 2022)
### Added
- Added company info to customer object
- Added verification and notification if frontend URLs have changed due to JTL/plugin updates and how to correct them
- Added VAT amount to shopping cart object
- Added ApplePay payment method

### Changed
- Unzer SDK version updated to 1.1.4.2
- Remove default value for payment method selection, as the NOVA theme already has a back button in the additional payment step.

### Fixed
- Fixed an issue with instalments sending incorrect/temporary order numbers to Unzer
- Fixed an unhandled error when retrieving refunds in the backend
- Fixed problem with umlauts in intermediate payment step encoded with HTML entities instead of UTF-8
- Fixed problem with cancelling Invoice (Secured) orders before they are completed
- Fixed problem with wrong order number in order confirmation emails

## [1.0.2] (March 2022)
### Added
- add minimum customer info (name and email) to all payments

### Changed
- use short id as transaction id in payment history (WaWi)

### Fixed
- set default db engine and charset when creating database tables to avoid issues due to weird defaults
- add error handling to avoid issues in the frontend when API is not callable *(ie missing keys)*
- fix issue with -0.0 beeing interpreted as negative in the unzer api
- potential fix for mismatch of order ids between the unzer insight portal and the shop
- error in the placeholder of the public key setting in the backend


## [1.0.1] (November 2021)
### Added
- JTL Shop 5.1 Compatability
- JTL WaWi 1.6 Compatability
- PHP 8.0 Compatability

### Fixed
- typo in SQL Query
- diplay error for cancellations with the same ID but different charges
- problem in validation resulting in not being able to use vouchers/coupons in the last checkout step

## [1.0.0] (July 2021)
### Added
- Initial Release
