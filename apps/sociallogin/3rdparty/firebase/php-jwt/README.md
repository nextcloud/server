![Build Status](https://github.com/firebase/php-jwt/actions/workflows/tests.yml/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/firebase/php-jwt/v/stable)](https://packagist.org/packages/firebase/php-jwt)
[![Total Downloads](https://poser.pugx.org/firebase/php-jwt/downloads)](https://packagist.org/packages/firebase/php-jwt)
[![License](https://poser.pugx.org/firebase/php-jwt/license)](https://packagist.org/packages/firebase/php-jwt)

PHP-JWT
=======
A simple library to encode and decode JSON Web Tokens (JWT) in PHP, conforming to [RFC 7519](https://tools.ietf.org/html/rfc7519).

Installation
------------

Use composer to manage your dependencies and download PHP-JWT:

```bash
composer require firebase/php-jwt
```

Optionally, install the `paragonie/sodium_compat` package from composer if your
php is < 7.2 or does not have libsodium installed:

```bash
composer require paragonie/sodium_compat
```

Example
-------
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = 'example_key';
$payload = [
    'iss' => 'http://example.org',
    'aud' => 'http://example.com',
    'iat' => 1356999524,
    'nbf' => 1357000000
];

/**
 * IMPORTANT:
 * You must specify supported algorithms for your application. See
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
 * for a list of spec-compliant algorithms.
 */
$jwt = JWT::encode($payload, $key, 'HS256');
$decoded = JWT::decode($jwt, new Key($key, 'HS256'));

print_r($decoded);

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;

/**
 * You can add a leeway to account for when there is a clock skew times between
 * the signing and verifying servers. It is recommended that this leeway should
 * not be bigger than a few minutes.
 *
 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
 */
JWT::$leeway = 60; // $leeway in seconds
$decoded = JWT::decode($jwt, new Key($key, 'HS256'));
```
Example with RS256 (openssl)
----------------------------
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC8kGa1pSjbSYZVebtTRBLxBz5H4i2p/llLCrEeQhta5kaQu/Rn
vuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t0tyazyZ8JXw+KgXTxldMPEL9
5+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4ehde/zUxo6UvS7UrBQIDAQAB
AoGAb/MXV46XxCFRxNuB8LyAtmLDgi/xRnTAlMHjSACddwkyKem8//8eZtw9fzxz
bWZ/1/doQOuHBGYZU8aDzzj59FZ78dyzNFoF91hbvZKkg+6wGyd/LrGVEB+Xre0J
Nil0GReM2AHDNZUYRv+HYJPIOrB0CRczLQsgFJ8K6aAD6F0CQQDzbpjYdx10qgK1
cP59UHiHjPZYC0loEsk7s+hUmT3QHerAQJMZWC11Qrn2N+ybwwNblDKv+s5qgMQ5
5tNoQ9IfAkEAxkyffU6ythpg/H0Ixe1I2rd0GbF05biIzO/i77Det3n4YsJVlDck
ZkcvY3SK2iRIL4c9yY6hlIhs+K9wXTtGWwJBAO9Dskl48mO7woPR9uD22jDpNSwe
k90OMepTjzSvlhjbfuPN1IdhqvSJTDychRwn1kIJ7LQZgQ8fVz9OCFZ/6qMCQGOb
qaGwHmUK6xzpUbbacnYrIM6nLSkXgOAwv7XXCojvY614ILTK3iXiLBOxPu5Eu13k
eUz9sHyD6vkgZzjtxXECQAkp4Xerf5TGfQXGXhxIX52yH+N2LtujCdkQZjXAsGdm
B2zNzvrlgRmgBrklMTrMYgm1NPcW+bRLGcwgW2PTvNM=
-----END RSA PRIVATE KEY-----
EOD;

$publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kGa1pSjbSYZVebtTRBLxBz5H
4i2p/llLCrEeQhta5kaQu/RnvuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t
0tyazyZ8JXw+KgXTxldMPEL95+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4
ehde/zUxo6UvS7UrBQIDAQAB
-----END PUBLIC KEY-----
EOD;

$payload = [
    'iss' => 'example.org',
    'aud' => 'example.com',
    'iat' => 1356999524,
    'nbf' => 1357000000
];

$jwt = JWT::encode($payload, $privateKey, 'RS256');
echo "Encode:\n" . print_r($jwt, true) . "\n";

$decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;
echo "Decode:\n" . print_r($decoded_array, true) . "\n";
```

Example with a passphrase
-------------------------

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Your passphrase
$passphrase = '[YOUR_PASSPHRASE]';

// Your private key file with passphrase
// Can be generated with "ssh-keygen -t rsa -m pem"
$privateKeyFile = '/path/to/key-with-passphrase.pem';

// Create a private key of type "resource"
$privateKey = openssl_pkey_get_private(
    file_get_contents($privateKeyFile),
    $passphrase
);

$payload = [
    'iss' => 'example.org',
    'aud' => 'example.com',
    'iat' => 1356999524,
    'nbf' => 1357000000
];

$jwt = JWT::encode($payload, $privateKey, 'RS256');
echo "Encode:\n" . print_r($jwt, true) . "\n";

// Get public key from the private key, or pull from from a file.
$publicKey = openssl_pkey_get_details($privateKey)['key'];

$decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
echo "Decode:\n" . print_r((array) $decoded, true) . "\n";
```

Example with EdDSA (libsodium and Ed25519 signature)
----------------------------
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Public and private keys are expected to be Base64 encoded. The last
// non-empty line is used so that keys can be generated with
// sodium_crypto_sign_keypair(). The secret keys generated by other tools may
// need to be adjusted to match the input expected by libsodium.

$keyPair = sodium_crypto_sign_keypair();

$privateKey = base64_encode(sodium_crypto_sign_secretkey($keyPair));

$publicKey = base64_encode(sodium_crypto_sign_publickey($keyPair));

$payload = [
    'iss' => 'example.org',
    'aud' => 'example.com',
    'iat' => 1356999524,
    'nbf' => 1357000000
];

$jwt = JWT::encode($payload, $privateKey, 'EdDSA');
echo "Encode:\n" . print_r($jwt, true) . "\n";

$decoded = JWT::decode($jwt, new Key($publicKey, 'EdDSA'));
echo "Decode:\n" . print_r((array) $decoded, true) . "\n";
````

Using JWKs
----------

```php
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

// Set of keys. The "keys" key is required. For example, the JSON response to
// this endpoint: https://www.gstatic.com/iap/verify/public_key-jwk
$jwks = ['keys' => []];

// JWK::parseKeySet($jwks) returns an associative array of **kid** to Firebase\JWT\Key
// objects. Pass this as the second parameter to JWT::decode.
JWT::decode($payload, JWK::parseKeySet($jwks));
```

Using Cached Key Sets
---------------------

The `CachedKeySet` class can be used to fetch and cache JWKS (JSON Web Key Sets) from a public URI.
This has the following advantages:

1. The results are cached for performance.
2. If an unrecognized key is requested, the cache is refreshed, to accomodate for key rotation.
3. If rate limiting is enabled, the JWKS URI will not make more than 10 requests a second.

```php
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;

// The URI for the JWKS you wish to cache the results from
$jwksUri = 'https://www.gstatic.com/iap/verify/public_key-jwk';

// Create an HTTP client (can be any PSR-7 compatible HTTP client)
$httpClient = new GuzzleHttp\Client();

// Create an HTTP request factory (can be any PSR-17 compatible HTTP request factory)
$httpFactory = new GuzzleHttp\Psr\HttpFactory();

// Create a cache item pool (can be any PSR-6 compatible cache item pool)
$cacheItemPool = Phpfastcache\CacheManager::getInstance('files');

$keySet = new CachedKeySet(
    $jwksUri,
    $httpClient,
    $httpFactory,
    $cacheItemPool,
    null, // $expiresAfter int seconds to set the JWKS to expire
    true  // $rateLimit    true to enable rate limit of 10 RPS on lookup of invalid keys
);

$jwt = 'eyJhbGci...'; // Some JWT signed by a key from the $jwkUri above
$decoded = JWT::decode($jwt, $keySet);
```

Miscellaneous
-------------

#### Exception Handling

When a call to `JWT::decode` is invalid, it will throw one of the following exceptions:

```php
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

try {
    $decoded = JWT::decode($payload, $keys);
} catch (InvalidArgumentException $e) {
    // provided key/key-array is empty or malformed.
} catch (DomainException $e) {
    // provided algorithm is unsupported OR
    // provided key is invalid OR
    // unknown error thrown in openSSL or libsodium OR
    // libsodium is required but not available.
} catch (SignatureInvalidException $e) {
    // provided JWT signature verification failed.
} catch (BeforeValidException $e) {
    // provided JWT is trying to be used before "nbf" claim OR
    // provided JWT is trying to be used before "iat" claim.
} catch (ExpiredException $e) {
    // provided JWT is trying to be used after "exp" claim.
} catch (UnexpectedValueException $e) {
    // provided JWT is malformed OR
    // provided JWT is missing an algorithm / using an unsupported algorithm OR
    // provided JWT algorithm does not match provided key OR
    // provided key ID in key/key-array is empty or invalid.
}
```

All exceptions in the `Firebase\JWT` namespace extend `UnexpectedValueException`, and can be simplified
like this:

```php
try {
    $decoded = JWT::decode($payload, $keys);
} catch (LogicException $e) {
    // errors having to do with environmental setup or malformed JWT Keys
} catch (UnexpectedValueException $e) {
    // errors having to do with JWT signature and claims
}
```

#### Casting to array

The return value of `JWT::decode` is the generic PHP object `stdClass`. If you'd like to handle with arrays
instead, you can do the following:

```php
// return type is stdClass
$decoded = JWT::decode($payload, $keys);

// cast to array
$decoded = json_decode(json_encode($decoded), true);
```

Tests
-----
Run the tests using phpunit:

```bash
$ pear install PHPUnit
$ phpunit --configuration phpunit.xml.dist
PHPUnit 3.7.10 by Sebastian Bergmann.
.....
Time: 0 seconds, Memory: 2.50Mb
OK (5 tests, 5 assertions)
```

New Lines in private keys
-----

If your private key contains `\n` characters, be sure to wrap it in double quotes `""`
and not single quotes `''` in order to properly interpret the escaped characters.

License
-------
[3-Clause BSD](http://opensource.org/licenses/BSD-3-Clause).
