<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Core\Middleware;

use OC\AppFramework\Http\Request;
use OC\Authentication\Exceptions\TwoFactorAuthRequiredException;
use OC\Authentication\Exceptions\UserAlreadyLoggedInException;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Authentication\TwoFactorAuth\ProviderSet;
use OC\Core\Controller\TwoFactorChallengeController;
use OC\Core\Middleware\TwoFactorMiddleware;
use OC\User\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TwoFactorMiddlewareTest extends TestCase {
	/** @var Manager|MockObject */
	private $twoFactorManager;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var ISession|MockObject */
	private $session;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IControllerMethodReflector|MockObject */
	private $reflector;

	/** @var IRequest|MockObject */
	private $request;

	/** @var TwoFactorMiddleware */
	private $middleware;

	/** @var Controller */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->twoFactorManager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->createMock(ISession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->reflector = $this->createMock(IControllerMethodReflector::class);
		$this->request = new Request(
			[
				'server' => [
					'REQUEST_URI' => 'test/url'
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);

		$this->middleware = new TwoFactorMiddleware($this->twoFactorManager, $this->userSession, $this->session, $this->urlGenerator, $this->reflector, $this->request);
		$this->controller = $this->createMock(Controller::class);
	}

	public function testBeforeControllerNotLoggedIn(): void {
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		$this->userSession->expects($this->never())
			->method('getUser');

		$this->middleware->beforeController($this->controller, 'index');
	}

	public function testBeforeSetupController(): void {
		$user = $this->createMock(IUser::class);
		$controller = $this->createMock(ALoginSetupController::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->willReturn(true);
		$this->userSession->expects($this->never())
			->method('isLoggedIn');

		$this->middleware->beforeController($controller, 'create');
	}

	public function testBeforeControllerNoTwoFactorCheckNeeded(): void {
		$user = $this->createMock(IUser::class);

		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->willReturn(false);

		$this->middleware->beforeController($this->controller, 'index');
	}


	public function testBeforeControllerTwoFactorAuthRequired(): void {
		$this->expectException(TwoFactorAuthRequiredException::class);

		$user = $this->createMock(IUser::class);

		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->with($user)
			->willReturn(true);

		$this->middleware->beforeController($this->controller, 'index');
	}


	public function testBeforeControllerUserAlreadyLoggedIn(): void {
		$this->expectException(UserAlreadyLoggedInException::class);

		$user = $this->createMock(IUser::class);

		$this->reflector
			->method('hasAnnotation')
			->willReturn(false);
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->with($user)
			->willReturn(false);

		$twoFactorChallengeController = $this->getMockBuilder(TwoFactorChallengeController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($twoFactorChallengeController, 'index');
	}

	public function testAfterExceptionTwoFactorAuthRequired(): void {
		$ex = new TwoFactorAuthRequiredException();

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge')
			->willReturn('test/url');
		$expected = new RedirectResponse('test/url');

		$this->assertEquals($expected, $this->middleware->afterException($this->controller, 'index', $ex));
	}

	public function testAfterException(): void {
		$ex = new UserAlreadyLoggedInException();

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->willReturn('redirect/url');
		$expected = new RedirectResponse('redirect/url');

		$this->assertEquals($expected, $this->middleware->afterException($this->controller, 'index', $ex));
	}

	public function testRequires2FASetupDoneAnnotated(): void {
		$user = $this->createMock(IUser::class);

		$this->reflector
			->method('hasAnnotation')
			->willReturnCallback(function (string $annotation) {
				return $annotation === 'TwoFactorSetUpDoneRequired';
			});
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->with($user)
			->willReturn(false);

		$this->expectException(UserAlreadyLoggedInException::class);

		$twoFactorChallengeController = $this->getMockBuilder(TwoFactorChallengeController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($twoFactorChallengeController, 'index');
	}

	public function dataRequires2FASetupDone() {
		$provider = $this->createMock(IProvider::class);
		$provider->method('getId')
			->willReturn('2FAftw');

		return [
			[[], false, false],
			[[],  true,  true],
			[[$provider], false, true],
			[[$provider], true,  true],
		];
	}

	/**
	 * @dataProvider dataRequires2FASetupDone
	 */
	public function testRequires2FASetupDone(array $providers, bool $missingProviders, bool $expectEception): void {
		$user = $this->createMock(IUser::class);

		$this->reflector
			->method('hasAnnotation')
			->willReturn(false);
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$providerSet = new ProviderSet($providers, $missingProviders);
		$this->twoFactorManager->method('getProviderSet')
			->with($user)
			->willReturn($providerSet);
		$this->userSession
			->method('isLoggedIn')
			->willReturn(false);

		if ($expectEception) {
			$this->expectException(TwoFactorAuthRequiredException::class);
		} else {
			// hack to make phpunit shut up. Since we don't expect an exception here...
			$this->assertTrue(true);
		}

		$twoFactorChallengeController = $this->getMockBuilder(TwoFactorChallengeController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($twoFactorChallengeController, 'index');
	}
}
