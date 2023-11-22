<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector;

use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Class PublicAuthTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector
 */
class PublicAuthTest extends \Test\TestCase {

	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;
	/** @var \OCA\DAV\Connector\PublicAuth */
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

		$this->auth = new \OCA\DAV\Connector\PublicAuth(
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

	public function testSharePasswordLinkValidSession(): void {
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
