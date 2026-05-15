<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Signature\Model;

use Firebase\JWT\JWK;
use OC\Security\Signature\Model\Rfc9421IncomingSignedRequest;
use OC\Security\Signature\Model\Rfc9421OutgoingSignedRequest;
use OCP\IRequest;
use OCP\Security\Signature\Enum\DigestAlgorithm;
use OCP\Security\Signature\Enum\SignatureAlgorithm;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\InvalidSignatureException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\ISignatoryManager;
use OCP\Security\Signature\Model\Signatory;
use Test\TestCase;

class Rfc9421RoundTripTest extends TestCase {
	public function testEcdsaP256RoundTripVerifies(): void {
		[$signatory, $jwk] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$body = '{"hello":"world"}';
		$method = 'POST';
		$uri = 'https://receiver.example.org/ocm/shares';

		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', $method, $uri);
		$out->sign();

		$req = $this->mockRequestFromOutgoing($out, $method, '/ocm/shares', 'receiver.example.org');
		$in = new Rfc9421IncomingSignedRequest($body, $req);
		$in->setKey($jwk);

		$this->assertSame($out->getSignatureBaseString(), $in->getSignatureBaseString());
		$in->verify(); // throws on failure
		$this->addToAssertionCount(1);
	}

	public function testEd25519VerifyAcceptedWhenSodiumLoaded(): void {
		$this->skipUnlessSodium();
		[$signatory, $jwk] = $this->ed25519Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManagerWithSigningAlgorithm($signatory, 'ed25519');

		$body = '{"hello":"world"}';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		// Ed25519 sign() throws via Algorithm::sign; produce the signature directly.
		$rawSig = sodium_crypto_sign_detached($out->getSignatureBaseString(), $signatory->getPrivateKey());
		$out->setSignature(base64_encode($rawSig));
		$headers = $out->getHeaders();
		$paramsLine = '("@method" "@target-uri" "content-digest" "content-length" "date");created=' . time() . ';keyid="' . $signatory->getKeyId() . '"';
		$headers['Signature-Input'] = 'ocm=' . $paramsLine;
		$headers['Signature'] = 'ocm=:' . base64_encode($rawSig) . ':';

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$in = new Rfc9421IncomingSignedRequest($body, $req);
		$in->setKey($jwk);
		$in->verify();
		$this->addToAssertionCount(1);
	}

	public function testTamperedBodyRejected(): void {
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$body = 'original';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		$req = $this->mockRequestFromOutgoing($out, 'POST', '/ocm/shares', 'receiver.example.org');
		$this->expectException(IncomingRequestException::class);
		new Rfc9421IncomingSignedRequest('tampered', $req);
	}

	public function testTamperedSignatureRejected(): void {
		[$signatory, $jwk] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$body = 'msg';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		$headers = $out->getHeaders();
		// Replace the inner base64 of the signature with a different valid base64.
		$headers['Signature'] = preg_replace('/=:[^:]+:/', '=:' . base64_encode(random_bytes(64)) . ':', (string)$headers['Signature']);

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$in = new Rfc9421IncomingSignedRequest($body, $req);
		$in->setKey($jwk);

		$this->expectException(InvalidSignatureException::class);
		$in->verify();
	}

