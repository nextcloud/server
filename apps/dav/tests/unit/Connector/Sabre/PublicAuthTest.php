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
use OCP\Security\Bruteforce\IThrottler;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class PublicAuthTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector
 */
class PublicAuthTest extends \Test\TestCase {

	private ISession&MockObject $session;
	private IRequest&MockObject $request;
	private IManager&MockObject $shareManager;
	private IThrottler&MockObject $throttler;
	private LoggerInterface&MockObject $logger;
	private IURLGenerator&MockObject $urlGenerator;
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

		$this->auth = new PublicAuth(
			$this->request,
			$this->shareManager,
			$this->session,
			$this->throttler,
			$this->logger,
			$this->urlGenerator,
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
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$result = self::invokePrivate($this->auth, 'getToken');

		$this->assertSame('GX9HSGQrGE', $result);
	}

	public function testGetTokenInvalid(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files');

		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		self::invokePrivate($this->auth, 'getToken');
	}

	public function testCheckTokenValidShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn(null);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'checkToken');
		$this->assertSame([true, 'principals/GX9HSGQrGE'], $result);
	}

	public function testCheckTokenInvalidShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->will($this->throwException(new ShareNotFound()));

		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenAlreadyAuthenticated(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('42');

		$result = self::invokePrivate($this->auth, 'checkToken');
		$this->assertSame([true, 'principals/GX9HSGQrGE'], $result);
	}

	public function testCheckTokenPasswordNotAuthenticated(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(false);

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenPasswordAuthenticatedWrongShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(false);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('43');

		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		self::invokePrivate($this->auth, 'checkToken');
	}

	public function testNoShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willThrowException(new ShareNotFound());

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testShareNoPassword(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn(null);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordFancyShareType(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordRemote(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_REMOTE);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkValidPassword(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);

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

	public function testSharePasswordMailValidPassword(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
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
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getId')->willReturn('42');

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

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('42');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkInvalidSession(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getId')->willReturn('42');

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

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('43');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordMailInvalidSession(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_EMAIL);
		$share->method('getId')->willReturn('42');

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

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('43');

		$result = self::invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}
}
