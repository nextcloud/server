<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\Controller;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OC\Authentication\Token\IProvider;
use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Controller\TokenController;
use OCA\CloudFederationAPI\Db\OcmTokenMapMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\Signatory;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TokenControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IProvider&MockObject $tokenProvider;
	private ISecureRandom&MockObject $random;
	private ITimeFactory&MockObject $timeFactory;
	private LoggerInterface&MockObject $logger;
	private ISignatureManager&MockObject $signatureManager;
	private OCMSignatoryManager&MockObject $signatoryManager;
	private IAppConfig&MockObject $appConfig;
	private OcmTokenMapMapper&MockObject $ocmTokenMapMapper;
	private IShareManager&MockObject $shareManager;

	private TokenController $controller;

	/** Public key matching the signatory private key configured by configureHappyPath(). */
	private string $publicKeyPem = '';

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->signatoryManager = $this->createMock(OCMSignatoryManager::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->ocmTokenMapMapper = $this->createMock(OcmTokenMapMapper::class);
		$this->shareManager = $this->createMock(IShareManager::class);

		$this->controller = new TokenController(
			$this->request,
			$this->tokenProvider,
			$this->random,
			$this->timeFactory,
			$this->logger,
			$this->signatureManager,
			$this->signatoryManager,
			$this->appConfig,
			$this->ocmTokenMapMapper,
			$this->shareManager,
		);
	}

	#[\Override]
	protected function tearDown(): void {
		JWT::$timestamp = null;
		parent::tearDown();
	}

	/**
	 * Configure the collaborators so that exchanging $refreshToken issues a JWT
	 * access token. Returns the refresh token mock for further expectations.
	 */
	private function configureHappyPath(
		string $refreshToken,
		int $tokenId,
		string $uid,
		string $shareOwner,
		string $sharedWith,
		string $jti,
		array $scope = [IToken::SCOPE_FILESYSTEM => true],
	): IToken&MockObject {
		$privateKey = openssl_pkey_new([
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		]);
		openssl_pkey_export($privateKey, $privateKeyPem);
		$this->publicKeyPem = openssl_pkey_get_details($privateKey)['key'];

		$refreshTokenMock = $this->createMock(IToken::class);
		$refreshTokenMock->method('getType')->willReturn(IToken::PERMANENT_TOKEN);
		$refreshTokenMock->method('getId')->willReturn($tokenId);
		$refreshTokenMock->method('getUID')->willReturn($uid);
		$refreshTokenMock->method('getLoginName')->willReturn($uid);
		$refreshTokenMock->method('getScopeAsArray')->willReturn($scope);
		$this->tokenProvider->method('getToken')
			->with($refreshToken)
			->willReturn($refreshTokenMock);

		$this->ocmTokenMapMapper->method('findByRefreshToken')
			->with($refreshToken)
			->willReturn(null);

		$share = $this->createMock(IShare::class);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getId')->willReturn('789');
		$this->shareManager->method('getShareByToken')
			->with($refreshToken)
			->willReturn($share);

		$signatory = new Signatory();
		$signatory->setKeyId('https://local.example.com/index.php/ocm#signature');
		$signatory->setPrivateKey($privateKeyPem);
		$this->signatoryManager->method('getLocalJwksSignatory')->willReturn($signatory);

		$this->random->method('generate')->willReturn($jti);
		$this->timeFactory->method('getTime')->willReturn(1000000);

		$accessToken = $this->createMock(IToken::class);
		$accessToken->method('getId')->willReturn(456);
		$this->tokenProvider->method('generateToken')->willReturn($accessToken);

		return $refreshTokenMock;
	}

	public function testAccessTokenSuccess(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->with($this->signatoryManager)
			->willReturn($signedRequest);

		$this->configureHappyPath('valid-refresh-token', 123, 'testuser', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00');

		$this->ocmTokenMapMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function ($mapping) {
				return $mapping->getAccessTokenId() === 456
					&& $mapping->getRefreshToken() === 'valid-refresh-token'
					&& $mapping->getExpires() === 1000000 + 3600;
			}));

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
		$this->assertSame('sharee@remote.example.com', $decoded->aud);
		$this->assertSame('789', $decoded->client_id);
		$this->assertSame('fixedjtivalue00', $decoded->jti);
		$this->assertSame(1000000, $decoded->iat);
		$this->assertSame(1000000 + 3600, $decoded->exp);
	}

	public function testAccessTokenLocksRefreshTokenToExchangeOnly(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->with($this->signatoryManager)
			->willReturn($signedRequest);

		$refreshTokenMock = $this->configureHappyPath('valid-refresh-token', 123, 'testuser', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00');

		// The refresh token must be downgraded so it can no longer mount the
		// filesystem, only be replayed against the token endpoint.
		$refreshTokenMock->expects($this->once())
			->method('setScope')
			->with($this->callback(fn (array $scope): bool => ($scope[IToken::SCOPE_FILESYSTEM] ?? null) === false));

		$result = $this->controller->accessToken('authorization_code', 'valid-refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	public function testAccessTokenDoesNotRelockAlreadyLockedRefreshToken(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->with($this->signatoryManager)
			->willReturn($signedRequest);

		$refreshTokenMock = $this->configureHappyPath('valid-refresh-token', 123, 'testuser', 'owner', 'sharee@remote.example.com', 'fixedjtivalue00', [IToken::SCOPE_FILESYSTEM => false]);

		// Already locked from a previous exchange: do not rewrite the scope.
		$refreshTokenMock->expects($this->never())->method('setScope');

		$result = $this->controller->accessToken('authorization_code', 'valid-refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	public function testAccessTokenWithoutSignatureEnforcementDisabled(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(false);

		$this->configureHappyPath('refresh-token', 123, 'testuser', 'owner', 'sharee', 'fixedjtivalue00');

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
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$result = $this->controller->accessToken('password', 'refresh-token');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'unsupported_grant_type'], $result->getData());
	}

	public function testAccessTokenMissingGrantType(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$result = $this->controller->accessToken('', 'refresh-token');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'unsupported_grant_type'], $result->getData());
	}

	public function testAccessTokenMissingRefreshToken(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$result = $this->controller->accessToken('authorization_code', '');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'refresh_token is required'], $result->getData());
	}

	public function testAccessTokenNonPermanentToken(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$refreshToken = $this->createMock(IToken::class);
		$refreshToken->method('getType')->willReturn(IToken::TEMPORARY_TOKEN);
		$refreshToken->method('getId')->willReturn(123);

		$this->tokenProvider->method('getToken')
			->with('non-permanent-token')
			->willReturn($refreshToken);

		$result = $this->controller->accessToken('authorization_code', 'non-permanent-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenInvalidToken(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$this->tokenProvider->method('getToken')
			->with('invalid-token')
			->willThrowException(new InvalidTokenException());

		$result = $this->controller->accessToken('authorization_code', 'invalid-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenExpiredToken(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$this->tokenProvider->method('getToken')
			->with('expired-token')
			->willThrowException(new ExpiredTokenException($this->createMock(IToken::class)));

		$result = $this->controller->accessToken('authorization_code', 'expired-token');

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_grant'], $result->getData());
	}

	public function testAccessTokenServerError(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');
		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$this->tokenProvider->method('getToken')
			->willThrowException(new \RuntimeException('Database connection failed'));

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

		$this->configureHappyPath('refresh-token', 123, 'testuser', 'owner', 'sharee', 'fixedjtivalue00');

		$result = $this->controller->accessToken('authorization_code', 'refresh-token');

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}
}
