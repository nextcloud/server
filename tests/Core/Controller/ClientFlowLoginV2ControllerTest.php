<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Controller;

use OC\Core\Controller\ClientFlowLoginV2Controller;
use OC\Core\Data\LoginFlowV2Credentials;
use OC\Core\Db\LoginFlowV2;
use OC\Core\Exception\LoginFlowV2ClientForbiddenException;
use OC\Core\Exception\LoginFlowV2NotFoundException;
use OC\Core\Service\LoginFlowV2Service;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ClientFlowLoginV2ControllerTest extends TestCase {
	/** @var IRequest|MockObject */
	private $request;
	/** @var LoginFlowV2Service|MockObject */
	private $loginFlowV2Service;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var ISession|MockObject */
	private $session;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var ISecureRandom|MockObject */
	private $random;
	/** @var Defaults|MockObject */
	private $defaults;
	/** @var IL10N|MockObject */
	private $l;
	/** @var ClientFlowLoginV2Controller */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->loginFlowV2Service = $this->createMock(LoginFlowV2Service::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->defaults = $this->createMock(Defaults::class);
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
			$this->defaults,
			'user',
			$this->l
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
			->with('client.flow.v2.login.token')
			->willReturn('loginToken');

		$flow = new LoginFlowV2();
		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willReturn($flow);

		$this->random->method('generate')
			->with(64, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS)
			->willReturn('random');
		$this->session->expects($this->once())
			->method('set')
			->with('client.flow.v2.state.token', 'random');

		$this->controller->showAuthPickerPage();
	}

	public function testGrantPageNoStateToken(): void {
		$result = $this->controller->grantPage(null);

		$this->assertSame(Http::STATUS_FORBIDDEN, $result->getStatus());
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
		$this->loginFlowV2Service->method('getByLoginToken')
			->with('loginToken')
			->willReturn($flow);

		$result = $this->controller->grantPage('stateToken');
		$this->assertSame(Http::STATUS_OK, $result->getStatus());
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

		$this->request->method('getServerProtocol')
			->willReturn('https');
		$this->request->method('getRequestUri')
			->willReturn('/login/v2');
		$this->request->method('getServerHost')
			->willReturn('server');

		$result = $this->controller->generateAppPassword('stateToken');
		$this->assertSame(Http::STATUS_OK, $result->getStatus());

		$this->assertTrue($clearedLogin);
		$this->assertTrue($clearedState);
	}
}
