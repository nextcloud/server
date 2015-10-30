<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\Unit\Connector\Sabre;

use Test\TestCase;
use OCP\ISession;
use OCP\IUserSession;

/**
 * Class Auth
 *
 * @package OCA\DAV\Connector\Sabre
 */
class Auth extends TestCase {
	/** @var ISession */
	private $session;
	/** @var \OCA\DAV\Connector\Sabre\Auth */
	private $auth;
	/** @var IUserSession */
	private $userSession;

	public function setUp() {
		parent::setUp();
		$this->session = $this->getMockBuilder('\OCP\ISession')
			->disableOriginalConstructor()->getMock();
		$this->userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()->getMock();
		$this->auth = new \OCA\DAV\Connector\Sabre\Auth($this->session, $this->userSession);
	}

	public function testIsDavAuthenticatedWithoutDavSession() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue(null));

		$this->assertFalse($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testIsDavAuthenticatedWithWrongDavSession() {
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('AnotherUser'));

		$this->assertFalse($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testIsDavAuthenticatedWithCorrectDavSession() {
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('MyTestUser'));

		$this->assertTrue($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testValidateUserPassOfAlreadyDAVAuthenticatedUser() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('MyTestUser'));
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->userSession
			->expects($this->exactly(2))
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('MyTestUser'));
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertTrue($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}

	public function testValidateUserPassOfInvalidDAVAuthenticatedUser() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyTestUser'));
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('AnotherUser'));
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertFalse($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}

	public function testValidateUserPassOfInvalidDAVAuthenticatedUserWithValidPassword() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('MyTestUser'));
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->userSession
			->expects($this->exactly(3))
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('AnotherUser'));
		$this->userSession
			->expects($this->once())
			->method('login')
			->with('MyTestUser', 'MyTestPassword')
			->will($this->returnValue(true));
		$this->session
			->expects($this->once())
			->method('set')
			->with('AUTHENTICATED_TO_DAV_BACKEND', 'MyTestUser');
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertTrue($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}

	public function testValidateUserPassWithInvalidPassword() {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(false));
		$this->userSession
			->expects($this->once())
			->method('login')
			->with('MyTestUser', 'MyTestPassword')
			->will($this->returnValue(false));
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertFalse($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}

	public function testGetCurrentUserWithoutBeingLoggedIn() {
		$this->assertSame(null, $this->auth->getCurrentUser());
	}

	public function testGetCurrentUserWithValidDAVLogin() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyTestUser'));
		$this->userSession
			->expects($this->exactly(2))
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('MyTestUser'));

		$this->assertSame('MyTestUser', $this->auth->getCurrentUser());
	}

	public function testGetCurrentUserWithoutAnyDAVLogin() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyTestUser'));
		$this->userSession
			->expects($this->exactly(2))
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue(null));

		$this->assertSame('MyTestUser', $this->auth->getCurrentUser());
	}

	public function testGetCurrentUserWithWrongDAVUser() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyWrongDavUser'));
		$this->userSession
			->expects($this->exactly(2))
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->exactly(3))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue('AnotherUser'));

		$this->assertSame(null, $this->auth->getCurrentUser());
	}

	public function testAuthenticateAlreadyLoggedIn() {
		$server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->session
			->expects($this->once())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->will($this->returnValue(null));
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyWrongDavUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertTrue($this->auth->authenticate($server, 'TestRealm'));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotAuthenticated
	 * @expectedExceptionMessage No basic authentication headers were found
	 */
	public function testAuthenticateNoBasicAuthenticateHeadersProvided() {
		$server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest = $this->getMockBuilder('\Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$server->httpResponse = $this->getMockBuilder('\Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();
		$this->auth->authenticate($server, 'TestRealm');
	}

	public function testAuthenticateValidCredentials() {
		$server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest = $this->getMockBuilder('\Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest
			->expects($this->once())
			->method('getHeader')
			->with('Authorization')
			->will($this->returnValue('basic dXNlcm5hbWU6cGFzc3dvcmQ='));
		$server->httpResponse = $this->getMockBuilder('\Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('login')
			->with('username', 'password')
			->will($this->returnValue(true));
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('MyTestUser'));
		$this->userSession
			->expects($this->exactly(2))
			->method('getUser')
			->will($this->returnValue($user));
		$this->assertTrue($this->auth->authenticate($server, 'TestRealm'));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotAuthenticated
	 * @expectedExceptionMessage Username or password does not match
	 */
	public function testAuthenticateInvalidCredentials() {
		$server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest = $this->getMockBuilder('\Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest
			->expects($this->once())
			->method('getHeader')
			->with('Authorization')
			->will($this->returnValue('basic dXNlcm5hbWU6cGFzc3dvcmQ='));
		$server->httpResponse = $this->getMockBuilder('\Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('login')
			->with('username', 'password')
			->will($this->returnValue(false));
		$this->auth->authenticate($server, 'TestRealm');
	}
}
