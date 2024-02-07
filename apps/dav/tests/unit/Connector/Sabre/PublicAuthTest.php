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
use Psr\Log\LoggerInterface;

/**
 * Class PublicAuthTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector
 */
class PublicAuthTest extends \Test\TestCase {

	/** @var ISession|MockObject */
	private $session;
	/** @var IRequest|MockObject */
	private $request;
	/** @var IManager|MockObject */
	private $shareManager;
	/** @var PublicAuth */
	private $auth;
	/** @var IThrottler|MockObject */
	private $throttler;
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var string */
	private $oldUser;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->auth = new \OCA\DAV\Connector\Sabre\PublicAuth(
			$this->request,
			$this->shareManager,
			$this->session,
			$this->throttler,
			$this->logger,
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

	public function testGetToken(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$result = $this->invokePrivate($this->auth, 'getToken');

		$this->assertSame('GX9HSGQrGE', $result);
	}

	public function testGetTokenInvalid(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files');

		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		$this->invokePrivate($this->auth, 'getToken');
	}

	public function testCheckTokenValidShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn(null);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'checkToken');
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
		$this->invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenAlreadyAuthenticated(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('42');
	
		$result = $this->invokePrivate($this->auth, 'checkToken');
		$this->assertSame([true, 'principals/GX9HSGQrGE'], $result);
	}

	public function testCheckTokenPasswordNotAuthenticated(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(false);
	
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		$this->invokePrivate($this->auth, 'checkToken');
	}

	public function testCheckTokenPasswordAuthenticatedWrongShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(false);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('43');
	
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		$this->invokePrivate($this->auth, 'checkToken');
	}

	public function testNoShare(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willThrowException(new ShareNotFound());

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testShareNoPassword(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn(null);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordFancyShareType(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordRemote(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(IShare::TYPE_REMOTE);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('GX9HSGQrGE')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkValidPassword(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
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

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordMailValidPassword(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
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

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testInvalidSharePasswordLinkValidSession(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
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

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkInvalidSession(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
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

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordMailInvalidSession(): void {
		$this->request->method('getPathInfo')
			->willReturn('/dav/files/GX9HSGQrGE');

		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()
			->getMock();
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

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}
}