	public function testOutgoingUsesOcmLabel(): void {
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$out = new Rfc9421OutgoingSignedRequest('msg', $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		$headers = $out->getHeaders();
		$this->assertStringStartsWith('ocm=(', (string)$headers['Signature-Input']);
		$this->assertStringStartsWith('ocm=:', (string)$headers['Signature']);
	}

	public function testRequestWithoutOcmLabelRejected(): void {
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$out = new Rfc9421OutgoingSignedRequest('msg', $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		// Rename the OCM label to something else; verifier MUST reject.
		$headers = $out->getHeaders();
		$headers['Signature-Input'] = preg_replace('/^ocm=/', 'sig1=', (string)$headers['Signature-Input']);
		$headers['Signature'] = preg_replace('/^ocm=/', 'sig1=', (string)$headers['Signature']);

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$this->expectException(SignatureNotFoundException::class);
		new Rfc9421IncomingSignedRequest('msg', $req);
	}

	public function testDuplicateOcmLabelRejected(): void {
		// RFC 8941 §4.2 last-wins on duplicate dictionary keys, but OCM
		// mandates that duplicate `ocm` entries cause the request to be
		// rejected outright. The model layer enforces that.
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$out = new Rfc9421OutgoingSignedRequest('msg', $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		$headers = $out->getHeaders();
		$headers['Signature-Input'] = (string)$headers['Signature-Input'] . ', ' . (string)$headers['Signature-Input'];
		$headers['Signature'] = (string)$headers['Signature'] . ', ' . (string)$headers['Signature'];

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$this->expectException(IncomingRequestException::class);
		new Rfc9421IncomingSignedRequest('msg', $req);
	}

	public function testForeignSiblingLabelIgnored(): void {
		[$signatory, $jwk] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$out = new Rfc9421OutgoingSignedRequest('msg', $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		// Splice in a sibling proxy_sig1 entry; the verifier must ignore it
		// and still verify the ocm-labeled signature successfully.
		$headers = $out->getHeaders();
		$proxyParams = '("@method");created=1;keyid="proxy"';
		$proxySig = base64_encode(random_bytes(64));
		$headers['Signature-Input'] = (string)$headers['Signature-Input'] . ', proxy_sig1=' . $proxyParams;
		$headers['Signature'] = (string)$headers['Signature'] . ', proxy_sig1=:' . $proxySig . ':';

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$in = new Rfc9421IncomingSignedRequest('msg', $req);
		$in->setKey($jwk);
		$in->verify();
		$this->addToAssertionCount(1);
	}

	public function testTooOldSignatureRejected(): void {
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$body = 'msg';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		// Backdate `created` in Signature-Input by 10 minutes.
		$headers = $out->getHeaders();
		$pastCreated = time() - 600;
		$headers['Signature-Input'] = preg_replace('/created=\d+/', 'created=' . $pastCreated, (string)$headers['Signature-Input']);

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$this->expectException(IncomingRequestException::class);
		new Rfc9421IncomingSignedRequest($body, $req, ['ttl' => 300]);
	}

	public function testFutureCreatedRejected(): void {
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$body = 'msg';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		// Push `created` 10 minutes into the future, well past the
		// 60-second skew tolerance.
		$headers = $out->getHeaders();
		$futureCreated = time() + 600;
		$headers['Signature-Input'] = preg_replace('/created=\d+/', 'created=' . $futureCreated, (string)$headers['Signature-Input']);

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$this->expectException(IncomingRequestException::class);
		new Rfc9421IncomingSignedRequest($body, $req);
	}

	public function testMissingCreatedRejected(): void {
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManager($signatory);

		$body = 'msg';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();

		// Strip the `;created=...` parameter so the signature loses its
		// freshness anchor.
		$headers = $out->getHeaders();
		$headers['Signature-Input'] = preg_replace('/;created=\d+/', '', (string)$headers['Signature-Input']);

		$req = $this->mockRequest($headers, 'POST', '/ocm/shares', 'receiver.example.org');
		$this->expectException(IncomingRequestException::class);
		new Rfc9421IncomingSignedRequest($body, $req);
	}

	public function testSignatureNotCoveringRequiredComponentsRejected(): void {
		// A peer that signs only `@method` and `@target-uri`: the body and
		// freshness window aren't bound. Even with a valid signature we
		// must refuse it.
		[$signatory] = $this->ecdsaP256Material('https://sender.example.org/ocm#ecdsa-p256-sha256');
		$signatoryManager = $this->makeSignatoryManagerWithComponents(
			$signatory,
			['@method', '@target-uri'],
		);

		$body = 'msg';
		$out = new Rfc9421OutgoingSignedRequest($body, $signatoryManager, 'receiver.example.org', 'POST', 'https://receiver.example.org/ocm/shares');
		$out->sign();
		$req = $this->mockRequest($out->getHeaders(), 'POST', '/ocm/shares', 'receiver.example.org');

		$this->expectException(IncomingRequestException::class);
		new Rfc9421IncomingSignedRequest($body, $req);
	}

	private function skipUnlessSodium(): void {
		if (!extension_loaded('sodium')) {
			$this->markTestSkipped('ext-sodium is not loaded');
		}
	}

	private function makeSignatoryManagerWithComponents(Signatory $signatory, array $components): ISignatoryManager {
		return new class($signatory, $components) implements ISignatoryManager {
			public function __construct(
				private Signatory $sig,
				private array $components,
			) {
			}

			public function getProviderId(): string {
				return 'test';
			}

			public function getOptions(): array {
				return [
					'algorithm' => SignatureAlgorithm::RSA_SHA256,
					'digestAlgorithm' => DigestAlgorithm::SHA256,
					'rfc9421.coveredComponents' => $this->components,
				];
			}

			public function getLocalSignatory(): Signatory {
				return $this->sig;
			}

			public function getRemoteSignatory(string $remote): ?Signatory {
				return null;
			}
		};
	}

	private function makeSignatoryManagerWithSigningAlgorithm(Signatory $signatory, string $signingAlgorithm): ISignatoryManager {
		return new class($signatory, $signingAlgorithm) implements ISignatoryManager {
			public function __construct(
				private Signatory $sig,
				private string $signingAlgorithm,
			) {
			}

			public function getProviderId(): string {
				return 'test';
			}

			public function getOptions(): array {
				return [
					'algorithm' => SignatureAlgorithm::RSA_SHA256,
					'digestAlgorithm' => DigestAlgorithm::SHA256,
					'rfc9421.signingAlgorithm' => $this->signingAlgorithm,
				];
			}

			public function getLocalSignatory(): Signatory {
				return $this->sig;
			}

			public function getRemoteSignatory(string $remote): ?Signatory {
				return null;
			}
		};
	}

	/**
	 * @return array{0: Signatory, 1: \Firebase\JWT\Key}
	 */
	private function ecdsaP256Material(string $kid): array {
		$pkey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
		$privatePem = '';
		openssl_pkey_export($pkey, $privatePem);
		$details = openssl_pkey_get_details($pkey);
		$publicPem = $details['key'];

		$signatory = new Signatory(true);
		$signatory->setKeyId($kid);
		$signatory->setPublicKey($publicPem);
		$signatory->setPrivateKey($privatePem);

		$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
		$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
		$key = JWK::parseKey([
			'kty' => 'EC',
			'crv' => 'P-256',
			'kid' => $kid,
			'alg' => 'ES256',
			'x' => self::b64url($x),
			'y' => self::b64url($y),
		], 'ES256');
		return [$signatory, $key];
	}

	/**
	 * @return array{0: Signatory, 1: \Firebase\JWT\Key}
	 */
	private function ed25519Material(string $kid): array {
		$keypair = sodium_crypto_sign_keypair();
		$publicKey = sodium_crypto_sign_publickey($keypair);
		$secretKey = sodium_crypto_sign_secretkey($keypair);
		$signatory = new Signatory(true);
		$signatory->setKeyId($kid);
		$signatory->setPublicKey($publicKey);
		$signatory->setPrivateKey($secretKey);
		$key = JWK::parseKey([
			'kty' => 'OKP',
			'crv' => 'Ed25519',
			'kid' => $kid,
			'alg' => 'EdDSA',
			'x' => self::b64url($publicKey),
		], 'EdDSA');
		return [$signatory, $key];
	}

	private static function b64url(string $bin): string {
		return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
	}

	private function makeSignatoryManager(Signatory $signatory): ISignatoryManager {
		return new class($signatory) implements ISignatoryManager {
			public function __construct(
				private Signatory $sig,
			) {
			}

			public function getProviderId(): string {
				return 'test';
			}

			public function getOptions(): array {
				return [
					'algorithm' => SignatureAlgorithm::RSA_SHA256,
					'digestAlgorithm' => DigestAlgorithm::SHA256,
				];
			}

			public function getLocalSignatory(): Signatory {
				return $this->sig;
			}

			public function getRemoteSignatory(string $remote): ?Signatory {
				return null;
			}
		};
	}

	private function mockRequestFromOutgoing(Rfc9421OutgoingSignedRequest $out, string $method, string $path, string $host): IRequest {
		return $this->mockRequest($out->getHeaders(), $method, $path, $host);
	}

	private function mockRequest(array $headers, string $method, string $path, string $host): IRequest {
		$lowered = [];
		foreach ($headers as $name => $value) {
			$lowered[strtolower($name)] = (string)$value;
		}
		$mock = $this->createMock(IRequest::class);
		$mock->method('getHeader')->willReturnCallback(static fn (string $h) => $lowered[strtolower($h)] ?? '');
		$mock->method('getMethod')->willReturn($method);
		$mock->method('getRequestUri')->willReturn($path);
		$mock->method('getServerProtocol')->willReturn('https');
		$mock->method('getServerHost')->willReturn($host);
		return $mock;
	}
}
