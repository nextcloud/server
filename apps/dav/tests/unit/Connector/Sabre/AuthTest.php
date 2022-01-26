<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Authentication\Exceptions\PasswordLoginForbiddenException;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Security\Bruteforce\Throttler;
use OC\User\Session;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\Exception\PasswordLoginForbidden;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

/**
 * Class AuthTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 * @group DB
 */
class AuthTest extends TestCase {
	/** @var ISession */
	private $session;
	/** @var Auth */
	private $auth;
	/** @var Session */
	private $userSession;
	/** @var IRequest */
	private $request;
	/** @var Manager */
	private $twoFactorManager;

	protected function setUp(): void {
		parent::setUp();
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(Session::class);
		$this->request = $this->createMock(IRequest::class);
		$this->twoFactorManager = $this->createMock(Manager::class);
		$throttler = $this->createMock(Throttler::class);
		$this->auth = new Auth(
			$this->session,
			$this->userSession,
			$this->request,
			$this->twoFactorManager,
			$throttler
		);
	}

	public function testIsDavAuthenticatedWithoutDavSession() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);

		$this->assertFalse($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testIsDavAuthenticatedWithWrongDavSession() {
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('AnotherUser');

		$this->assertFalse($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testIsDavAuthenticatedWithCorrectDavSession() {
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('MyTestUser');

		$this->assertTrue($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testValidateUserPassOfAlreadyDAVAuthenticatedUser() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('MyTestUser');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->exactly(2))
			->method('getUser')
			->willReturn($user);
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('MyTestUser');
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertTrue($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}

	public function testValidateUserPassOfInvalidDAVAuthenticatedUser() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('MyTestUser');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('AnotherUser');
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertFalse($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}

	public function testValidateUserPassOfInvalidDAVAuthenticatedUserWithValidPassword() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->exactly(3))
			->method('getUID')
			->willReturn('MyTestUser');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->exactly(3))
			->method('getUser')
			->willReturn($user);
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('AnotherUser');
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('MyTestUser', 'MyTestPassword', $this->request)
			->willReturn(true);
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
			->willReturn(false);
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('MyTestUser', 'MyTestPassword')
			->willReturn(false);
		$this->session
			->expects($this->once())
			->method('close');

		$this->assertFalse($this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']));
	}


	public function testValidateUserPassWithPasswordLoginForbidden() {
		$this->expectException(PasswordLoginForbidden::class);

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('MyTestUser', 'MyTestPassword')
			->will($this->throwException(new PasswordLoginForbiddenException()));
		$this->session
			->expects($this->once())
			->method('close');

		$this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenForNonGet() {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->request
			->expects($this->any())
			->method('getMethod')
			->willReturn('POST');
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('MyWrongDavUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);

		$expectedResponse = [
			false,
			"No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured",
		];
		$response = $this->auth->check($request, $response);
		$this->assertSame($expectedResponse, $response);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenAndCorrectlyDavAuthenticated() {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->request
			->expects($this->any())
			->method('getMethod')
			->willReturn('PROPFIND');
		$this->request
			->expects($this->any())
			->method('isUserAgent')
			->with([
				'/^Mozilla\/5\.0 \([A-Za-z ]+\) (mirall|csyncoC)\/.*$/',
				'/^Mozilla\/5\.0 \(Android\) (ownCloud|Nextcloud)\-android.*$/',
				'/^Mozilla\/5\.0 \(iOS\) (ownCloud|Nextcloud)\-iOS.*$/',
			])
			->willReturn(false);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('LoggedInUser');
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('LoggedInUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->auth->check($request, $response);
	}


	/**
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithoutTwoFactorChallengePassed() {
		$this->expectException(NotAuthenticated::class);
		$this->expectExceptionMessage('2FA challenge not passed.');

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->request
			->expects($this->any())
			->method('getMethod')
			->willReturn('PROPFIND');
		$this->request
			->expects($this->any())
			->method('isUserAgent')
			->with([
				'/^Mozilla\/5\.0 \([A-Za-z ]+\) (mirall|csyncoC)\/.*$/',
				'/^Mozilla\/5\.0 \(Android\) ownCloud\-android.*$/',
				'/^Mozilla\/5\.0 \(iOS\) (ownCloud|Nextcloud)\-iOS.*$/',
			])
			->willReturn(false);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('LoggedInUser');
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('LoggedInUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->with($user)
			->willReturn(true);
		$this->auth->check($request, $response);
	}


	/**
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenAndIncorrectlyDavAuthenticated() {
		$this->expectException(NotAuthenticated::class);
		$this->expectExceptionMessage('CSRF check not passed.');

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->request
			->expects($this->any())
			->method('getMethod')
			->willReturn('PROPFIND');
		$this->request
			->expects($this->any())
			->method('isUserAgent')
			->with([
				'/^Mozilla\/5\.0 \([A-Za-z ]+\) (mirall|csyncoC)\/.*$/',
				'/^Mozilla\/5\.0 \(Android\) (ownCloud|Nextcloud)\-android.*$/',
				'/^Mozilla\/5\.0 \(iOS\) (ownCloud|Nextcloud)\-iOS.*$/',
			])
			->willReturn(false);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('AnotherUser');
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('LoggedInUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->auth->check($request, $response);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenForNonGetAndDesktopClient() {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->request
			->expects($this->any())
			->method('getMethod')
			->willReturn('POST');
		$this->request
			->expects($this->any())
			->method('isUserAgent')
			->with([
				'/^Mozilla\/5\.0 \([A-Za-z ]+\) (mirall|csyncoC)\/.*$/',
				'/^Mozilla\/5\.0 \(Android\) (ownCloud|Nextcloud)\-android.*$/',
				'/^Mozilla\/5\.0 \(iOS\) (ownCloud|Nextcloud)\-iOS.*$/',
			])
			->willReturn(true);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('MyWrongDavUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);

		$this->auth->check($request, $response);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenForGet() {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('MyWrongDavUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->any())
			->method('getMethod')
			->willReturn('GET');

		$response = $this->auth->check($request, $response);
		$this->assertEquals([true, 'principals/users/MyWrongDavUser'], $response);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateAlreadyLoggedInWithCsrfTokenForGet() {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('MyWrongDavUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);

		$response = $this->auth->check($request, $response);
		$this->assertEquals([true, 'principals/users/MyWrongDavUser'], $response);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateNoBasicAuthenticateHeadersProvided() {
		$server = $this->createMock(Server::class);
		$server->httpRequest = $this->createMock(RequestInterface::class);
		$server->httpResponse = $this->createMock(ResponseInterface::class);
		$response = $this->auth->check($server->httpRequest, $server->httpResponse);
		$this->assertEquals([false, 'No \'Authorization: Basic\' header found. Either the client didn\'t send one, or the server is misconfigured'], $response);
	}


	/**
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateNoBasicAuthenticateHeadersProvidedWithAjax() {
		$this->expectException(NotAuthenticated::class);
		$this->expectExceptionMessage('Cannot authenticate over ajax calls');

		/** @var RequestInterface|MockObject $httpRequest */
		$httpRequest = $this->createMock(RequestInterface::class);
		/** @var ResponseInterface|MockObject $httpResponse */
		$httpResponse = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(false);
		$httpRequest
			->expects($this->once())
			->method('getHeader')
			->with('X-Requested-With')
			->willReturn('XMLHttpRequest');
		$this->auth->check($httpRequest, $httpResponse);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateNoBasicAuthenticateHeadersProvidedWithAjaxButUserIsStillLoggedIn() {
		/** @var RequestInterface|MockObject $httpRequest */
		$httpRequest = $this->createMock(RequestInterface::class);
		/** @var ResponseInterface|MockObject $httpResponse */
		$httpResponse = $this->createMock(ResponseInterface::class);
		/** @var IUser */
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('MyTestUser');
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->session
			->expects($this->atLeastOnce())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('MyTestUser');
		$this->request
			->expects($this->once())
			->method('getMethod')
			->willReturn('GET');
		$httpRequest
			->expects($this->atLeastOnce())
			->method('getHeader')
			->with('Authorization')
			->willReturn(null);
		$this->assertEquals(
			[true, 'principals/users/MyTestUser'],
			$this->auth->check($httpRequest, $httpResponse)
		);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateValidCredentials() {
		$server = $this->createMock(Server::class);
		$server->httpRequest = $this->createMock(RequestInterface::class);
		$server->httpRequest
			->expects($this->exactly(2))
			->method('getHeader')
			->withConsecutive(
				['X-Requested-With'],
				['Authorization']
			)
			->willReturnOnConsecutiveCalls(
				null, 'basic dXNlcm5hbWU6cGFzc3dvcmQ='
			);
		$server->httpResponse = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('username', 'password')
			->willReturn(true);
		$user = $this->createMock(IUser::class);
		$user->expects($this->exactly(3))
			->method('getUID')
			->willReturn('MyTestUser');
		$this->userSession
			->expects($this->exactly(4))
			->method('getUser')
			->willReturn($user);
		$response = $this->auth->check($server->httpRequest, $server->httpResponse);
		$this->assertEquals([true, 'principals/users/MyTestUser'], $response);
	}

	/**
	 * @throws NotAuthenticated
	 * @throws ServiceUnavailable
	 */
	public function testAuthenticateInvalidCredentials() {
		/** @var Server|MockObject $server */
		$server = $this->createMock(Server::class);
		/** @var RequestInterface|MockObject $httpRequest */
		$httpRequest = $this->createMock(RequestInterface::class);
		$server->httpRequest = $httpRequest;
		$server->httpRequest
			->expects($this->exactly(2))
			->method('getHeader')
			->withConsecutive(
				['X-Requested-With'],
				['Authorization']
			)
			->willReturnOnConsecutiveCalls(
				null, 'basic dXNlcm5hbWU6cGFzc3dvcmQ='
			);
		$server->httpResponse = $this->createMock(ResponseInterface::class);
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('username', 'password')
			->willReturn(false);
		$response = $this->auth->check($server->httpRequest, $server->httpResponse);
		$this->assertEquals([false, 'Username or password was incorrect'], $response);
	}
}
