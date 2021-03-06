# Changelog

All notable changes to this package will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.5.0] - 2020-09-09
### Changed
- Added support for Laravel 8

## [1.4.0] - 2020-03-03
### Changed
- Added support for Laravel 7

## [1.3.0] - 2020-01-17
### Added
- Added the `JWTGuard::setTokenSigner()` method to allow for the customisation of token signing

## [1.2.0] - 2020-01-12
### Added
- Added the `jwt:generate` command to generate keys for signing JWTs ([#7])

## [1.1.2] - 2020-01-03
### Fixed
- Fix TTL parsing when generating token ([#5])
### Changed
- Updated README to mention middleware required for JWT cookie

## [1.1.1] - 2019-12-14
### Fixed
- Added missing property for cookie jar on JWT guard
- Added null check for user provider when instantiating guard
- Updated docblocks for exceptions
- Explicitly call `toString()` method on UUIDs

## [1.1.0] - 2019-12-14
### Added
- Added HTTP only cookie support
- Added custom token generator support
- Added custom token validator support

### Fixed
- Add validation for token expiry which was previously missing

## [1.0.0] - 2019-11-19
- Initial release

[Unreleased]: https://github.com/sprocketbox/laravel-jwt/compare/v1.5.0...develop
[1.5.0]: https://github.com/sprocketbox/laravel-jwt/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/sprocketbox/laravel-jwt/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/sprocketbox/laravel-jwt/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/sprocketbox/laravel-jwt/compare/v1.1.2...v1.2.0
[1.1.2]: https://github.com/sprocketbox/laravel-jwt/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/sprocketbox/laravel-jwt/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/sprocketbox/laravel-jwt/compare/v1.0...v1.1.0
[1.0.0]: https://github.com/sprocketbox/laravel-jwt/releases/tag/v1.0
[#5]: https://github.com/sprocketbox/laravel-jwt/pull/5
[#7]: https://github.com/sprocketbox/laravel-jwt/issues/7