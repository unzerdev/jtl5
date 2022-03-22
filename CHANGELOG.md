# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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

[1.0.2]: https://github.com/unzerdev/jtl5/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/unzerdev/jtl5/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/unzerdev/jtl5