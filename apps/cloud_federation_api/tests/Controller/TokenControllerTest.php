<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\Controller;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Controller\TokenController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\Signatory;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TokenControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private ISecureRandom&MockObject $random;
	private ITimeFactory&MockObject $timeFactory;
	private LoggerInterface&MockObject $logger;
	private ISignatureManager&MockObject $signatureManager;
	private OCMSignatoryManager&MockObject $signatoryManager;
	private IAppConfig&MockObject $appConfig;
	private IShareManager&MockObject $shareManager;
	private ICloudIdManager&MockObject $cloudIdManager;

	private TokenController $controller;

	/** Public key matching the signatory private key configured by mockSignatory(). */
	private string $publicKeyPem = '';

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->signatureManager->method('extractIdentityFromUri')
			->willReturnCallback(static fn (string $uri): string => (string)parse_url($uri, PHP_URL_HOST));
		$this->signatoryManager = $this->createMock(OCMSignatoryManager::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);

		$this->controller = new TokenController(
			$this->request,
			$this->random,
			$this->timeFactory,
			$this->logger,
			$this->signatureManager,
			$this->signatoryManager,
			$this->appConfig,
			$this->shareManager,
			$this->cloudIdManager,
		);
	}

	#[\Override]
	protected function tearDown(): void {
		JWT::$timestamp = null;
		parent::tearDown();
	}

	/**
	 * Accept any signed request, as if the remote had signed it correctly.
	 */
	private function mockSignedRequest(string $origin = 'remote.example.com'): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn($origin);
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);
	}

	/**
	 * Publish a freshly generated RSA signatory and pin the issue time and jti.
	 */
	private function mockSignatory(string $jti): void {
		$privateKey = openssl_pkey_new([
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		]);
		openssl_pkey_export($privateKey, $privateKeyPem);
		$this->publicKeyPem = openssl_pkey_get_details($privateKey)['key'];

		$signatory = new Signatory();
		$signatory->setKeyId('https://local.example.com/index.php/ocm#signature');
		$signatory->setPrivateKey($privateKeyPem);
		$this->signatoryManager->method('getLocalJwksSignatory')->willReturn($signatory);

		$this->random->method('generate')->willReturn($jti);
		$this->timeFactory->method('getTime')->willReturn(1000000);
	}

	/**
	 * Make $refreshToken resolve to a share, and that share's recipient to a remote.
	 *
	 * @param string|null $storedToken token stored on the share, as compared against the presented one
	 */
	private function mockShare(
		string $refreshToken,
		?string $storedToken,
		int $shareType = IShare::TYPE_REMOTE,
		string $shareOwner = 'owner',
		string $sharedWith = 'sharee@remote.example.com',
	): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getToken')->willReturn($storedToken);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getId')->willReturn('789');
		$this->shareManager->method('getShareByToken')
			->with($refreshToken)
			->willReturn($share);

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getRemote')->willReturn('https://remote.example.com');
		$this->cloudIdManager->method('resolveCloudId')
			->with($sharedWith)
			->willReturn($cloudId);

		return $share;
	}

	/**
	 * Configure the collaborators so that exchanging $refreshToken issues a JWT
	 * access token. Returns the share mock for further expectations.
	 */
	private function configureHappyPath(
		string $refreshToken,
		string $shareOwner,
		string $sharedWith,
		string $jti,
		int $shareType = IShare::TYPE_REMOTE,
	): IShare&MockObject {
		$this->mockSignatory($jti);

		return $this->mockShare($refreshToken, $refreshToken, $shareType, $shareOwner, $sharedWith);
	}

	public function testAccessTokenSuccess(): void {
		$this->mockSignedRequest();
		$this->configureHappyPath('valid-refresh-token', 'owner', 'sharee@department@remote.example.com', 'fixedjtivalue00');

		$result = $this->controller->accessToken('authorization_code', 'valid-refresh-token');

		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertEquals(Http::STATUS_OK, $result->getStatus());

		$data = $result->getData();
		$this->assertSame('Bearer', $data['token_type']);
		$this->assertSame(3600, $data['expires_in']);
		$this->assertNotEmpty($data['access_token']);

		// Evaluate token validity at the mocked issue time, not the real clock.
		JWT::$timestamp = 1000000;
		$decoded = JWT::decode($data['access_token'], new Key($this->publicKeyPem, 'RS256'));
		$this->assertSame('https://local.example.com', $decoded->iss);
		$this->assertSame('owner', $decoded->sub);
		$this->assertSame('sharee@department@remote.example.com', $decoded->aud);
		$this->assertSame('789', $decoded->client_id);
		$this->assertSame('fixedjtivalue00', $decoded->jti);
		$this->assertSame(1000000, $decoded->iat);
		$this->assertSame(1000000 + 3600, $decoded->exp);
	}

	public function testAccessTokenVerifiesSignatureAgainstShareRecipientOrigin(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->expects($this->once())
			->method('getIncomingSignedRequest')
			->with($this->signatoryManager, null, 'remote.example.com')
			->willReturn($signedRequest);

		$this->configureHappyPath('valid-refresh-token', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00');

		$result = $this->controller->accessToken('authorization_code', 'valid-refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	public function testAccessTokenWithRemoteGroupShare(): void {
		$this->mockSignedRequest();
		$this->configureHappyPath('valid-refresh-token', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00', IShare::TYPE_REMOTE_GROUP);

		$result = $this->controller->accessToken('authorization_code', 'valid-refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	public function testAccessTokenWithNonFederatedShare(): void {
		$this->mockSignedRequest();
		$this->mockShare('link-share-token', 'link-share-token', IShare::TYPE_LINK);

		$result = $this->controller->accessToken('authorization_code', 'link-share-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenWithMismatchingShareToken(): void {
		$this->mockSignedRequest();
		$this->mockShare('presented-token', 'other-token');

		$result = $this->controller->accessToken('authorization_code', 'presented-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenWithTokenlessShare(): void {
		$this->mockSignedRequest();
		$this->mockShare('presented-token', null);

		$result = $this->controller->accessToken('authorization_code', 'presented-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenWithoutSignatureEnforcementDisabled(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(false);

		$this->configureHappyPath('refresh-token', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00');

		$result = $this->controller->accessToken('authorization_code', 'refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	public function testAccessTokenWithoutSignatureEnforcementEnabled(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(true);

		$result = $this->controller->accessToken('authorization_code', 'refresh-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_request'], $result->getData());
	}

	public function testAccessTokenInvalidSignature(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureException('Invalid signature'));

		$result = $this->controller->accessToken('authorization_code', 'refresh-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_request'], $result->getData());
	}

	public function testAccessTokenUnsupportedGrantType(): void {
		$this->mockSignedRequest();

		$result = $this->controller->accessToken('password', 'refresh-token');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'unsupported_grant_type'], $result->getData());
	}

	public function testAccessTokenMissingGrantType(): void {
		$this->mockSignedRequest();

		$result = $this->controller->accessToken('', 'refresh-token');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'unsupported_grant_type'], $result->getData());
	}

	public function testAccessTokenMissingRefreshToken(): void {
		$this->mockSignedRequest();

		$result = $this->controller->accessToken('authorization_code', '');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'refresh_token is required'], $result->getData());
	}

	public function testAccessTokenInvalidToken(): void {
		$this->mockSignedRequest();
		$this->shareManager->method('getShareByToken')
			->with('invalid-token')
			->willThrowException(new ShareNotFound());

		$result = $this->controller->accessToken('authorization_code', 'invalid-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenServerError(): void {
		$this->mockSignedRequest();
		$this->mockShare('some-token', 'some-token');
		$this->signatoryManager->method('getLocalJwksSignatory')->willReturn(null);

		$result = $this->controller->accessToken('authorization_code', 'some-token');

		$this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $result->getStatus());
		$this->assertEquals(['error' => 'server_error'], $result->getData());
	}

	public function testAccessTokenWithSignatoryNotFoundException(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatoryNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(false);

		$this->configureHappyPath('refresh-token', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00');

		$result = $this->controller->accessToken('authorization_code', 'refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}
}
