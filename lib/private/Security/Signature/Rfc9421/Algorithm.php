<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Rfc9421;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use OCP\Security\Signature\Exceptions\SignatureException;
use Throwable;

/**
 * RFC 9421 §3.3 sign/verify primitives.
 *
 * Asymmetric algorithms only: RSA-PKCS1-v1_5 (SHA-256/384/512), ECDSA P-256
 * SHA-256, ECDSA P-384 SHA-384, Ed25519. JOSE aliases (RFC 7518 / RFC 8037)
 * accepted per RFC 9421 §3.3.7. RSA-PSS is rejected: OPENSSL_PKCS1_PSS_PADDING
 * needs PHP 8.5 and we still support 8.2-8.4.
 *
 * Sign delegates to {@see JWT::sign}. Verify takes a {@see Key} parsed by
 * firebase/php-jwt (which has already validated the JWK's kty/crv/alg
 * consistency) and only enforces the cross-source agreement between the JWK
 * `alg` and the Signature-Input `alg` parameter (RFC 9421 §3.2 step 6).
 */
final class Algorithm {
	public const NATIVE = [
		'rsa-v1_5-sha256',
		'rsa-v1_5-sha384',
		'rsa-v1_5-sha512',
		'ecdsa-p256-sha256',
		'ecdsa-p384-sha384',
		'ed25519',
	];

