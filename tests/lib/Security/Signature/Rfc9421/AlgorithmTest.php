<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Signature\Rfc9421;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use OC\Security\Signature\Rfc9421\Algorithm;
use OCP\Security\Signature\Exceptions\SignatureException;
use Test\TestCase;

class AlgorithmTest extends TestCase {
	public function testNormalizeNativeIsPassThrough(): void {
		$this->assertSame('ed25519', Algorithm::normalize('ed25519'));
		$this->assertSame('rsa-v1_5-sha256', Algorithm::normalize('rsa-v1_5-sha256'));
		$this->assertSame('ecdsa-p256-sha256', Algorithm::normalize('ecdsa-p256-sha256'));
	}

	public function testNormalizeJoseAliases(): void {
		$this->assertSame('ed25519', Algorithm::normalize('EdDSA'));
		$this->assertSame('ecdsa-p256-sha256', Algorithm::normalize('ES256'));
		$this->assertSame('ecdsa-p384-sha384', Algorithm::normalize('ES384'));
		$this->assertSame('rsa-v1_5-sha256', Algorithm::normalize('RS256'));
	}

	public function testNormalizeRejectsUnknown(): void {
		$this->expectException(SignatureException::class);
		Algorithm::normalize('totally-not-real');
	}

	public function testNormalizeRejectsRsaPss(): void {
		$this->expectException(SignatureException::class);
		Algorithm::normalize('rsa-pss-sha512');
	}

	public function testNormalizeRejectsJosePsAlias(): void {
		$this->expectException(SignatureException::class);
		Algorithm::normalize('PS512');
	}

	public function testDeriveJoseAlgFromJwk(): void {
		$this->assertSame('EdDSA', Algorithm::deriveJoseAlgFromJwk(['kty' => 'OKP', 'crv' => 'Ed25519']));
		$this->assertSame('ES256', Algorithm::deriveJoseAlgFromJwk(['kty' => 'EC', 'crv' => 'P-256']));
		$this->assertSame('ES384', Algorithm::deriveJoseAlgFromJwk(['kty' => 'EC', 'crv' => 'P-384']));
		// RSA: hash function isn't determined by key shape.
		$this->assertNull(Algorithm::deriveJoseAlgFromJwk(['kty' => 'RSA']));
		$this->assertNull(Algorithm::deriveJoseAlgFromJwk([]));
	}

	public function testEd25519RoundTrip(): void {
		[$priv, $key] = $this->ed25519KeyPair();
		$base = 'arbitrary signature base';
		$sig = Algorithm::sign($base, $priv, 'ed25519');
		$this->assertSame(64, strlen($sig));
		$this->assertTrue(Algorithm::verify($base, $sig, $key, 'ed25519'));
		// JOSE alias accepted.
		$this->assertTrue(Algorithm::verify($base, $sig, $key, 'EdDSA'));
		// alg-omitted path resolves through Key alg.
		$this->assertTrue(Algorithm::verify($base, $sig, $key, null));
		// tamper detection
		$this->assertFalse(Algorithm::verify($base . 'x', $sig, $key, 'ed25519'));
	}

	public function testRsaPkcs1RoundTrip(): void {
		[$priv, $key] = $this->rsaKeyPair();
		$sig = Algorithm::sign('payload', $priv, 'rsa-v1_5-sha256');
		$this->assertSame(256, strlen($sig));
		$this->assertTrue(Algorithm::verify('payload', $sig, $key, 'rsa-v1_5-sha256'));
		$this->assertTrue(Algorithm::verify('payload', $sig, $key, 'RS256'));
	}

	public function testEcdsaP256RoundTrip(): void {
		[$priv, $key] = $this->ecKeyPair('prime256v1', 'P-256', 'ES256');
		$sig = Algorithm::sign('payload', $priv, 'ecdsa-p256-sha256');
		$this->assertSame(64, strlen($sig));
		$this->assertTrue(Algorithm::verify('payload', $sig, $key, 'ecdsa-p256-sha256'));
		$this->assertTrue(Algorithm::verify('payload', $sig, $key, 'ES256'));
	}

	public function testEcdsaP384RoundTrip(): void {
		[$priv, $key] = $this->ecKeyPair('secp384r1', 'P-384', 'ES384');
		$sig = Algorithm::sign('payload', $priv, 'ecdsa-p384-sha384');
		$this->assertSame(96, strlen($sig));
		$this->assertTrue(Algorithm::verify('payload', $sig, $key, 'ecdsa-p384-sha384'));
	}

