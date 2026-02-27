<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Controller;

use OC\Authentication\Token\IProvider;
use OC\OCM\OCMSignatoryManager;
use OCA\DAV\Controller\TokenController;
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

	private TokenController $controller;

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

		$this->controller = new TokenController(
			$this->request,
			$this->tokenProvider,
			$this->random,
			$this->timeFactory,
			$this->logger,
			$this->signatureManager,
			$this->signatoryManager,
			$this->appConfig,
		);
	}

	public function testAccessTokenSuccess(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');

		$this->signatureManager->method('getIncomingSignedRequest')
			->with($this->signatoryManager)
			->willReturn($signedRequest);

		$refreshToken = $this->createMock(IToken::class);
		$refreshToken->method('getType')->willReturn(IToken::PERMANENT_TOKEN);
		$refreshToken->method('getId')->willReturn(123);
		$refreshToken->method('getLoginName')->willReturn('testuser');

		$this->tokenProvider->method('getToken')
			->with('valid-refresh-token')
			->willReturn($refreshToken);

		$this->random->method('generate')
			->with(64, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS)
			->willReturn('generated-access-token');

		$this->timeFactory->method('getTime')->willReturn(1000000);

		$accessToken = $this->createMock(IToken::class);
		$this->tokenProvider->method('generateToken')
			->with(
				'generated-access-token',
				'valid-refresh-token',
				'testuser',
				null,
				'OCM Access Token',
				IToken::TEMPORARY_TOKEN,
				IToken::DO_NOT_REMEMBER
			)
			->willReturn($accessToken);

		$accessToken->expects($this->once())
			->method('setExpires')
			->with(1000000 + 3600);

		$this->tokenProvider->expects($this->once())
			->method('updateToken')
			->with($accessToken);

		// Simulate POST body
		$this->simulatePostBody('grant_type=authorization_code&code=valid-refresh-token');

		$result = $this->controller->accessToken();

		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
		$this->assertEquals([
			'access_token' => 'generated-access-token',
			'token_type' => 'Bearer',
			'expires_in' => 3600,
		], $result->getData());
	}

	public function testAccessTokenWithoutSignatureEnforcementDisabled(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(false);

		$refreshToken = $this->createMock(IToken::class);
		$refreshToken->method('getType')->willReturn(IToken::PERMANENT_TOKEN);
		$refreshToken->method('getLoginName')->willReturn('testuser');

		$this->tokenProvider->method('getToken')
			->willReturn($refreshToken);

		$this->random->method('generate')->willReturn('generated-access-token');
		$this->timeFactory->method('getTime')->willReturn(1000000);

		$accessToken = $this->createMock(IToken::class);
		$this->tokenProvider->method('generateToken')->willReturn($accessToken);

		$this->simulatePostBody('grant_type=authorization_code&code=refresh-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	public function testAccessTokenWithoutSignatureEnforcementEnabled(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(true);

		$this->simulatePostBody('grant_type=authorization_code&code=refresh-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_request'], $result->getData());
	}

	public function testAccessTokenInvalidSignature(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatureException('Invalid signature'));

		$this->simulatePostBody('grant_type=authorization_code&code=refresh-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $result->getStatus());
		$this->assertEquals(['error' => 'invalid_request'], $result->getData());
	}

	public function testAccessTokenUnsupportedGrantType(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');

		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$this->simulatePostBody('grant_type=password&code=refresh-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'unsupported_grant_type'], $result->getData());
	}

	public function testAccessTokenMissingGrantType(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');

		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$this->simulatePostBody('code=refresh-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEquals(['error' => 'unsupported_grant_type'], $result->getData());
	}

	public function testAccessTokenMissingRefreshToken(): void {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example.com');

		$this->signatureManager->method('getIncomingSignedRequest')
			->willReturn($signedRequest);

		$this->simulatePostBody('grant_type=authorization_code');

		$result = $this->controller->accessToken();

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

		$this->simulatePostBody('grant_type=authorization_code&code=non-permanent-token');

		$result = $this->controller->accessToken();

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

		$this->simulatePostBody('grant_type=authorization_code&code=invalid-token');

		$result = $this->controller->accessToken();

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

		$this->simulatePostBody('grant_type=authorization_code&code=expired-token');

		$result = $this->controller->accessToken();

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

		$this->simulatePostBody('grant_type=authorization_code&code=some-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $result->getStatus());
		$this->assertEquals(['error' => 'server_error'], $result->getData());
	}

	public function testAccessTokenWithSignatoryNotFoundException(): void {
		$this->signatureManager->method('getIncomingSignedRequest')
			->willThrowException(new SignatoryNotFoundException());

		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, false, true)
			->willReturn(false);

		$refreshToken = $this->createMock(IToken::class);
		$refreshToken->method('getType')->willReturn(IToken::PERMANENT_TOKEN);
		$refreshToken->method('getLoginName')->willReturn('testuser');

		$this->tokenProvider->method('getToken')->willReturn($refreshToken);
		$this->random->method('generate')->willReturn('generated-access-token');
		$this->timeFactory->method('getTime')->willReturn(1000000);

		$accessToken = $this->createMock(IToken::class);
		$this->tokenProvider->method('generateToken')->willReturn($accessToken);

		$this->simulatePostBody('grant_type=authorization_code&code=refresh-token');

		$result = $this->controller->accessToken();

		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
	}

	private function simulatePostBody(string $body): void {
		// We need to use a stream wrapper to simulate php://input
		stream_wrapper_unregister('php');
		stream_wrapper_register('php', TestPhpInputStream::class);
		TestPhpInputStream::$body = $body;
	}

	protected function tearDown(): void {
		// Restore the original php stream wrapper
		stream_wrapper_restore('php');
		parent::tearDown();
	}
}

/**
 * Helper class to simulate php://input
 */
class TestPhpInputStream {
	public static string $body = '';
	private int $position = 0;
	public mixed $context = null;

	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool {
		if ($path === 'php://input') {
			$this->position = 0;
			return true;
		}
		return false;
	}

	public function stream_read(int $count): string {
		$result = substr(self::$body, $this->position, $count);
		$this->position += strlen($result);
		return $result;
	}

	public function stream_eof(): bool {
		return $this->position >= strlen(self::$body);
	}

	public function stream_stat(): array {
		return [];
	}
}