	/**
	 * For Ed25519 $privateKey is the raw 64-byte sodium secret key; otherwise
	 * a PEM private key. Returns raw signature bytes (R||S for ECDSA).
	 *
	 * Ed25519 calls sodium directly: JWT::sign runs the key through
	 * `validateEdDSAKey` which base64url-decodes it first, which mangles raw
	 * sodium bytes.
	 *
	 * @throws SignatureException
	 */
	public static function sign(string $signatureBase, string $privateKey, string $algorithm): string {
		$normalized = self::normalize($algorithm);

		if ($normalized === 'ed25519') {
			if (strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
				throw new SignatureException('Ed25519 secret key must be ' . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES . ' bytes');
			}
			return sodium_crypto_sign_detached($signatureBase, $privateKey);
		}

		try {
			return JWT::sign($signatureBase, $privateKey, self::nativeToJose($normalized));
		} catch (Throwable $e) {
			throw new SignatureException('signing failed for ' . $normalized . ': ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * @param string $signature raw signature bytes (already base64-decoded)
	 * @param string|null $algorithm algorithm hint from Signature-Input `alg=`
	 * @throws SignatureException
	 */
	public static function verify(string $signatureBase, string $signature, Key $key, ?string $algorithm): bool {
		$resolved = self::normalize($key->getAlgorithm());

		if ($algorithm !== null && $algorithm !== '') {
			$hintNative = self::normalize($algorithm);
			if ($hintNative !== $resolved) {
				throw new SignatureException(
					'algorithm sources disagree: Signature-Input alg says ' . $hintNative . ', JWK alg says ' . $resolved
				);
			}
		}

		$material = $key->getKeyMaterial();

		if ($resolved === 'ed25519') {
			if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
				return false;
			}
			// parseKey hands OKP material as plain base64 of the 32 raw bytes.
			$rawPublic = base64_decode((string)$material, true);
			if ($rawPublic === false || strlen($rawPublic) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
				return false;
			}
			return sodium_crypto_sign_verify_detached($signature, $signatureBase, $rawPublic);
		}

		[$opensslAlgo, $encoding] = self::opensslParametersForAlgorithm($resolved);

		if ($encoding === 'ecdsa') {
			$signature = self::ecdsaRawToDer($signature, self::ecdsaCoordinateSize($resolved));
			if ($signature === null) {
				return false;
			}
		}

		return openssl_verify($signatureBase, $signature, $material, $opensslAlgo) === 1;
	}

	/**
	 * Map a JOSE alg (RFC 7518/8037) to the RFC 9421 native identifier.
	 * Pass-through if already native.
	 *
	 * @throws SignatureException
	 */
	public static function normalize(string $algorithm): string {
		$lower = strtolower($algorithm);
		if (in_array($lower, self::NATIVE, true)) {
			return $lower;
		}
		return match ($algorithm) {
			'EdDSA' => 'ed25519',
			'ES256' => 'ecdsa-p256-sha256',
			'ES384' => 'ecdsa-p384-sha384',
			'RS256' => 'rsa-v1_5-sha256',
			'RS384' => 'rsa-v1_5-sha384',
			'RS512' => 'rsa-v1_5-sha512',
			default => throw new SignatureException('unsupported signature algorithm: ' . $algorithm),
		};
	}

	/**
	 * Default JOSE alg for {@see \Firebase\JWT\JWK::parseKey} when the JWK has
	 * no `alg` (RFC 7517 leaves it optional). Null if kty/crv don't pin one
	 * down (e.g. RSA, where the hash isn't determined).
	 *
	 * @param array<string, mixed> $jwk
	 */
	public static function deriveJoseAlgFromJwk(array $jwk): ?string {
		return match ($jwk['kty'] ?? '') {
			'OKP' => match ($jwk['crv'] ?? '') {
				'Ed25519' => 'EdDSA',
				default => null,
			},
			'EC' => match ($jwk['crv'] ?? '') {
				'P-256' => 'ES256',
				'P-384' => 'ES384',
				default => null,
			},
			default => null,
		};
	}

	private static function nativeToJose(string $native): string {
		return match ($native) {
			'ed25519' => 'EdDSA',
			'ecdsa-p256-sha256' => 'ES256',
			'ecdsa-p384-sha384' => 'ES384',
			'rsa-v1_5-sha256' => 'RS256',
			'rsa-v1_5-sha384' => 'RS384',
			'rsa-v1_5-sha512' => 'RS512',
			default => throw new SignatureException('unsupported signature algorithm: ' . $native),
		};
	}

	/**
	 * @return array{0: int, 1: string} [openssl digest, wire encoding]
	 */
	private static function opensslParametersForAlgorithm(string $native): array {
		return match ($native) {
			'rsa-v1_5-sha256' => [OPENSSL_ALGO_SHA256, 'raw'],
			'rsa-v1_5-sha384' => [OPENSSL_ALGO_SHA384, 'raw'],
			'rsa-v1_5-sha512' => [OPENSSL_ALGO_SHA512, 'raw'],
			'ecdsa-p256-sha256' => [OPENSSL_ALGO_SHA256, 'ecdsa'],
			'ecdsa-p384-sha384' => [OPENSSL_ALGO_SHA384, 'ecdsa'],
			default => throw new SignatureException('unsupported signature algorithm: ' . $native),
		};
	}

	private static function ecdsaCoordinateSize(string $native): int {
		return match ($native) {
			'ecdsa-p256-sha256' => 32,
			'ecdsa-p384-sha384' => 48,
			default => throw new InvalidArgumentException('not an ECDSA algorithm: ' . $native),
		};
	}

	/**
	 * Raw R||S (RFC 9421 §3.3.4 wire form) to DER for openssl_verify.
	 * firebase/php-jwt has the inverse but keeps it private.
	 */
	public static function ecdsaRawToDer(string $raw, int $coordinateSize): ?string {
		if (strlen($raw) !== $coordinateSize * 2) {
			return null;
		}
		$r = ltrim(substr($raw, 0, $coordinateSize), "\x00");
		$s = ltrim(substr($raw, $coordinateSize), "\x00");
		// DER INTEGER must be positive; pad if high bit is set.
		if ($r === '' || (ord($r[0]) & 0x80) !== 0) {
			$r = "\x00" . $r;
		}
		if ($s === '' || (ord($s[0]) & 0x80) !== 0) {
			$s = "\x00" . $s;
		}
		$rEncoded = "\x02" . self::derLength(strlen($r)) . $r;
		$sEncoded = "\x02" . self::derLength(strlen($s)) . $s;
		$body = $rEncoded . $sEncoded;
		return "\x30" . self::derLength(strlen($body)) . $body;
	}

	private static function derLength(int $length): string {
		if ($length < 0x80) {
			return chr($length);
		}
		$bytes = '';
		while ($length > 0) {
			$bytes = chr($length & 0xff) . $bytes;
			$length >>= 8;
		}
		return chr(0x80 | strlen($bytes)) . $bytes;
	}
}
