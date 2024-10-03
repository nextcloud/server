# Changelog

## [6.4.0](https://github.com/firebase/php-jwt/compare/v6.3.2...v6.4.0) (2023-02-08)


### Features

* add support for W3C ES256K ([#462](https://github.com/firebase/php-jwt/issues/462)) ([213924f](https://github.com/firebase/php-jwt/commit/213924f51936291fbbca99158b11bd4ae56c2c95))
* improve caching by only decoding jwks when necessary ([#486](https://github.com/firebase/php-jwt/issues/486)) ([78d3ed1](https://github.com/firebase/php-jwt/commit/78d3ed1073553f7d0bbffa6c2010009a0d483d5c))

## [6.3.2](https://github.com/firebase/php-jwt/compare/v6.3.1...v6.3.2) (2022-11-01)


### Bug Fixes

* check kid before using as array index ([bad1b04](https://github.com/firebase/php-jwt/commit/bad1b040d0c736bbf86814c6b5ae614f517cf7bd))

## [6.3.1](https://github.com/firebase/php-jwt/compare/v6.3.0...v6.3.1) (2022-11-01)


### Bug Fixes

* casing of GET for PSR compat ([#451](https://github.com/firebase/php-jwt/issues/451)) ([60b52b7](https://github.com/firebase/php-jwt/commit/60b52b71978790eafcf3b95cfbd83db0439e8d22))
* string interpolation format for php 8.2 ([#446](https://github.com/firebase/php-jwt/issues/446)) ([2e07d8a](https://github.com/firebase/php-jwt/commit/2e07d8a1524d12b69b110ad649f17461d068b8f2))

## 6.3.0 / 2022-07-15

 - Added ES256 support to JWK parsing ([#399](https://github.com/firebase/php-jwt/pull/399))
 - Fixed potential caching error in `CachedKeySet` by caching jwks as strings ([#435](https://github.com/firebase/php-jwt/pull/435))

## 6.2.0 / 2022-05-14

 - Added `CachedKeySet` ([#397](https://github.com/firebase/php-jwt/pull/397))
 - Added `$defaultAlg` parameter to `JWT::parseKey` and `JWT::parseKeySet` ([#426](https://github.com/firebase/php-jwt/pull/426)).

## 6.1.0 / 2022-03-23

 - Drop support for PHP 5.3, 5.4, 5.5, 5.6, and 7.0
 - Add parameter typing and return types where possible

## 6.0.0 / 2022-01-24

 - **Backwards-Compatibility Breaking Changes**: See the [Release Notes](https://github.com/firebase/php-jwt/releases/tag/v6.0.0) for more information.
 - New Key object to prevent key/algorithm type confusion (#365)
 - Add JWK support (#273)
 - Add ES256 support (#256)
 - Add ES384 support (#324)
 - Add Ed25519 support (#343)

## 5.0.0 / 2017-06-26
- Support RS384 and RS512.
  See [#117](https://github.com/firebase/php-jwt/pull/117). Thanks [@joostfaassen](https://github.com/joostfaassen)!
- Add an example for RS256 openssl.
  See [#125](https://github.com/firebase/php-jwt/pull/125). Thanks [@akeeman](https://github.com/akeeman)!
- Detect invalid Base64 encoding in signature.
  See [#162](https://github.com/firebase/php-jwt/pull/162). Thanks [@psignoret](https://github.com/psignoret)!
- Update `JWT::verify` to handle OpenSSL errors.
  See [#159](https://github.com/firebase/php-jwt/pull/159). Thanks [@bshaffer](https://github.com/bshaffer)!
- Add `array` type hinting to `decode` method
  See [#101](https://github.com/firebase/php-jwt/pull/101). Thanks [@hywak](https://github.com/hywak)!
- Add all JSON error types.
  See [#110](https://github.com/firebase/php-jwt/pull/110). Thanks [@gbalduzzi](https://github.com/gbalduzzi)!
- Bugfix 'kid' not in given key list.
  See [#129](https://github.com/firebase/php-jwt/pull/129). Thanks [@stampycode](https://github.com/stampycode)!
- Miscellaneous cleanup, documentation and test fixes.
  See [#107](https://github.com/firebase/php-jwt/pull/107), [#115](https://github.com/firebase/php-jwt/pull/115),
  [#160](https://github.com/firebase/php-jwt/pull/160), [#161](https://github.com/firebase/php-jwt/pull/161), and
  [#165](https://github.com/firebase/php-jwt/pull/165). Thanks [@akeeman](https://github.com/akeeman),
  [@chinedufn](https://github.com/chinedufn), and [@bshaffer](https://github.com/bshaffer)!

## 4.0.0 / 2016-07-17
- Add support for late static binding. See [#88](https://github.com/firebase/php-jwt/pull/88) for details. Thanks to [@chappy84](https://github.com/chappy84)!
- Use static `$timestamp` instead of `time()` to improve unit testing. See [#93](https://github.com/firebase/php-jwt/pull/93) for details. Thanks to [@josephmcdermott](https://github.com/josephmcdermott)!
- Fixes to exceptions classes. See [#81](https://github.com/firebase/php-jwt/pull/81) for details. Thanks to [@Maks3w](https://github.com/Maks3w)!
- Fixes to PHPDoc. See [#76](https://github.com/firebase/php-jwt/pull/76) for details. Thanks to [@akeeman](https://github.com/akeeman)!

## 3.0.0 / 2015-07-22
- Minimum PHP version updated from `5.2.0` to `5.3.0`.
- Add `\Firebase\JWT` namespace. See
[#59](https://github.com/firebase/php-jwt/pull/59) for details. Thanks to
[@Dashron](https://github.com/Dashron)!
- Require a non-empty key to decode and verify a JWT. See
[#60](https://github.com/firebase/php-jwt/pull/60) for details. Thanks to
[@sjones608](https://github.com/sjones608)!
- Cleaner documentation blocks in the code. See
[#62](https://github.com/firebase/php-jwt/pull/62) for details. Thanks to
[@johanderuijter](https://github.com/johanderuijter)!

## 2.2.0 / 2015-06-22
- Add support for adding custom, optional JWT headers to `JWT::encode()`. See
[#53](https://github.com/firebase/php-jwt/pull/53/files) for details. Thanks to
[@mcocaro](https://github.com/mcocaro)!

## 2.1.0 / 2015-05-20
- Add support for adding a leeway to `JWT:decode()` that accounts for clock skew
between signing and verifying entities. Thanks to [@lcabral](https://github.com/lcabral)!
- Add support for passing an object implementing the `ArrayAccess` interface for
`$keys` argument in `JWT::decode()`. Thanks to [@aztech-dev](https://github.com/aztech-dev)!

## 2.0.0 / 2015-04-01
- **Note**: It is strongly recommended that you update to > v2.0.0 to address
  known security vulnerabilities in prior versions when both symmetric and
  asymmetric keys are used together.
- Update signature for `JWT::decode(...)` to require an array of supported
  algorithms to use when verifying token signatures.
