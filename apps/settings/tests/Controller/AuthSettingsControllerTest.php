<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Settings\Controller;

use OC\AppFramework\Http;
use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\Token\IWipeableToken;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\RemoteWipe;
use OCA\Settings\Controller\AuthSettingsController;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AuthSettingsControllerTest extends TestCase {

	/** @var AuthSettingsController */
	private $controller;
	/** @var IRequest|MockObject */
	private $request;
	/** @var IProvider|MockObject */
	private $tokenProvider;
	/** @var ISession|MockObject */
	private $session;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var ISecureRandom|MockObject */
	private $secureRandom;
	/** @var IManager|MockObject */
	private $activityManager;
	/** @var RemoteWipe|MockObject */
	private $remoteWipe;
	private $uid = 'jane';

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->activityManager = $this->createMock(IManager::class);
		$this->remoteWipe = $this->createMock(RemoteWipe::class);
		/** @var LoggerInterface|MockObject $logger */
		$logger = $this->createMock(LoggerInterface::class);

		$this->controller = new AuthSettingsController(
			'core',
			$this->request,
			$this->tokenProvider,
			$this->session,
			$this->secureRandom,
			$this->uid,
			$this->userSession,
			$this->activityManager,
			$this->remoteWipe,
			$logger
		);
	}

	public function testCreate(): void {
		$name = 'Nexus 4';
		$sessionToken = $this->createMock(IToken::class);
		$deviceToken = $this->createMock(IToken::class);
		$password = '123456';

		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessionid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessionid')
			->willReturn($sessionToken);
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($sessionToken, 'sessionid')
			->willReturn($password);
		$sessionToken->expects($this->once())
			->method('getLoginName')
			->willReturn('User13');

		$this->secureRandom->expects($this->exactly(5))
			->method('generate')
			->with(5, ISecureRandom::CHAR_HUMAN_READABLE)
			->willReturn('XXXXX');
		$newToken = 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX';

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($newToken, $this->uid, 'User13', $password, $name, IToken::PERMANENT_TOKEN)
			->willReturn($deviceToken);

		$deviceToken->expects($this->once())
			->method('jsonSerialize')
			->willReturn(['dummy' => 'dummy', 'canDelete' => true]);

		$this->mockActivityManager();

		$expected = [
			'token' => $newToken,
			'deviceToken' => ['dummy' => 'dummy', 'canDelete' => true, 'canRename' => true],
			'loginName' => 'User13',
		];

		$response = $this->controller->create($name);
		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expected, $response->getData());
	}

	public function testCreateSessionNotAvailable(): void {
		$name = 'personal phone';

		$this->session->expects($this->once())
			->method('getId')
			->will($this->throwException(new SessionNotAvailableException()));

		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_SERVICE_UNAVAILABLE);

		$this->assertEquals($expected, $this->controller->create($name));
	}

	public function testCreateInvalidToken(): void {
		$name = 'Company IPhone';

		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessionid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessionid')
			->will($this->throwException(new InvalidTokenException()));

		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_SERVICE_UNAVAILABLE);

		$this->assertEquals($expected, $this->controller->create($name));
	}

	public function testDestroy(): void {
		$tokenId = 124;
		$token = $this->createMock(PublicKeyToken::class);

		$this->mockGetTokenById($tokenId, $token);
		$this->mockActivityManager();

		$token->expects($this->exactly(2))
			->method('getId')
			->willReturn($tokenId);

		$token->expects($this->once())
			->method('getUID')
			->willReturn('jane');

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with($this->uid, $tokenId);

		$this->assertEquals([], $this->controller->destroy($tokenId));
	}

	public function testDestroyExpired(): void {
		$tokenId = 124;
		$token = $this->createMock(PublicKeyToken::class);

		$token->expects($this->exactly(2))
			->method('getId')
			->willReturn($tokenId);

		$token->expects($this->once())
			->method('getUID')
			->willReturn($this->uid);

		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo($tokenId))
			->willThrowException(new ExpiredTokenException($token));

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with($this->uid, $tokenId);

		$this->assertSame([], $this->controller->destroy($tokenId));
	}

	public function testDestroyWrongUser(): void {
		$tokenId = 124;
		$token = $this->createMock(PublicKeyToken::class);

		$this->mockGetTokenById($tokenId, $token);

		$token->expects($this->once())
			->method('getUID')
			->willReturn('foobar');

		$response = $this->controller->destroy($tokenId);
		$this->assertSame([], $response->getData());
		$this->assertSame(\OCP\AppFramework\Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function dataRenameToken(): array {
		return [
			'App password => Other token name' => ['App password', 'Other token name'],
			'Other token name => App password' => ['Other token name', 'App password'],
		];
	}

	/**
	 * @dataProvider dataRenameToken
	 *
	 * @param string $name
	 * @param string $newName
	 */
	public function testUpdateRename(string $name, string $newName): void {
		$tokenId = 42;
		$token = $this->createMock(PublicKeyToken::class);

		$this->mockGetTokenById($tokenId, $token);
		$this->mockActivityManager();

		$token->expects($this->once())
			->method('getUID')
			->willReturn('jane');

		$token->expects($this->once())
			->method('getName')
			->willReturn($name);

		$token->expects($this->once())
			->method('getScopeAsArray')
			->willReturn([IToken::SCOPE_FILESYSTEM => true]);

		$token->expects($this->once())
			->method('setName')
			->with($this->equalTo($newName));

		$this->tokenProvider->expects($this->once())
			->method('updateToken')
			->with($this->equalTo($token));

		$this->assertSame([], $this->controller->update($tokenId, [IToken::SCOPE_FILESYSTEM => true], $newName));
	}

	public function dataUpdateFilesystemScope(): array {
		return [
			'Grant filesystem access' => [false, true],
			'Revoke filesystem access' => [true, false],
		];
	}

	/**
	 * @dataProvider dataUpdateFilesystemScope
	 *
	 * @param bool $filesystem
	 * @param bool $newFilesystem
	 */
	public function testUpdateFilesystemScope(bool $filesystem, bool $newFilesystem): void {
		$tokenId = 42;
		$token = $this->createMock(PublicKeyToken::class);

		$this->mockGetTokenById($tokenId, $token);
		$this->mockActivityManager();

		$token->expects($this->once())
			->method('getUID')
			->willReturn('jane');

		$token->expects($this->once())
			->method('getName')
			->willReturn('App password');

		$token->expects($this->once())
			->method('getScopeAsArray')
			->willReturn([IToken::SCOPE_FILESYSTEM => $filesystem]);

		$token->expects($this->once())
			->method('setScope')
			->with($this->equalTo([IToken::SCOPE_FILESYSTEM => $newFilesystem]));

		$this->tokenProvider->expects($this->once())
			->method('updateToken')
			->with($this->equalTo($token));

		$this->assertSame([], $this->controller->update($tokenId, [IToken::SCOPE_FILESYSTEM => $newFilesystem], 'App password'));
	}

	public function testUpdateNoChange(): void {
		$tokenId = 42;
		$token = $this->createMock(PublicKeyToken::class);

		$this->mockGetTokenById($tokenId, $token);

		$token->expects($this->once())
			->method('getUID')
			->willReturn('jane');

		$token->expects($this->once())
			->method('getName')
			->willReturn('App password');

		$token->expects($this->once())
			->method('getScopeAsArray')
			->willReturn([IToken::SCOPE_FILESYSTEM => true]);

		$token->expects($this->never())
			->method('setName');

		$token->expects($this->never())
			->method('setScope');

		$this->tokenProvider->expects($this->once())
			->method('updateToken')
			->with($this->equalTo($token));

		$this->assertSame([], $this->controller->update($tokenId, [IToken::SCOPE_FILESYSTEM => true], 'App password'));
	}

	public function testUpdateExpired(): void {
		$tokenId = 42;
		$token = $this->createMock(PublicKeyToken::class);

		$token->expects($this->once())
			->method('getUID')
			->willReturn($this->uid);

		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo($tokenId))
			->willThrowException(new ExpiredTokenException($token));

		$this->tokenProvider->expects($this->once())
			->method('updateToken')
			->with($this->equalTo($token));

		$this->assertSame([], $this->controller->update($tokenId, [IToken::SCOPE_FILESYSTEM => true], 'App password'));
	}

	public function testUpdateTokenWrongUser(): void {
		$tokenId = 42;
		$token = $this->createMock(PublicKeyToken::class);

		$this->mockGetTokenById($tokenId, $token);

		$token->expects($this->once())
			->method('getUID')
			->willReturn('foobar');

		$token->expects($this->never())
			->method('setScope');
		$this->tokenProvider->expects($this->never())
			->method('updateToken');

		$response = $this->controller->update($tokenId, [IToken::SCOPE_FILESYSTEM => true], 'App password');
		$this->assertSame([], $response->getData());
		$this->assertSame(\OCP\AppFramework\Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testUpdateTokenNonExisting(): void {
		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willThrowException(new InvalidTokenException('Token does not exist'));

		$this->tokenProvider->expects($this->never())
			->method('updateToken');

		$response = $this->controller->update(42, [IToken::SCOPE_FILESYSTEM => true], 'App password');
		$this->assertSame([], $response->getData());
		$this->assertSame(\OCP\AppFramework\Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	private function mockActivityManager(): void {
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($this->createMock(IEvent::class));
		$this->activityManager->expects($this->once())
			->method('publish');
	}

	/**
	 * @param int $tokenId
	 * @param $token
	 */
	private function mockGetTokenById(int $tokenId, $token): void {
		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo($tokenId))
			->willReturn($token);
	}

	public function testRemoteWipeNotSuccessful(): void {
		$token = $this->createMock(IToken::class);
		$token->expects($this->once())
			->method('getUID')
			->willReturn($this->uid);
		$this->mockGetTokenById(123, $token);

		$this->remoteWipe->expects($this->once())
			->method('markTokenForWipe')
			->with($token)
			->willReturn(false);

		$response = $this->controller->wipe(123);

		$expected = new JSONResponse([], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expected, $response);
	}

	public function testRemoteWipeWrongUser(): void {
		$token = $this->createMock(IToken::class);
		$token->expects($this->once())
			->method('getUID')
			->willReturn('definetly-not-' . $this->uid);
		$this->mockGetTokenById(123, $token);

		$this->remoteWipe->expects($this->never())
			->method('markTokenForWipe');

		$response = $this->controller->wipe(123);

		$expected = new JSONResponse([], Http::STATUS_NOT_FOUND);
		$this->assertEquals($expected, $response);
	}

	public function testRemoteWipeSuccessful(): void {
		$token = $this->createMock(IWipeableToken::class);
		$token->expects($this->once())
			->method('getUID')
			->willReturn($this->uid);
		$this->mockGetTokenById(123, $token);

		$this->remoteWipe->expects($this->once())
			->method('markTokenForWipe')
			->with($token)
			->willReturn(true);

		$response = $this->controller->wipe(123);

		$expected = new JSONResponse([]);
		$this->assertEquals($expected, $response);
	}
}
