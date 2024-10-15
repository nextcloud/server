<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector;

use OCA\DAV\Connector\LegacyPublicAuth;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Class LegacyPublicAuthTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector
 */
class LegacyPublicAuthTest extends \Test\TestCase {

	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;
	/** @var LegacyPublicAuth */
	private $auth;
	/** @var IThrottler|\PHPUnit\Framework\MockObject\MockObject */
	private $throttler;

	/** @var string */
	private $oldUser;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->getMockBuilder(ISession::class)
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();
		$this->shareManager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->throttler = $this->getMockBuilder(IThrottler::class)
			->disableOriginalConstructor()
			->getMock();

		$this->auth = new LegacyPublicAuth(
			$this->request,
			$this->shareManager,
			$this->session,
			$this->throttler
		);

		// Store current user
		$this->oldUser = \OC_User::getUser();
	}

	protected function tearDown(): void {
		\OC_User::setIncognitoMode(false);

		// Set old user
		\OC_User::setUserId($this->oldUser);
		\OC_Util::setupFS($this->oldUser);

		parent::tearDown();
	}

	public function testNoShare(): void {
		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willThrowException(new ShareNotFound());

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testShareNoPassword(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn(null);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordFancyShareType(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordRemote(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_REMOTE);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkValidPassword(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(true);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordMailValidPassword(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_EMAIL);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->expects($this->once())
			->method('checkPassword')->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(true);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testInvalidSharePasswordLinkValidSession(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getId')->willReturn('42');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(false);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('42');

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkInvalidSession(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getId')->willReturn('42');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(false);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('43');

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordMailInvalidSession(): void {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_EMAIL);
		$share->method('getId')->willReturn('42');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$this->shareManager->method('checkPassword')
			->with(
				$this->equalTo($share),
				$this->equalTo('password')
			)->willReturn(false);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('43');

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}
}
