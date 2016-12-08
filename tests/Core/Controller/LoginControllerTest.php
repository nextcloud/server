<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace Tests\Core\Controller;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Controller\LoginController;
use OC\Security\Bruteforce\Throttler;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Test\TestCase;

class LoginControllerTest extends TestCase {
	/** @var LoginController */
	private $loginController;
	/** @var IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ISession | \PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IUserSession | \PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var Manager | \PHPUnit_Framework_MockObject_MockObject */
	private $twoFactorManager;
	/** @var Throttler */
	private $throttler;

	public function setUp() {
		parent::setUp();
		$this->request = $this->getMockBuilder('\\OCP\\IRequest')->getMock();
		$this->userManager = $this->getMockBuilder('\\OCP\\IUserManager')->getMock();
		$this->config = $this->getMockBuilder('\\OCP\\IConfig')->getMock();
		$this->session = $this->getMockBuilder('\\OCP\\ISession')->getMock();
		$this->userSession = $this->getMockBuilder('\\OC\\User\\Session')
			->disableOriginalConstructor()
			->getMock();
		$this->urlGenerator = $this->getMockBuilder('\\OCP\\IURLGenerator')->getMock();
		$this->twoFactorManager = $this->getMockBuilder('\OC\Authentication\TwoFactorAuth\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->throttler = $this->getMockBuilder('\OC\Security\Bruteforce\Throttler')
			->disableOriginalConstructor()
			->getMock();

		$this->loginController = new LoginController(
			'core',
			$this->request,
			$this->userManager,
			$this->config,
			$this->session,
			$this->userSession,
			$this->urlGenerator,
			$this->twoFactorManager,
			$this->throttler
		);
	}

	public function testLogoutWithoutToken() {
		$this->request
			->expects($this->once())
			->method('getCookie')
			->with('oc_token')
			->willReturn(null);
		$this->config
			->expects($this->never())
			->method('deleteUserValue');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.login.showLoginForm')
			->willReturn('/login');

		$expected = new RedirectResponse('/login');
		$this->assertEquals($expected, $this->loginController->logout());
	}

	public function testLogoutWithToken() {
		$this->request
			->expects($this->once())
			->method('getCookie')
			->with('oc_token')
			->willReturn('MyLoginToken');
		$user = $this->getMockBuilder('\\OCP\\IUser')->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('JohnDoe');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->config
			->expects($this->once())
			->method('deleteUserValue')
			->with('JohnDoe', 'login_token', 'MyLoginToken');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.login.showLoginForm')
			->willReturn('/login');

		$expected = new RedirectResponse('/login');
		$this->assertEquals($expected, $this->loginController->logout());
	}

	public function testShowLoginFormForLoggedInUsers() {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);

		$expectedResponse = new RedirectResponse(\OC_Util::getDefaultPageUrl());
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('', '', ''));
	}

	public function testShowLoginFormWithErrorsInSession() {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->session
			->expects($this->once())
			->method('get')
			->with('loginMessages')
			->willReturn(
				[
					[
						'ErrorArray1',
						'ErrorArray2',
					],
					[
						'MessageArray1',
						'MessageArray2',
					],
				]
			);

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'ErrorArray1' => true,
				'ErrorArray2' => true,
				'messages' => [
					'MessageArray1',
					'MessageArray2',
				],
				'loginName' => '',
				'user_autofocus' => true,
				'canResetPassword' => true,
				'alt_login' => [],
				'rememberLoginAllowed' => \OC_Util::rememberLoginAllowed(),
				'rememberLoginState' => 0,
				'resetPasswordLink' => null,
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('', '', ''));
	}

	/**
	 * @return array
	 */
	public function passwordResetDataProvider() {
		return [
			[
				true,
				true,
			],
			[
				false,
				false,
			],
		];
	}

	/**
	 * @dataProvider passwordResetDataProvider
	 */
	public function testShowLoginFormWithPasswordResetOption($canChangePassword,
															 $expectedResult) {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('lost_password_link')
			->willReturn(false);
		$user = $this->getMockBuilder('\\OCP\\IUser')->getMock();
		$user
			->expects($this->once())
			->method('canChangePassword')
			->willReturn($canChangePassword);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('LdapUser')
			->willReturn($user);

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'messages' => [],
				'loginName' => 'LdapUser',
				'user_autofocus' => false,
				'canResetPassword' => $expectedResult,
				'alt_login' => [],
				'rememberLoginAllowed' => \OC_Util::rememberLoginAllowed(),
				'rememberLoginState' => 0,
				'resetPasswordLink' => false,
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('LdapUser', '', ''));
	}

	public function testShowLoginFormForUserNamedNull() {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('lost_password_link')
			->willReturn(false);
		$user = $this->getMockBuilder('\\OCP\\IUser')->getMock();
		$user
			->expects($this->once())
			->method('canChangePassword')
			->willReturn(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('0')
			->willReturn($user);

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'messages' => [],
				'loginName' => '0',
				'user_autofocus' => false,
				'canResetPassword' => false,
				'alt_login' => [],
				'rememberLoginAllowed' => \OC_Util::rememberLoginAllowed(),
				'rememberLoginState' => 0,
				'resetPasswordLink' => false,
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('0', '', ''));
	}

	public function testLoginWithInvalidCredentials() {
		$user = 'MyUserName';
		$password = 'secret';
		$loginPageUrl = 'some url';

		$this->request
			->expects($this->exactly(4))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->throttler
			->expects($this->exactly(2))
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);
		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('login', '192.168.0.1', ['user' => 'MyUserName']);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->will($this->returnValue(false));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.login.showLoginForm')
			->will($this->returnValue($loginPageUrl));

		$this->userSession->expects($this->never())
			->method('createSessionToken');

		$expected = new \OCP\AppFramework\Http\RedirectResponse($loginPageUrl);
		$this->assertEquals($expected, $this->loginController->tryLogin($user, $password, ''));
	}

	public function testLoginWithValidCredentials() {
		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('\OCP\IUser')->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('uid'));
		$user->expects($this->any())
			->method('getLastLogin')
			->willReturn(123456);
		$password = 'secret';
		$indexPageUrl = \OC_Util::getDefaultPageUrl();

		$this->request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(200);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->will($this->returnValue($user));
		$this->userSession->expects($this->once())
			->method('login')
			->with($user, $password);
		$this->userSession->expects($this->once())
			->method('createSessionToken')
			->with($this->request, $user->getUID(), $user, $password);
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(false));
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('uid', 'core', 'timezone', 'Europe/Berlin');
		$this->session->expects($this->once())
			->method('set')
			->with('timezone', '1');

		$expected = new \OCP\AppFramework\Http\RedirectResponse($indexPageUrl);
		$this->assertEquals($expected, $this->loginController->tryLogin($user, $password, null, 'Europe/Berlin', '1'));
	}

	public function testLoginWithoutPassedCsrfCheckAndNotLoggedIn() {
		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('\OCP\IUser')->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('jane'));
		$password = 'secret';
		$originalUrl = 'another%20url';

		$this->request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(200);
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->with()
			->will($this->returnValue(false));

		$expected = new \OCP\AppFramework\Http\RedirectResponse(\OC_Util::getDefaultPageUrl());
		$this->assertEquals($expected, $this->loginController->tryLogin('Jane', $password, $originalUrl));
	}

	public function testLoginWithoutPassedCsrfCheckAndLoggedIn() {
		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('\OCP\IUser')->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('jane'));
		$password = 'secret';
		$originalUrl = 'another%20url';
		$redirectUrl = 'http://localhost/another url';

		$this->request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(200);
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->with()
			->will($this->returnValue(true));
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with(urldecode($originalUrl))
			->will($this->returnValue($redirectUrl));

		$expected = new \OCP\AppFramework\Http\RedirectResponse($redirectUrl);
		$this->assertEquals($expected, $this->loginController->tryLogin('Jane', $password, $originalUrl));
	}

	public function testLoginWithValidCredentialsAndRedirectUrl() {
		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('\OCP\IUser')->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('jane'));
		$password = 'secret';
		$originalUrl = 'another%20url';
		$redirectUrl = 'http://localhost/another url';

		$this->request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(200);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with('Jane', $password)
			->will($this->returnValue($user));
		$this->userSession->expects($this->once())
			->method('createSessionToken')
			->with($this->request, $user->getUID(), 'Jane', $password);
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->with()
			->will($this->returnValue(true));
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with(urldecode($originalUrl))
			->will($this->returnValue($redirectUrl));

		$expected = new \OCP\AppFramework\Http\RedirectResponse(urldecode($redirectUrl));
		$this->assertEquals($expected, $this->loginController->tryLogin('Jane', $password, $originalUrl));
	}
	
	public function testLoginWithTwoFactorEnforced() {
		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('\OCP\IUser')->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('john'));
		$password = 'secret';
		$challengeUrl = 'challenge/url';

		$this->request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(200);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->will($this->returnValue($user));
		$this->userSession->expects($this->once())
			->method('login')
			->with('john@doe.com', $password);
		$this->userSession->expects($this->once())
			->method('createSessionToken')
			->with($this->request, $user->getUID(), 'john@doe.com', $password);
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(true));
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with($user);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge')
			->will($this->returnValue($challengeUrl));

		$expected = new RedirectResponse($challengeUrl);
		$this->assertEquals($expected, $this->loginController->tryLogin('john@doe.com', $password, null));
	}

	public function testToNotLeakLoginName() {
		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('\OCP\IUser')->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('john'));

		$this->userManager->expects($this->exactly(2))
			->method('checkPassword')
			->withConsecutive(
				['john@doe.com', 'just wrong'],
				['john', 'just wrong']
				)
			->willReturn(false);
		
		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with('john@doe.com')
			->willReturn([$user]);

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.login.showLoginForm', ['user' => 'john@doe.com'])
			->will($this->returnValue(''));
		$this->request
			->expects($this->exactly(3))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(200);
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('login', '192.168.0.1', ['user' => 'john@doe.com']);

		$expected = new RedirectResponse('');
		$this->assertEquals($expected, $this->loginController->tryLogin('john@doe.com', 'just wrong', null));
	}
}
