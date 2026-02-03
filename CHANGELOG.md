# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-02-03

### Changed

- **Package is now completely free!** All features previously in Pro are now included at no cost
- Removed license key requirement
- Admin Dashboard (Filament) now included by default
- API endpoints now available without license
- All reporting and analytics features included

### Removed

- `LicenseManager` class
- `GenerateLicenseKey` artisan command
- Pro license configuration options

## [1.0.0] - 2026-01-10

### Added

- Initial release
- Commission Plans with multi-tier structures
- Commission Rules (percentage, flat, tiered, bonus threshold)
- Commission Earnings tracking
- Team split commissions
- Clawback support for refunds/chargebacks
- Payout management and processing
- Artisan commands for payout processing
- Events for commission lifecycle
- Comprehensive test suite