	public function testKeyTypeMismatchFailsClosed(): void {
		[, $rsaKey] = $this->rsaKeyPair();
		$this->expectException(SignatureException::class);
		Algorithm::verify('payload', random_bytes(64), $rsaKey, 'ed25519');
	}

	public function testAlgHintConflictsWithJwkAlgRejected(): void {
		// Ed25519 JWK, request claims ES256: RFC 9421 §3.2 step 6 disagreement.
		[, $key] = $this->ed25519KeyPair();
		$this->expectException(SignatureException::class);
		Algorithm::verify('payload', random_bytes(64), $key, 'ES256');
	}

	public function testParseKeyRejectsContradictoryAlg(): void {
		// kty=OKP/crv=Ed25519 with alg=ES256 is contradictory; firebase's
		// parseKey rejects it before we ever build a Key.
		$keypair = sodium_crypto_sign_keypair();
		$this->expectException(\Throwable::class);
		JWK::parseKey([
			'kty' => 'OKP',
			'crv' => 'Ed25519',
			'kid' => 'k',
			'alg' => 'ES256',
			'x' => self::b64url(sodium_crypto_sign_publickey($keypair)),
		], null);
	}

	public function testAlgHintAgreesViaJoseAlias(): void {
		[$priv, $key] = $this->ed25519KeyPair();
		$base = 'agreement check';
		$sig = Algorithm::sign($base, $priv, 'ed25519');
		$this->assertTrue(Algorithm::verify($base, $sig, $key, 'ed25519'));
		$this->assertTrue(Algorithm::verify($base, $sig, $key, 'EdDSA'));
	}

	public function testEcdsaRawToDerProducesValidSignature(): void {
		[$priv, $key] = $this->ecKeyPair('prime256v1', 'P-256', 'ES256');
		$rawSig = Algorithm::sign('msg', $priv, 'ecdsa-p256-sha256');
		$der = Algorithm::ecdsaRawToDer($rawSig, 32);
		$this->assertNotNull($der);
		$this->assertTrue(Algorithm::verify('msg', $rawSig, $key, 'ecdsa-p256-sha256'));
	}

	public function testEcdsaRawToDerWrongLength(): void {
		$this->assertNull(Algorithm::ecdsaRawToDer('short', 32));
	}

	/**
	 * @return array{0: string, 1: Key}
	 */
	private function ed25519KeyPair(): array {
		$keypair = sodium_crypto_sign_keypair();
		$publicKey = sodium_crypto_sign_publickey($keypair);
		$secretKey = sodium_crypto_sign_secretkey($keypair);
		$key = JWK::parseKey([
			'kty' => 'OKP',
			'crv' => 'Ed25519',
			'kid' => 'k',
			'alg' => 'EdDSA',
			'x' => self::b64url($publicKey),
		], 'EdDSA');
		return [$secretKey, $key];
	}

	/**
	 * @return array{0: string, 1: Key}
	 */
	private function rsaKeyPair(): array {
		$pkey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
		$priv = '';
		openssl_pkey_export($pkey, $priv);
		$details = openssl_pkey_get_details($pkey);
		$key = JWK::parseKey([
			'kty' => 'RSA',
			'kid' => 'k',
			'alg' => 'RS256',
			'n' => self::b64url($details['rsa']['n']),
			'e' => self::b64url($details['rsa']['e']),
		], 'RS256');
		return [$priv, $key];
	}

	/**
	 * @return array{0: string, 1: Key}
	 */
	private function ecKeyPair(string $opensslCurve, string $jwkCurve, string $joseAlg): array {
		$pkey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => $opensslCurve]);
		$priv = '';
		openssl_pkey_export($pkey, $priv);
		$details = openssl_pkey_get_details($pkey);
		$key = JWK::parseKey([
			'kty' => 'EC',
			'crv' => $jwkCurve,
			'kid' => 'k',
			'alg' => $joseAlg,
			'x' => self::b64url($details['ec']['x']),
			'y' => self::b64url($details['ec']['y']),
		], $joseAlg);
		return [$priv, $key];
	}

	private static function b64url(string $bin): string {
		return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
	}
}
