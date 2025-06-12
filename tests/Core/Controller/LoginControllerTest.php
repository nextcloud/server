<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Controller;

use OC\Authentication\Login\Chain as LoginChain;
use OC\Authentication\Login\LoginData;
use OC\Authentication\Login\LoginResult;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Controller\LoginController;
use OC\User\Session;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ITrustedDomainHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class LoginControllerTest extends TestCase {
	/** @var LoginController */
	private $loginController;

	/** @var IRequest|MockObject */
	private $request;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IConfig|MockObject */
	private $config;

	/** @var ISession|MockObject */
	private $session;

	/** @var Session|MockObject */
	private $userSession;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var Manager|MockObject */
	private $twoFactorManager;

	/** @var Defaults|MockObject */
	private $defaults;

	/** @var IThrottler|MockObject */
	private $throttler;

	/** @var IInitialState|MockObject */
	private $initialState;

	/** @var \OC\Authentication\WebAuthn\Manager|MockObject */
	private $webAuthnManager;

	/** @var IManager|MockObject */
	private $notificationManager;

	/** @var IL10N|MockObject */
	private $l;

	/** @var IAppManager|MockObject */
	private $appManager;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(\OC\User\Manager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(Session::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->twoFactorManager = $this->createMock(Manager::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->webAuthnManager = $this->createMock(\OC\Authentication\WebAuthn\Manager::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});


		$this->request->method('getRemoteAddress')
			->willReturn('1.2.3.4');
		$this->request->method('getHeader')
			->with('Origin')
			->willReturn('domain.example.com');
		$this->throttler->method('getDelay')
			->with(
				$this->equalTo('1.2.3.4'),
				$this->equalTo('')
			)->willReturn(1000);

		$this->loginController = new LoginController(
			'core',
			$this->request,
			$this->userManager,
			$this->config,
			$this->session,
			$this->userSession,
			$this->urlGenerator,
			$this->defaults,
			$this->throttler,
			$this->initialState,
			$this->webAuthnManager,
			$this->notificationManager,
			$this->l,
			$this->appManager,
		);
	}

	public function testLogoutWithoutToken(): void {
		$this->request
			->expects($this->once())
			->method('getCookie')
			->with('nc_token')
			->willReturn(null);
		$this->request
			->method('getServerProtocol')
			->willReturn('https');
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->willReturn(false);
		$this->config
			->expects($this->never())
			->method('deleteUserValue');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.login.showLoginForm')
			->willReturn('/login');

		$expected = new RedirectResponse('/login');
		$expected->addHeader('Clear-Site-Data', '"cache", "storage"');
		$this->assertEquals($expected, $this->loginController->logout());
	}

	public function testLogoutNoClearSiteData(): void {
		$this->request
			->expects($this->once())
			->method('getCookie')
			->with('nc_token')
			->willReturn(null);
		$this->request
			->method('getServerProtocol')
			->willReturn('https');
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->willReturn(true);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.login.showLoginForm')
			->willReturn('/login');

		$expected = new RedirectResponse('/login');
		$this->assertEquals($expected, $this->loginController->logout());
	}

	public function testLogoutWithToken(): void {
		$this->request
			->expects($this->once())
			->method('getCookie')
			->with('nc_token')
			->willReturn('MyLoginToken');
		$this->request
			->method('getServerProtocol')
			->willReturn('https');
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->willReturn(false);
		$user = $this->createMock(IUser::class);
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
		$expected->addHeader('Clear-Site-Data', '"cache", "storage"');
		$this->assertEquals($expected, $this->loginController->logout());
	}

	public function testShowLoginFormForLoggedInUsers(): void {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToDefaultPageUrl')
			->willReturn('/default/foo');

		$expectedResponse = new RedirectResponse('/default/foo');
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('', ''));
	}

	public function testShowLoginFormWithErrorsInSession(): void {
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

		$calls = [
			[
				'loginMessages',
				[
					'MessageArray1',
					'MessageArray2',
					'This community release of Nextcloud is unsupported and push notifications are limited.',
				],
			],
			[
				'loginErrors',
				[
					'ErrorArray1',
					'ErrorArray2',
				],
			],
			[
				'loginUsername',
				'',
			]
		];
		$this->initialState->expects($this->exactly(13))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				if (!empty($expected)) {
					$this->assertEquals($expected, func_get_args());
				}
			});

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'alt_login' => [],
				'pageTitle' => 'Login'
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('', ''));
	}

	public function testShowLoginFormForFlowAuth(): void {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$calls = [
			[], [], [],
			[
				'loginAutocomplete',
				false
			],
			[
				'loginRedirectUrl',
				'login/flow'
			],
		];
		$this->initialState->expects($this->exactly(14))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				if (!empty($expected)) {
					$this->assertEquals($expected, func_get_args());
				}
			});

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'alt_login' => [],
				'pageTitle' => 'Login'
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('', 'login/flow'));
	}

	/**
	 * @return array
	 */
	public static function passwordResetDataProvider(): array {
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
		$expectedResult): void {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->config
			->expects(self::once())
			->method('getSystemValue')
			->willReturnMap([
				['login_form_autocomplete', true, true],
			]);
		$this->config
			->expects(self::once())
			->method('getSystemValueString')
			->willReturnMap([
				['lost_password_link', '', ''],
			]);
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('canChangePassword')
			->willReturn($canChangePassword);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('LdapUser')
			->willReturn($user);
		$calls = [
			[], [],
			[
				'loginUsername',
				'LdapUser'
			],
			[], [], [],
			[
				'loginCanResetPassword',
				$expectedResult
			],
		];
		$this->initialState->expects($this->exactly(13))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				if (!empty($expected)) {
					$this->assertEquals($expected, func_get_args());
				}
			});

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'alt_login' => [],
				'pageTitle' => 'Login'
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('LdapUser', ''));
	}

	public function testShowLoginFormForUserNamed0(): void {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->config
			->expects(self::once())
			->method('getSystemValue')
			->willReturnMap([
				['login_form_autocomplete', true, true],
			]);
		$this->config
			->expects(self::once())
			->method('getSystemValueString')
			->willReturnMap([
				['lost_password_link', '', ''],
			]);
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('canChangePassword')
			->willReturn(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('0')
			->willReturn($user);
		$calls = [
			[], [], [],
			[
				'loginAutocomplete',
				true
			],
			[],
			[
				'loginResetPasswordLink',
				false
			],
			[
				'loginCanResetPassword',
				false
			],
		];
		$this->initialState->expects($this->exactly(13))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				if (!empty($expected)) {
					$this->assertEquals($expected, func_get_args());
				}
			});

		$expectedResponse = new TemplateResponse(
			'core',
			'login',
			[
				'alt_login' => [],
				'pageTitle' => 'Login'
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->loginController->showLoginForm('0', ''));
	}

	public function testLoginWithInvalidCredentials(): void {
		$user = 'MyUserName';
		$password = 'secret';
		$loginPageUrl = '/login?redirect_url=/apps/files';
		$loginChain = $this->createMock(LoginChain::class);
		$trustedDomainHelper = $this->createMock(ITrustedDomainHelper::class);
		$trustedDomainHelper->method('isTrustedUrl')->willReturn(true);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$loginData = new LoginData(
			$this->request,
			$user,
			$password,
			'/apps/files'
		);
		$loginResult = LoginResult::failure($loginData, LoginController::LOGIN_MSG_INVALIDPASSWORD);
		$loginChain->expects($this->once())
			->method('process')
			->with($this->equalTo($loginData))
			->willReturn($loginResult);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.login.showLoginForm', [
				'user' => $user,
				'redirect_url' => '/apps/files',
				'direct' => 1,
			])
			->willReturn($loginPageUrl);
		$expected = new RedirectResponse($loginPageUrl);
		$expected->throttle(['user' => 'MyUserName']);

		$response = $this->loginController->tryLogin($loginChain, $trustedDomainHelper, $user, $password, '/apps/files');

		$this->assertEquals($expected, $response);
	}

	public function testLoginWithValidCredentials(): void {
		$user = 'MyUserName';
		$password = 'secret';
		$loginChain = $this->createMock(LoginChain::class);
		$trustedDomainHelper = $this->createMock(ITrustedDomainHelper::class);
		$trustedDomainHelper->method('isTrustedUrl')->willReturn(true);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$loginData = new LoginData(
			$this->request,
			$user,
			$password
		);
		$loginResult = LoginResult::success($loginData);
		$loginChain->expects($this->once())
			->method('process')
			->with($this->equalTo($loginData))
			->willReturn($loginResult);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToDefaultPageUrl')
			->willReturn('/default/foo');

		$expected = new RedirectResponse('/default/foo');
		$this->assertEquals($expected, $this->loginController->tryLogin($loginChain, $trustedDomainHelper, $user, $password));
	}

	public function testLoginWithoutPassedCsrfCheckAndNotLoggedIn(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('jane');
		$password = 'secret';
		$originalUrl = 'another%20url';
		$loginChain = $this->createMock(LoginChain::class);
		$trustedDomainHelper = $this->createMock(ITrustedDomainHelper::class);
		$trustedDomainHelper->method('isTrustedUrl')->willReturn(true);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->userSession
			->method('isLoggedIn')
			->with()
			->willReturn(false);
		$this->config->expects($this->never())
			->method('deleteUserValue');
		$this->userSession->expects($this->never())
			->method('createRememberMeToken');

		$response = $this->loginController->tryLogin($loginChain, $trustedDomainHelper, 'Jane', $password, $originalUrl);

		$expected = new RedirectResponse('');
		$expected->throttle(['user' => 'Jane']);
		$this->assertEquals($expected, $response);
	}

	public function testLoginWithoutPassedCsrfCheckAndLoggedIn(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('jane');
		$password = 'secret';
		$originalUrl = 'another url';
		$redirectUrl = 'http://localhost/another url';
		$loginChain = $this->createMock(LoginChain::class);
		$trustedDomainHelper = $this->createMock(ITrustedDomainHelper::class);
		$trustedDomainHelper->method('isTrustedUrl')->willReturn(true);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->userSession
			->method('isLoggedIn')
			->with()
			->willReturn(true);
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with(urldecode($originalUrl))
			->willReturn($redirectUrl);
		$this->config->expects($this->never())
			->method('deleteUserValue');
		$this->userSession->expects($this->never())
			->method('createRememberMeToken');
		$this->config
			->method('getSystemValue')
			->with('remember_login_cookie_lifetime')
			->willReturn(1234);

		$response = $this->loginController->tryLogin($loginChain, $trustedDomainHelper, 'Jane', $password, $originalUrl);

		$expected = new RedirectResponse($redirectUrl);
		$this->assertEquals($expected, $response);
	}

	public function testLoginWithValidCredentialsAndRedirectUrl(): void {
		$user = 'MyUserName';
		$password = 'secret';
		$redirectUrl = 'https://next.cloud/apps/mail';
		$loginChain = $this->createMock(LoginChain::class);
		$trustedDomainHelper = $this->createMock(ITrustedDomainHelper::class);
		$trustedDomainHelper->method('isTrustedUrl')->willReturn(true);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$loginData = new LoginData(
			$this->request,
			$user,
			$password,
			'/apps/mail'
		);
		$loginResult = LoginResult::success($loginData);
		$loginChain->expects($this->once())
			->method('process')
			->with($this->equalTo($loginData))
			->willReturn($loginResult);
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('/apps/mail')
			->willReturn($redirectUrl);
		$expected = new RedirectResponse($redirectUrl);

		$response = $this->loginController->tryLogin($loginChain, $trustedDomainHelper, $user, $password, '/apps/mail');

		$this->assertEquals($expected, $response);
	}

	public function testToNotLeakLoginName(): void {
		$loginChain = $this->createMock(LoginChain::class);
		$trustedDomainHelper = $this->createMock(ITrustedDomainHelper::class);
		$trustedDomainHelper->method('isTrustedUrl')->willReturn(true);
		$this->request
			->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$loginPageUrl = '/login?redirect_url=/apps/files';
		$loginData = new LoginData(
			$this->request,
			'john@doe.com',
			'just wrong',
			'/apps/files'
		);
		$loginResult = LoginResult::failure($loginData, LoginController::LOGIN_MSG_INVALIDPASSWORD);
		$loginChain->expects($this->once())
			->method('process')
			->with($this->equalTo($loginData))
			->willReturnCallback(function (LoginData $data) use ($loginResult) {
				$data->setUsername('john');
				return $loginResult;
			});
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.login.showLoginForm', [
				'user' => 'john@doe.com',
				'redirect_url' => '/apps/files',
				'direct' => 1,
			])
			->willReturn($loginPageUrl);
		$expected = new RedirectResponse($loginPageUrl);
		$expected->throttle(['user' => 'john']);

		$response = $this->loginController->tryLogin(
			$loginChain,
			$trustedDomainHelper,
			'john@doe.com',
			'just wrong',
			'/apps/files'
		);

		$this->assertEquals($expected, $response);
	}
}
