<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Connector;

use OCA\DAV\Connector\Sabre\PublicAuth;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class PublicAuthTest
 *
 *
 * @package OCA\DAV\Tests\unit\Connector
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class PublicAuthTest extends \Test\TestCase {

	private ISession&MockObject $session;
	private IRequest&MockObject $request;
	private IManager&MockObject $shareManager;
	private IThrottler&MockObject $throttler;
	private LoggerInterface&MockObject $logger;
	private IURLGenerator&MockObject $urlGenerator;
	private IUserSession&MockObject $userSession;
	private PublicAuth $auth;

	private bool|string $oldUser;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->auth = new PublicAuth(
			$this->request,
			$this->shareManager,
			$this->session,
			$this->throttler,
			$this->logger,
			$this->urlGenerator,
			$this->userSession,
		);

		// Store current user
		$this->oldUser = \OC_User::getUser();
	}

	protected function tearDown(): void {
		\OC_User::setIncognitoMode(false);

		// Set old user
		\OC_User::setUserId($this->oldUser);
		if ($this->oldUser !== false) {
			\OC_Util::setupFS($this->oldUser);
		}

		parent::tearDown();
	}

	public function testGetToken(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$result = self::invokePrivate($this->auth, 'getToken');

		$this->assertSame('GX9HSGQrGE', $result);
	}

	public static function dataGetTokenNormalizesPath(): array {
		return [
			'parent segment' => ['/dav/uploads/tokenA/../tokenB/upload-id/1', 'tokenB'],
			'nested parent segments' => ['/dav/files/tokenA/../../uploads/tokenB/upload-id/1', 'tokenB'],
			'current segment' => ['/dav/uploads/./tokenB/upload-id/1', 'tokenB'],
			'encoded dots are not a parent segment' => ['/dav/uploads/%2e%2e/tokenB', '..'],
			'encoded token' => ['/dav/files/token%41', 'tokenA'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetTokenNormalizesPath')]
	public function testGetTokenNormalizesPath(string $rawPath, string $expected): void {
		$this->request->method('getRawPathInfo')
			->willReturn($rawPath);

		$this->assertSame($expected, self::invokePrivate($this->auth, 'getToken'));
	}

	public function testCheckTokenUsesNormalizedToken(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/uploads/tokenA/../tokenB/upload-id/1');

		$share = $this->createMock(IShare::class);
		$share->method('getToken')->willReturn('GX9HSGQrGE');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('tokenB')
			->willReturn($share);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn(null);

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testGetTokenInvalid(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files');

		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		self::invokePrivate($this->auth, 'getToken');
	}

	public function testCheckTokenValidShare(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getToken')->willReturn('GX9HSGQrGE');
		$share->method('getPassword')->willReturn(null);
		$share->method('isPasswordProtected')->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'checkToken');
		$this->assertSame([true, 'principals/GX9HSGQrGE'], $result);
	}

	public function testCheckTokenInvalidShare(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willThrowException(new ShareNotFound());

		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenAlreadyAuthenticated(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(42);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"GX9HSGQrGE":"password"}');

		$result = self::invokePrivate($this->auth, 'checkToken');
		$this->assertSame([true, 'principals/GX9HSGQrGE'], $result);
	}

	public function testCheckTokenAuthenticatedWithChangedPassword(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('new-password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(42);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"GX9HSGQrGE":"old-password"}');

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testSessionInvalidatedByPasswordChange(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$passwordHash = 'old-password';
		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturnCallback(static function () use (&$passwordHash) {
			return $passwordHash;
		});
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getId')->willReturn('42');
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);
		$this->shareManager->method('checkPassword')
			->willReturnCallback(static function (IShare $share, ?string $password) use (&$passwordHash) {
				return $password === $passwordHash;
			});

		$sessionData = [];
		$this->session->method('exists')->willReturnCallback(static function (string $key) use (&$sessionData) {
			return isset($sessionData[$key]);
		});
		$this->session->method('get')->willReturnCallback(static function (string $key) use (&$sessionData) {
			return $sessionData[$key] ?? null;
		});
		$this->session->method('set')->willReturnCallback(static function (string $key, $value) use (&$sessionData): void {
			$sessionData[$key] = $value;
		});

		$this->assertTrue(self::invokePrivate($this->auth, 'validateUserPass', ['username', 'old-password']));
		$this->assertSame([true, 'principals/GX9HSGQrGE'], self::invokePrivate($this->auth, 'checkToken'));

		$passwordHash = 'new-password';

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenPasswordNotAuthenticated(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getToken')->willReturn('GX9HSGQrGE');
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn(null);

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenPasswordAuthenticatedWrongShare(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(42);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"other-token":"password"}');

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testNoShare(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willThrowException(new ShareNotFound());

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testShareNoPassword(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn(null);
		$share->method('isPasswordProtected')->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordFancyShareType(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testSharePasswordRemote(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_REMOTE);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkValidPassword(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(true);

		$this->session->expects($this->once())
			->method('set')
			->with('public_link_authenticated_frontend', '{"GX9HSGQrGE":"password"}');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordNotHashedNotStoredInSession(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(false);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->method('checkPassword')->willReturn(true);

		$this->session->expects($this->never())->method('set');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordMailValidPassword(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getToken')->willReturn('GX9HSGQrGE');
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_EMAIL);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(true);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testInvalidSharePasswordLinkValidSession(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(false);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"GX9HSGQrGE":"password"}');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testInvalidSharePasswordLinkSessionWithChangedPassword(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('new-password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('old-password')
			)->willReturn(false);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"GX9HSGQrGE":"old-password"}');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'old-password']);

		$this->assertFalse($result);
	}

	public function testSharePasswordLinkInvalidSession(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(false);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"other-token":"password"}');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testSharePasswordMailInvalidSession(): void {
		$this->request->method('getRawPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('isPasswordProtected')->willReturn(true);
		$share->method('isPasswordHashed')->willReturn(true);
		$share->method('getShareType')->willReturn(IShare::TYPE_EMAIL);
		$share->method('getToken')->willReturn('GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(false);

		$this->session->method('get')->with('public_link_authenticated_frontend')->willReturn('{"other-token":"password"}');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}
}
