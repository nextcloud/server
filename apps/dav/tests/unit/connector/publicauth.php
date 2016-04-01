<?php

namespace OCA\DAV\Tests\Unit\Connector;

use OCP\IRequest;
use OCP\ISession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class PublicAuth extends \Test\TestCase {

	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;
	/** @var \OCA\DAV\Connector\PublicAuth */
	private $auth;

	/** @var string */
	private $oldUser;

	protected function setUp() {
		parent::setUp();

		$this->session = $this->getMock('\OCP\ISession');
		$this->request = $this->getMock('\OCP\IRequest');
		$this->shareManager = $this->getMock('\OCP\Share\IManager');

		$this->auth = new \OCA\DAV\Connector\PublicAuth(
			$this->request,
			$this->shareManager,
			$this->session
		);

		// Store current user
		$this->oldUser = \OC_User::getUser();
	}

	protected function tearDown() {
		\OC_User::setIncognitoMode(false);

		// Set old user
		\OC_User::setUserId($this->oldUser);
		\OC_Util::setupFS($this->oldUser);

		parent::tearDown();
	}

	public function testNoShare() {
		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willThrowException(new ShareNotFound());

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}

	public function testShareNoPassword() {
		$share = $this->getMock('OCP\Share\IShare');
		$share->method('getPassword')->willReturn(null);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordFancyShareType() {
		$share = $this->getMock('OCP\Share\IShare');
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertFalse($result);
	}


	public function testSharePasswordRemote() {
		$share = $this->getMock('OCP\Share\IShare');
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_REMOTE);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willReturn($share);

		$result = $this->invokePrivate($this->auth, 'validateUserPass', ['username', 'password']);

		$this->assertTrue($result);
	}

	public function testSharePasswordLinkValidPassword() {
		$share = $this->getMock('OCP\Share\IShare');
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);

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

	public function testSharePasswordLinkValidSession() {
		$share = $this->getMock('OCP\Share\IShare');
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
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

	public function testSharePasswordLinkInvalidSession() {
		$share = $this->getMock('OCP\Share\IShare');
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
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
