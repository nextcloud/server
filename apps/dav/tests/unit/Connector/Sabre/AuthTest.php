<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\User\Session;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
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
	/** @var ISession&MockObject */
	private $session;
	/** @var \OCA\DAV\Connector\Sabre\Auth */
	private $auth;
	/** @var Session&MockObject */
	private $userSession;
	/** @var IRequest&MockObject */
	private $request;
	/** @var Manager&MockObject */
	private $twoFactorManager;
	/** @var IThrottler&MockObject */
	private $throttler;

	protected function setUp(): void {
		parent::setUp();
		$this->session = $this->getMockBuilder(ISession::class)
			->disableOriginalConstructor()->getMock();
		$this->userSession = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()->getMock();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()->getMock();
		$this->twoFactorManager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->throttler = $this->getMockBuilder(IThrottler::class)
			->disableOriginalConstructor()
			->getMock();
		$this->auth = new \OCA\DAV\Connector\Sabre\Auth(
			$this->session,
			$this->userSession,
			$this->request,
			$this->twoFactorManager,
			$this->throttler
		);
	}

	public function testIsDavAuthenticatedWithoutDavSession(): void {
		$this->session
			->expects($this->once())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);

		$this->assertFalse($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testIsDavAuthenticatedWithWrongDavSession(): void {
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('AnotherUser');

		$this->assertFalse($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testIsDavAuthenticatedWithCorrectDavSession(): void {
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('MyTestUser');

		$this->assertTrue($this->invokePrivate($this->auth, 'isDavAuthenticated', ['MyTestUser']));
	}

	public function testValidateUserPassOfAlreadyDAVAuthenticatedUser(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->exactly(1))
			->method('getUID')
			->willReturn('MyTestUser');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->exactly(1))
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

	public function testValidateUserPassOfInvalidDAVAuthenticatedUser(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testValidateUserPassOfInvalidDAVAuthenticatedUserWithValidPassword(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testValidateUserPassWithInvalidPassword(): void {
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


	public function testValidateUserPassWithPasswordLoginForbidden(): void {
		$this->expectException(\OCA\DAV\Connector\Sabre\Exception\PasswordLoginForbidden::class);

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('MyTestUser', 'MyTestPassword')
			->will($this->throwException(new \OC\Authentication\Exceptions\PasswordLoginForbiddenException()));
		$this->session
			->expects($this->once())
			->method('close');

		$this->invokePrivate($this->auth, 'validateUserPass', ['MyTestUser', 'MyTestPassword']);
	}

	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenForNonGet(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
				->disableOriginalConstructor()
				->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
				->disableOriginalConstructor()
				->getMock();
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
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenAndCorrectlyDavAuthenticated(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
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
			->willReturn(false);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('LoggedInUser');
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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


	public function testAuthenticateAlreadyLoggedInWithoutTwoFactorChallengePassed(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		$this->expectExceptionMessage('2FA challenge not passed.');

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
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
			->willReturn(false);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('LoggedInUser');
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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


	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenAndIncorrectlyDavAuthenticated(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		$this->expectExceptionMessage('CSRF check not passed.');

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
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
			->willReturn(false);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn('AnotherUser');
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenForNonGetAndDesktopClient(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
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
			->willReturn(true);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testAuthenticateAlreadyLoggedInWithoutCsrfTokenForGet(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testAuthenticateAlreadyLoggedInWithCsrfTokenForGet(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session
			->expects($this->any())
			->method('get')
			->with('AUTHENTICATED_TO_DAV_BACKEND')
			->willReturn(null);
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testAuthenticateNoBasicAuthenticateHeadersProvided(): void {
		$server = $this->getMockBuilder(Server::class)
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$server->httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->auth->check($server->httpRequest, $server->httpResponse);
		$this->assertEquals([false, 'No \'Authorization: Basic\' header found. Either the client didn\'t send one, or the server is misconfigured'], $response);
	}


	public function testAuthenticateNoBasicAuthenticateHeadersProvidedWithAjax(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
		$this->expectExceptionMessage('Cannot authenticate over ajax calls');

		/** @var \Sabre\HTTP\RequestInterface&MockObject $httpRequest */
		$httpRequest = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var \Sabre\HTTP\ResponseInterface&MockObject $httpResponse */
		$httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(false);
		$httpRequest
			->expects($this->exactly(2))
			->method('getHeader')
			->willReturnMap([
				['X-Requested-With', 'XMLHttpRequest'],
				['Authorization', null],
			]);

		$this->auth->check($httpRequest, $httpResponse);
	}

	public function testAuthenticateWithBasicAuthenticateHeadersProvidedWithAjax(): void {
		// No CSRF
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);

		/** @var \Sabre\HTTP\RequestInterface&MockObject $httpRequest */
		$httpRequest = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var \Sabre\HTTP\ResponseInterface&MockObject $httpResponse */
		$httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$httpRequest
			->expects($this->any())
			->method('getHeader')
			->willReturnMap([
				['X-Requested-With', 'XMLHttpRequest'],
				['Authorization', 'basic dXNlcm5hbWU6cGFzc3dvcmQ='],
			]);

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->willReturn('MyDavUser');
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(false);
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('username', 'password')
			->willReturn(true);
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->auth->check($httpRequest, $httpResponse);
	}

	public function testAuthenticateNoBasicAuthenticateHeadersProvidedWithAjaxButUserIsStillLoggedIn(): void {
		/** @var \Sabre\HTTP\RequestInterface $httpRequest */
		$httpRequest = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var \Sabre\HTTP\ResponseInterface $httpResponse */
		$httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var IUser */
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
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

	public function testAuthenticateValidCredentials(): void {
		$server = $this->getMockBuilder(Server::class)
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest
			->expects($this->once())
			->method('getHeader')
			->with('Authorization')
			->willReturn('basic dXNlcm5hbWU6cGFzc3dvcmQ=');

		$server->httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('username', 'password')
			->willReturn(true);
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('MyTestUser');
		$this->userSession
			->expects($this->exactly(3))
			->method('getUser')
			->willReturn($user);
		$response = $this->auth->check($server->httpRequest, $server->httpResponse);
		$this->assertEquals([true, 'principals/users/MyTestUser'], $response);
	}

	public function testAuthenticateInvalidCredentials(): void {
		$server = $this->getMockBuilder(Server::class)
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$server->httpRequest
			->expects($this->exactly(2))
			->method('getHeader')
			->willReturnMap([
				['Authorization', 'basic dXNlcm5hbWU6cGFzc3dvcmQ='],
				['X-Requested-With', null],
			]);
		$server->httpResponse = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('logClientIn')
			->with('username', 'password')
			->willReturn(false);
		$response = $this->auth->check($server->httpRequest, $server->httpResponse);
		$this->assertEquals([false, 'Username or password was incorrect'], $response);
	}
}
