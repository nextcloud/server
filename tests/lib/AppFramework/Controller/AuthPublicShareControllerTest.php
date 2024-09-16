<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Controller;

use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

class AuthPublicShareControllerTest extends \Test\TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	/** @var AuthPublicShareController|\PHPUnit\Framework\MockObject\MockObject */
	private $controller;


	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->controller = $this->getMockBuilder(AuthPublicShareController::class)
			->setConstructorArgs([
				'app',
				$this->request,
				$this->session,
				$this->urlGenerator
			])->setMethods([
				'authFailed',
				'getPasswordHash',
				'isAuthenticated',
				'isPasswordProtected',
				'isValidToken',
				'showShare',
				'verifyPassword',
				'validateIdentity',
				'generatePassword'
			])->getMock();
	}

	public function testShowAuthenticate(): void {
		$expects = new TemplateResponse('core', 'publicshareauth', [], 'guest');

		$this->assertEquals($expects, $this->controller->showAuthenticate());
	}

	public function testAuthenticateAuthenticated(): void {
		$this->controller->method('isAuthenticated')
			->willReturn(true);

		$this->controller->setToken('myToken');

		$this->session->method('get')
			->willReturnMap(['public_link_authenticate_redirect', ['foo' => 'bar']]);

		$this->urlGenerator->method('linkToRoute')
			->willReturn('myLink!');

		$result = $this->controller->authenticate('password');
		$this->assertInstanceOf(RedirectResponse::class, $result);
		$this->assertSame('myLink!', $result->getRedirectURL());
	}

	public function testAuthenticateInvalidPassword(): void {
		$this->controller->setToken('token');
		$this->controller->method('isPasswordProtected')
			->willReturn(true);

		$this->controller->method('verifyPassword')
			->with('password')
			->willReturn(false);

		$this->controller->expects($this->once())
			->method('authFailed');

		$expects = new TemplateResponse('core', 'publicshareauth', ['wrongpw' => true], 'guest');
		$expects->throttle();

		$result = $this->controller->authenticate('password');

		$this->assertEquals($expects, $result);
	}

	public function testAuthenticateValidPassword(): void {
		$this->controller->setToken('token');
		$this->controller->method('isPasswordProtected')
			->willReturn(true);
		$this->controller->method('verifyPassword')
			->with('password')
			->willReturn(true);
		$this->controller->method('getPasswordHash')
			->willReturn('hash');

		$this->session->expects($this->once())
			->method('regenerateId');
		$this->session->method('get')
			->willReturnMap(['public_link_authenticate_redirect', ['foo' => 'bar']]);

		$tokenSet = false;
		$hashSet = false;
		$this->session
			->method('set')
			->willReturnCallback(function ($key, $value) use (&$tokenSet, &$hashSet) {
				if ($key === 'public_link_authenticated_token' && $value === 'token') {
					$tokenSet = true;
					return true;
				}
				if ($key === 'public_link_authenticated_password_hash' && $value === 'hash') {
					$hashSet = true;
					return true;
				}
				return false;
			});

		$this->urlGenerator->method('linkToRoute')
			->willReturn('myLink!');

		$result = $this->controller->authenticate('password');
		$this->assertInstanceOf(RedirectResponse::class, $result);
		$this->assertSame('myLink!', $result->getRedirectURL());
		$this->assertTrue($tokenSet);
		$this->assertTrue($hashSet);
	}
}
