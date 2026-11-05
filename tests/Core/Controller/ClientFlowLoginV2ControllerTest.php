<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Core\Controller\ClientFlowLoginV2Controller;
use OC\Core\Data\LoginFlowV2Credentials;
use OC\Core\Data\LoginFlowV2Tokens;
use OC\Core\Db\LoginFlowV2;
use OC\Core\Exception\LoginFlowV2ClientForbiddenException;
use OC\Core\Exception\LoginFlowV2NotFoundException;
use OC\Core\Service\LoginFlowV2Service;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ClientFlowLoginV2ControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private LoginFlowV2Service&MockObject $loginFlowV2Service;
	private IURLGenerator&MockObject $urlGenerator;
	private ISession&MockObject $session;
	private IUserSession&MockObject $userSession;
	private ISecureRandom&MockObject $random;
	private IInitialState&MockObject $initialState;
	private IL10N&MockObject $l;
	/** @var ClientFlowLoginV2Controller */
	private $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getServerProtocol')->willReturn('https');
		$this->request->method('getServerHost')->willReturn('server');
		$this->request->method('getRequestUri')->willReturn('/index.php/login/v2/flow');
		$this->loginFlowV2Service = $this->createMock(LoginFlowV2Service::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->controller = new ClientFlowLoginV2Controller(
			'core',
			$this->request,
			$this->loginFlowV2Service,
			$this->urlGenerator,
			$this->session,
			$this->userSession,
			$this->random,
			'user',
			$this->l,
			$this->initialState,
		);
	}

	public function testPollInvalid(): void {
		$this->loginFlowV2Service->method('poll')
			->with('token')
			->willThrowException(new LoginFlowV2NotFoundException());

		$result = $this->controller->poll('token');

		$this->assertSame([], $result->getData());
		$this->assertSame(Http::STATUS_NOT_FOUND, $result->getStatus());
	}

	public function testPollValid(): void {
		$creds = new LoginFlowV2Credentials('server', 'login', 'pass');
		$this->loginFlowV2Service->method('poll')
			->with('token')
			->willReturn($creds);

		$result = $this->controller->poll('token');

		$this->assertSame($creds->jsonSerialize(), $result->getData());
		$this->assertSame(Http::STATUS_OK, $result->getStatus());
	}

	public function testLandingInvalid(): void {
		$this->session->expects($this->never())
			->method($this->anything());

		$this->loginFlowV2Service->method('startLoginFlow')
			->with('token')
			->willReturn(false);

		$result = $this->controller->landing('token');

		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
		$this->assertInstanceOf(StandaloneTemplateResponse::class, $result);
	}

	public function testLandingValid(): void {
		$this->session->expects($this->once())
			->method('set')
			->with('client.flow.v2.login.token', 'token');

		$this->loginFlowV2Service->method('startLoginFlow')
			->with('token')
			->willReturn(true);

		$this->urlGenerator->method('linkToRouteAbsolute')
			->with('core.ClientFlowLoginV2.showAuthPickerPage')
			->willReturn('https://server/path');

		$result = $this->controller->landing('token');

		$this->assertInstanceOf(RedirectResponse::class, $result);
		$this->assertSame(Http::STATUS_SEE_OTHER, $result->getStatus());
		$this->assertSame('https://server/path', $result->getRedirectURL());
	}

	public function testShowAuthPickerNoLoginToken(): void {
		$this->session->method('get')
			->willReturn(null);

		$result = $this->controller->showAuthPickerPage();

		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testShowAuthPickerInvalidLoginToken(): void {
		$this->session->method('get')
			->with('client.flow.v2.login.token')
			->willReturn('loginToken');

		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willThrowException(new LoginFlowV2NotFoundException());

		$result = $this->controller->showAuthPickerPage();

		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testShowAuthPickerForbiddenUserClient() {
		$this->session->method('get')
			->with('client.flow.v2.login.token')
			->willReturn('loginToken');

		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willThrowException(new LoginFlowV2ClientForbiddenException());

		$result = $this->controller->showAuthPickerPage();

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $result);
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
		$this->assertSame('Please use original client', $result->getParams()['message']);
	}

	public function testShowAuthPickerValidLoginToken(): void {
		$this->session->method('get')
			->willReturnMap([[ClientFlowLoginV2Controller::TOKEN_NAME, 'loginToken']]);

		$flow = new LoginFlowV2();
		$flow->setClientIp('203.0.113.1');
		$flow->setTimestamp(1700000000);
		$this->request->method('getRemoteAddress')->willReturn('198.51.100.2');
		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willReturn($flow);

		$this->random->method('generate')
			->with(64, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS)
			->willReturn('random');
		$this->session->expects($this->once())
			->method('set')
			->with('client.flow.v2.state.token', 'random');

		$providedState = [];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value) use (&$providedState): void {
				$providedState[$key] = $value;
			});

		$this->controller->showAuthPickerPage();

		foreach ([
			'clientIp' => '203.0.113.1',
			'browserIp' => '198.51.100.2',
			'createdAt' => 1700000000,
		] as $key => $value) {
			$this->assertSame($value, $providedState['loginFlowContext'][$key]);
		}
		$this->assertSame('auth-v2', $providedState['loginFlowState']);
		$this->assertSame('https://server', $providedState['loginFlowContext']['serverUrl']);
		$this->assertNull($providedState['loginFlowContext']['userId']);
		$this->assertSame([
			'stateToken' => 'random',
			'direct' => false,
			'loginRedirectUrl' => '',
			'appTokenUrl' => '',
		], $providedState['loginFlowAuth']);
	}

	public function testGrantPageNoStateToken(): void {
		$result = $this->controller->grantPage(null);

		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testSignedInUserGoesDirectlyToApproval(): void {
		$this->session->method('get')->with(ClientFlowLoginV2Controller::TOKEN_NAME)->willReturn('loginToken');
		$this->loginFlowV2Service->method('getByLoginToken')->with('loginToken')->willReturn(new LoginFlowV2());
		$this->userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->random->method('generate')->willReturn('fresh-state');
		$this->session->expects($this->once())->method('set')->with(ClientFlowLoginV2Controller::STATE_NAME, 'fresh-state');
		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('core.ClientFlowLoginV2.grantPage', ['stateToken' => 'fresh-state', 'user' => 'account-hint', 'direct' => 1])
			->willReturn('https://server/login/v2/grant?stateToken=fresh-state');
		$this->initialState->expects($this->never())->method('provideInitialState');
		$this->loginFlowV2Service->expects($this->never())->method('flowDone');

		$response = $this->controller->showAuthPickerPage('account-hint', 1);

		$this->assertInstanceOf(RedirectResponse::class, $response);
		$this->assertSame('https://server/login/v2/grant?stateToken=fresh-state', $response->getRedirectURL());
	}

	public function testGrantPageInvalidStateToken(): void {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				return null;
			});

		$result = $this->controller->grantPage('stateToken');
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testGrantPageInvalidLoginToken(): void {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				if ($name === 'client.flow.v2.state.token') {
					return 'stateToken';
				}
				if ($name === 'client.flow.v2.login.token') {
					return 'loginToken';
				}
				return null;
			});

		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willThrowException(new LoginFlowV2NotFoundException());

		$result = $this->controller->grantPage('stateToken');
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testGrantPageForbiddenUserClient() {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				if ($name === 'client.flow.v2.state.token') {
					return 'stateToken';
				}
				if ($name === 'client.flow.v2.login.token') {
					return 'loginToken';
				}
				return null;
			});

		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willThrowException(new LoginFlowV2ClientForbiddenException());

		$result = $this->controller->grantPage('stateToken');

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $result);
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
		$this->assertSame('Please use original client', $result->getParams()['message']);
	}

	public function testGrantPageValid(): void {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				if ($name === 'client.flow.v2.state.token') {
					return 'stateToken';
				}
				if ($name === 'client.flow.v2.login.token') {
					return 'loginToken';
				}
				return null;
			});

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('uid');
		$user->method('getDisplayName')
			->willReturn('display name');
		$this->userSession->method('getUser')
			->willReturn($user);

		$flow = new LoginFlowV2();
		$flow->setClientIp('203.0.113.1');
		$flow->setTimestamp(1700000000);
		$this->request->method('getRemoteAddress')->willReturn('198.51.100.2');
		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willReturn($flow);

		$providedState = [];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value) use (&$providedState): void {
				$providedState[$key] = $value;
			});

		$result = $this->controller->grantPage('stateToken');
		$this->assertSame(Http::STATUS_OK, $result->getStatus());
		foreach ([
			'clientIp' => '203.0.113.1',
			'browserIp' => '198.51.100.2',
			'createdAt' => 1700000000,
		] as $key => $value) {
			$this->assertSame($value, $providedState['loginFlowContext'][$key]);
		}
		$this->assertSame('grant-v2', $providedState['loginFlowState']);
		$this->assertSame([
			'actionUrl' => '',
			'stateToken' => 'stateToken',
			'direct' => false,
		], $providedState['loginFlowGrant']);
		$this->assertSame('uid', $providedState['loginFlowContext']['userId']);
		$this->assertSame('display name', $providedState['loginFlowContext']['userDisplayName']);
	}

	public function testInitRecordsClientAddress(): void {
		$this->request->method('getHeader')->with('user-agent')->willReturn('Desktop client');
		$this->request->method('getRemoteAddress')->willReturn('2001:db8::1');
		$this->loginFlowV2Service->expects($this->once())
			->method('createTokens')
			->with('Desktop client', '2001:db8::1')
			->willReturn(new LoginFlowV2Tokens('login-token', 'poll-token'));

		$response = $this->controller->init();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('poll-token', $response->getData()['poll']['token']);
	}

	public function testGenerateAppPasswordInvalidStateToken(): void {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				return null;
			});

		$result = $this->controller->generateAppPassword('stateToken');
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testGenerateAppPassworInvalidLoginToken(): void {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				if ($name === 'client.flow.v2.state.token') {
					return 'stateToken';
				}
				if ($name === 'client.flow.v2.login.token') {
					return 'loginToken';
				}
				return null;
			});

		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willThrowException(new LoginFlowV2NotFoundException());

		$result = $this->controller->generateAppPassword('stateToken');
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
	}

	public function testGenerateAppPasswordForbiddenUserClient() {
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				if ($name === 'client.flow.v2.state.token') {
					return 'stateToken';
				}
				if ($name === 'client.flow.v2.login.token') {
					return 'loginToken';
				}
				return null;
			});

		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willThrowException(new LoginFlowV2ClientForbiddenException());

		$result = $this->controller->generateAppPassword('stateToken');

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $result);
		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
		$this->assertSame('Please use original client', $result->getParams()['message']);
	}

	public function testGenerateAppPassworValid(): void {
		$providedState = [];
		$this->initialState->method('provideInitialState')->willReturnCallback(function (string $key, mixed $value) use (&$providedState): void {
			$providedState[$key] = $value;
		});
		$this->urlGenerator->method('linkToRouteAbsolute')
			->with('settings.PersonalSettings.index', ['section' => 'security'])->willReturn('https://server/settings/user/security');
		$this->session->method('get')
			->willReturnCallback(function ($name) {
				if ($name === 'client.flow.v2.state.token') {
					return 'stateToken';
				}
				if ($name === 'client.flow.v2.login.token') {
					return 'loginToken';
				}
				return null;
			});

		$flow = new LoginFlowV2();
		$flow->setClientIp('203.0.113.1');
		$this->request->method('getRemoteAddress')->willReturn('198.51.100.2');
		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willReturn($flow);

		$clearedState = false;
		$clearedLogin = false;
		$this->session->method('remove')
			->willReturnCallback(function ($name) use (&$clearedLogin, &$clearedState): void {
				if ($name === 'client.flow.v2.state.token') {
					$clearedState = true;
				}
				if ($name === 'client.flow.v2.login.token') {
					$clearedLogin = true;
				}
			});

		$this->session->method('getId')
			->willReturn('sessionId');

		$this->loginFlowV2Service->expects($this->once())
			->method('flowDone')
			->with(
				'loginToken',
				'sessionId',
				'https://server',
				'user'
			)->willReturn(true);

		$result = $this->controller->generateAppPassword('stateToken');
		$this->assertSame(Http::STATUS_OK, $result->getStatus());

		$this->assertTrue($clearedLogin);
		$this->assertTrue($clearedState);
		$this->assertSame('done', $providedState['loginFlowState']);
		$this->assertSame(['securitySettingsUrl' => 'https://server/settings/user/security'], $providedState['loginFlowDone']);
	}

	public static function completionResultProvider(): array {
		return [[true], [false]];
	}

	#[DataProvider('completionResultProvider')]
	public function testAppPasswordCompletionGuidance(bool $completed): void {
		$this->session->method('get')->willReturnMap([
			[ClientFlowLoginV2Controller::STATE_NAME, 'stateToken'],
			[ClientFlowLoginV2Controller::TOKEN_NAME, 'loginToken'],
		]);
		$flow = new LoginFlowV2();
		$flow->setClientIp('203.0.113.1');
		$this->request->method('getRemoteAddress')->willReturn('198.51.100.2');
		$this->loginFlowV2Service->method('getByLoginToken')->willReturn($flow);
		$token = $this->createMock(IToken::class);
		$token->method('getLoginName')->willReturn('user');
		$provider = $this->createMock(IProvider::class);
		$provider->method('getToken')->with('existing-password')->willReturn($token);
		$this->overwriteService(IProvider::class, $provider);
		$this->loginFlowV2Service->expects($this->once())->method('flowDoneWithAppPassword')
			->with('loginToken', 'https://server', 'user', 'existing-password')->willReturn($completed);
		$this->urlGenerator->method('linkToRouteAbsolute')
			->with('settings.PersonalSettings.index', ['section' => 'security'])->willReturn('https://server/settings/user/security');
		$providedState = [];
		$this->initialState->method('provideInitialState')->willReturnCallback(function (string $key, mixed $value) use (&$providedState): void {
			$providedState[$key] = $value;
		});

		$result = $this->controller->apptokenRedirect('stateToken', 'user', 'existing-password');

		$this->assertSame($completed ? Http::STATUS_OK : Http::STATUS_FORBIDDEN, $result->getStatus());
		$this->assertSame($completed ? [
			'loginFlowState' => 'done',
			'loginFlowDone' => ['securitySettingsUrl' => 'https://server/settings/user/security'],
		] : [], $providedState);
	}

	public function testDeclineMissingStateToken(): void {
		$this->loginFlowV2Service->expects($this->never())->method('decline');
		$this->session->expects($this->never())->method('remove');

		$this->assertSame(Http::STATUS_FORBIDDEN, $this->controller->decline(null)->getStatus());
	}

	public function testDeclineInvalidStateToken(): void {
		$this->session->method('get')->willReturn('another-state-token');
		$this->loginFlowV2Service->expects($this->never())->method('getByLoginToken');
		$this->loginFlowV2Service->expects($this->never())->method('decline');

		$this->assertSame(Http::STATUS_FORBIDDEN, $this->controller->decline('stateToken')->getStatus());
	}

	public static function declineUnavailableProvider(): array {
		return [
			[new LoginFlowV2NotFoundException(), Http::STATUS_GONE],
			[new LoginFlowV2ClientForbiddenException(), Http::STATUS_FORBIDDEN],
		];
	}

	#[DataProvider('declineUnavailableProvider')]
	public function testDeclineUnavailableRequest(\Exception $exception, int $status): void {
		$this->session->method('get')->willReturnMap([
			[ClientFlowLoginV2Controller::STATE_NAME, 'stateToken'],
			[ClientFlowLoginV2Controller::TOKEN_NAME, 'loginToken'],
		]);
		$this->loginFlowV2Service->method('getByLoginToken')->willThrowException($exception);
		$this->loginFlowV2Service->expects($this->never())->method('decline');
		$this->session->expects($this->never())->method('remove');

		$this->assertSame($status, $this->controller->decline('stateToken')->getStatus());
	}

	public static function declineResultProvider(): array {
		return [[true, Http::STATUS_OK], [false, Http::STATUS_CONFLICT]];
	}

	#[DataProvider('declineResultProvider')]
	public function testDeclinePendingRequest(bool $cancelled, int $status): void {
		$this->session->method('get')->willReturnMap([
			[ClientFlowLoginV2Controller::STATE_NAME, 'stateToken'],
			[ClientFlowLoginV2Controller::TOKEN_NAME, 'loginToken'],
		]);
		$this->loginFlowV2Service->method('getByLoginToken')->with('loginToken')->willReturn(new LoginFlowV2());
		$this->loginFlowV2Service->expects($this->once())->method('decline')->with('loginToken')->willReturn($cancelled);
		$removed = [];
		$this->session->method('remove')->willReturnCallback(function (string $key) use (&$removed): void {
			$removed[] = $key;
		});

		$this->assertSame($status, $this->controller->decline('stateToken')->getStatus());
		$this->assertSame($cancelled ? [ClientFlowLoginV2Controller::TOKEN_NAME, ClientFlowLoginV2Controller::STATE_NAME] : [], $removed);
	}
}
