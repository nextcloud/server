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
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\Formats\Signature\ASN1 as Asn1Signature;
use phpseclib3\Crypt\EC\Formats\Signature\IEEE as IeeeSignature;
use phpseclib3\Crypt\EC\PublicKey as EcPublicKey;
use Throwable;

/**
 * RFC 9421 §3.3 sign/verify primitives.
 *
 * Sign supports asymmetric algorithms reachable via ext-openssl: RSA-PKCS1-v1_5
 * (SHA-256/384/512) and ECDSA P-256 / P-384. JOSE aliases (RFC 7518 / RFC 8037)
 * accepted per RFC 9421 §3.3.7. RSA-PSS is rejected: OPENSSL_PKCS1_PSS_PADDING
 * needs PHP 8.5 and we still support 8.2-8.4.
 *
 * Verify additionally accepts Ed25519 through phpseclib, which selects the
 * best available engine and falls back to its PHP implementation.
 *
 * Sign delegates to {@see JWT::sign}. Verify takes a {@see Key} parsed by
 * firebase/php-jwt (which has already validated the JWK's kty/crv/alg
 * consistency) and only enforces the cross-source agreement between the JWK
 * `alg` and the Signature-Input `alg` parameter (RFC 9421 §3.2 step 6).
 */
final class Algorithm {
	private const int ED25519_PUBLIC_KEY_BYTES = 32;
	private const int ED25519_SIGNATURE_BYTES = 64;

	public const array NATIVE = [
		'rsa-v1_5-sha256',
		'rsa-v1_5-sha384',
		'rsa-v1_5-sha512',
		'ecdsa-p256-sha256',
		'ecdsa-p384-sha384',
		'ed25519',
	];

	/**
	 * $privateKey is a PEM private key. Returns raw signature bytes (R||S for
	 * ECDSA). Ed25519 is verify-only and is rejected here.
	 *
	 * @throws SignatureException
	 */
	public static function sign(string $signatureBase, string $privateKey, string $algorithm): string {
		$normalized = self::normalize($algorithm);

		if ($normalized === 'ed25519') {
			throw new SignatureException('Ed25519 signing is not supported; use ECDSA P-256 or RSA');
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
			if (strlen($signature) !== self::ED25519_SIGNATURE_BYTES) {
				return false;
			}
			$rawPublic = base64_decode((string)$material, true);
			if (
				$rawPublic === false
				|| strlen($rawPublic)
					!== self::ED25519_PUBLIC_KEY_BYTES
			) {
				return false;
			}
			try {
				$publicKey = EC::loadPublicKeyFormat(
					'libsodium',
					$rawPublic,
				);
				if (!$publicKey instanceof EcPublicKey) {
					return false;
				}
				return $publicKey->verify(
					$signatureBase,
					$signature,
				);
			} catch (Throwable $e) {
				throw new SignatureException(
					'Ed25519 verification failed: ' . $e->getMessage(),
					0,
					$e,
				);
			}
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
	 * Map a JOSE alg (RFC 7518/8037, including fully-specified RFC 9864
	 * names such as `Ed25519`) to the RFC 9421 native identifier.
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
	 * JOSE alg implied by a JWK's kty/crv, used to cross-check the JWK's
	 * mandatory `alg` member against its key material. Null if kty/crv
	 * don't pin one down (e.g. RSA, where the hash isn't determined).
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
		$components = IeeeSignature::load($raw);
		if ($components === false) {
			return null;
		}
		return Asn1Signature::save(
			$components['r'],
			$components['s'],
		);
	}
}
