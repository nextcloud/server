<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use OC\OCM\OCMDiscoveryService;
use OC\OCM\OCMSignatoryManager;
use OC\Security\Signature\Model\Rfc9421OutgoingSignedResponse;
use OCP\AppFramework\Http\Response;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMRequestException;
use OCP\OCM\IOCMProvider;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatoryManager;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\Signatory;
use OCP\Server;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Test\TestCase;

final class OCMDiscoveryServiceSignedExchangeTest extends TestCase {
	#[\PHPUnit\Framework\Attributes\TestWith([false, 'nonce-1'])]
	#[\PHPUnit\Framework\Attributes\TestWith([true, ''])]
	public function testRejectsInvalidSignedExchangeOptions(
		bool $signed,
		string $nonce,
	): void {
		$this->expectException(OCMRequestException::class);
		$this->createService(
			$this->createMock(OCMSignatoryManager::class),
		)->requestRemoteOcmEndpoint(
			null,
			'https://receiver.example.org',
			'/mls-key-packages',
			signed: $signed,
			signatureNonce: $nonce,
		);
	}

	public function testSignedExchangeProtectsNonceAndVerifiesResponse(): void {
		[$signatory, $key] = $this->ecdsaP256Material(
			'https://receiver.example.org/ocm#ecdsa-p256-sha256-1',
		);
		$signatoryManager = $this->createMock(
			OCMSignatoryManager::class,
		);
		$signatoryManager->method('getOptions')->willReturn([]);
		$signatoryManager->method('getLocalJwksSignatory')
			->willReturn($signatory);
		$signatoryManager->method('getRemoteSignatory')
			->willReturn(null);
		$signatoryManager->method('getRemoteKey')->willReturn($key);

		$service = $this->createService($signatoryManager);
		$provider = $this->createMock(IOCMProvider::class);
		$provider->method('isEnabled')->willReturn(true);
		$provider->method('hasCapability')->willReturn(true);
		$provider->method('getEndPoint')
			->willReturn('https://receiver.example.org/ocm');
		$reflection = new ReflectionClass($service);
		$reflection->getProperty('remoteProviders')->setValue(
			$service,
			['https://receiver.example.org' => $provider],
		);

		$responseManager = $this->createMock(ISignatoryManager::class);
		$responseManager->method('getOptions')->willReturn([]);
		$responseManager->method('getLocalSignatory')
			->willReturn($signatory);
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('get')
			->willReturnCallback(
				function (string $uri, array $options) use (
					$responseManager,
				): IResponse {
					$signatureInput
						= $options['headers']['Signature-Input'] ?? '';
					self::assertIsString($signatureInput);
					self::assertStringContainsString(
						';nonce="nonce-1"',
						$signatureInput,
					);

					$request = $this->createMock(IRequest::class);
					$requestHeaders = [
						'signature-input' => $signatureInput,
						'signature' => (string)($options['headers']['Signature'] ?? ''),
					];
					$request->method('getHeader')
						->willReturnCallback(
							static fn (string $name): string => $requestHeaders[strtolower($name)] ?? '',
						);
					$request->method('getMethod')->willReturn('GET');
					$request->method('getServerProtocol')
						->willReturn('https');
					$request->method('getServerHost')
						->willReturn('receiver.example.org');
					$request->method('getRequestUri')->willReturn(
						(string)parse_url($uri, PHP_URL_PATH)
							. '?'
							. (string)parse_url($uri, PHP_URL_QUERY),
					);
					$incoming = $this->createMock(
						IIncomingSignedRequest::class,
					);
					$incoming->method('getRequest')
						->willReturn($request);
					return $this->asClientResponse(
						new Rfc9421OutgoingSignedResponse(
							'{"keyPackages":[]}',
							$incoming,
							$responseManager,
						),
					);
				},
			);

		$response = $service->requestRemoteOcmEndpoint(
			'mls-key-packages',
			'https://receiver.example.org',
			'/mls-key-packages?userId=alice',
			[],
			'get',
			$client,
			['http_errors' => false],
			true,
			'nonce-1',
		);

		$this->assertSame(
			'{"keyPackages":[]}',
			$response->getBody(),
		);
	}

	private function createService(
		OCMSignatoryManager $signatoryManager,
	): OCMDiscoveryService {
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn(
			$this->createMock(ICache::class),
		);
		return new OCMDiscoveryService(
			$cacheFactory,
			$this->createMock(IClientService::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(IConfig::class),
			$this->createMock(IAppConfig::class),
			$this->createMock(IURLGenerator::class),
			Server::get(ISignatureManager::class),
			$signatoryManager,
			$this->createMock(ICloudIdManager::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	private function asClientResponse(Response $response): IResponse {
		$headers = $response->getHeaders();
		$clientResponse = $this->createMock(IResponse::class);
		$clientResponse->expects($this->once())->method('getBody')
			->willReturn($response->render());
		$clientResponse->method('getStatusCode')
			->willReturn($response->getStatus());
		$clientResponse->method('getHeader')->willReturnCallback(
			static fn (string $name): string
				=> (string)($headers[$name] ?? ''),
		);
		return $clientResponse;
	}

	/**
	 * @return array{Signatory, Key}
	 */
	private function ecdsaP256Material(string $keyId): array {
		$keyPair = openssl_pkey_new([
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name' => 'prime256v1',
		]);
		$privateKey = '';
		openssl_pkey_export($keyPair, $privateKey);
		$details = openssl_pkey_get_details($keyPair);
		$signatory = new Signatory(true);
		$signatory->setKeyId($keyId);
		$signatory->setPublicKey($details['key']);
		$signatory->setPrivateKey($privateKey);
		$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
		$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
		$key = JWK::parseKey([
			'kty' => 'EC',
			'crv' => 'P-256',
			'kid' => $keyId,
			'alg' => 'ES256',
			'x' => self::base64Url($x),
			'y' => self::base64Url($y),
		], 'ES256');
		return [$signatory, $key];
	}

	private static function base64Url(string $value): string {
		return rtrim(
			strtr(base64_encode($value), '+/', '-_'),
			'=',
		);
	}
}
