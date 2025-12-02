<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Core\Middleware;

use OC\AppFramework\Http\Attributes\TwoFactorSetUpDoneRequired;
use OC\AppFramework\Http\Request;
use OC\Authentication\Exceptions\TwoFactorAuthRequiredException;
use OC\Authentication\Exceptions\UserAlreadyLoggedInException;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Authentication\TwoFactorAuth\ProviderSet;
use OC\Core\Controller\TwoFactorChallengeController;
use OC\Core\Middleware\TwoFactorMiddleware;
use OC\User\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoTwoFactorRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class HasTwoFactorAnnotationController extends Controller {
	#[NoTwoFactorRequired]
	public function index(): Response {
		return new Response();
	}
}

class LoginSetupController extends ALoginSetupController {
	public function index(): Response {
		return new Response();
	}
}

class NoTwoFactorAnnotationController extends Controller {
	public function index(): Response {
		return new Response();
	}
}

class NoTwoFactorChallengeAnnotationController extends TwoFactorChallengeController {
	public function index(): Response {
		return new Response();
	}
}

class HasTwoFactorSetUpDoneAnnotationController extends TwoFactorChallengeController {
	#[TwoFactorSetUpDoneRequired]
	public function index(): Response {
		return new Response();
	}
}

class TwoFactorMiddlewareTest extends TestCase {
	private Manager&MockObject $twoFactorManager;
	private IUserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private IURLGenerator&MockObject $urlGenerator;
	private IControllerMethodReflector&MockObject $reflector;
	private IRequest $request;
	private TwoFactorMiddleware $middleware;
	private LoggerInterface&MockObject $logger;

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
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = new Request(
			[
				'server' => [
					'REQUEST_URI' => 'test/url'
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);

		$this->middleware = new TwoFactorMiddleware($this->twoFactorManager, $this->userSession, $this->session, $this->urlGenerator, $this->reflector, $this->request, $this->logger);
	}

	public function testBeforeControllerNotLoggedIn(): void {
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		$this->userSession->expects($this->never())
			->method('getUser');

		$controller = $this->getMockBuilder(NoTwoFactorAnnotationController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($controller, 'index');
	}

	public function testBeforeSetupController(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->willReturn(true);
		$this->userSession->expects($this->never())
			->method('isLoggedIn');

		$this->middleware->beforeController(new LoginSetupController('foo', $this->request), 'index');
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

		$controller = $this->getMockBuilder(NoTwoFactorAnnotationController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($controller, 'index');
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

		$controller = $this->getMockBuilder(NoTwoFactorAnnotationController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($controller, 'index');
	}


	public function testBeforeControllerUserAlreadyLoggedIn(): void {
		$this->expectException(UserAlreadyLoggedInException::class);

		$user = $this->createMock(IUser::class);

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

		$twoFactorChallengeController = $this->getMockBuilder(NoTwoFactorChallengeAnnotationController::class)
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

		$controller = new HasTwoFactorAnnotationController('foo', $this->request);
		$this->assertEquals($expected, $this->middleware->afterException($controller, 'index', $ex));
	}

	public function testAfterException(): void {
		$ex = new UserAlreadyLoggedInException();

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->willReturn('redirect/url');
		$expected = new RedirectResponse('redirect/url');

		$controller = new HasTwoFactorAnnotationController('foo', $this->request);
		$this->assertEquals($expected, $this->middleware->afterException($controller, 'index', $ex));
	}

	public function testRequires2FASetupDoneAnnotated(): void {
		$user = $this->createMock(IUser::class);

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

		$controller = $this->getMockBuilder(HasTwoFactorSetUpDoneAnnotationController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($controller, 'index');
	}

	public static function dataRequires2FASetupDone(): array {
		return [
			[false, false, false],
			[false,  true,  true],
			[true, false, true],
			[true, true,  true],
		];
	}

	#[DataProvider('dataRequires2FASetupDone')]
	public function testRequires2FASetupDone(bool $hasProvider, bool $missingProviders, bool $expectEception): void {
		if ($hasProvider) {
			$provider = $this->createMock(IProvider::class);
			$provider->method('getId')
				->willReturn('2FAftw');
			$providers = [$provider];
		} else {
			$providers = [];
		}


		$user = $this->createMock(IUser::class);

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

		$controller = $this->getMockBuilder(NoTwoFactorChallengeAnnotationController::class)
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($controller, 'index');
	}
}
