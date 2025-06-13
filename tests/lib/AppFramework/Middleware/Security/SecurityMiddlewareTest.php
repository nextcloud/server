<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\Exceptions\AppNotEnabledException;
use OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException;
use OC\AppFramework\Middleware\Security\Exceptions\ExAppRequiredException;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\Appframework\Middleware\Security\Exceptions\StrictCookieMissingException;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Settings\AuthorizedGroupMapper;
use OC\User\Session;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\Ip\IRemoteAddress;
use Psr\Log\LoggerInterface;
use Test\AppFramework\Middleware\Security\Mock\NormalController;
use Test\AppFramework\Middleware\Security\Mock\OCSController;
use Test\AppFramework\Middleware\Security\Mock\SecurityMiddlewareController;

class SecurityMiddlewareTest extends \Test\TestCase {
	/** @var SecurityMiddleware|\PHPUnit\Framework\MockObject\MockObject */
	private $middleware;
	/** @var SecurityMiddlewareController */
	private $controller;
	/** @var SecurityException */
	private $secException;
	/** @var SecurityException */
	private $secAjaxException;
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ControllerMethodReflector */
	private $reader;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** @var INavigationManager|\PHPUnit\Framework\MockObject\MockObject */
	private $navigationManager;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var AuthorizedGroupMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $authorizedGroupMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->authorizedGroupMapper = $this->createMock(AuthorizedGroupMapper::class);
		$this->userSession = $this->createMock(Session::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('test');
		$this->userSession->method('getUser')->willReturn($user);
		$this->request = $this->createMock(IRequest::class);
		$this->controller = new SecurityMiddlewareController(
			'test',
			$this->request
		);
		$this->reader = new ControllerMethodReflector();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->middleware = $this->getMiddleware(true, true, false);
		$this->secException = new SecurityException('hey', false);
		$this->secAjaxException = new SecurityException('hey', true);
	}

	private function getMiddleware(bool $isLoggedIn, bool $isAdminUser, bool $isSubAdmin, bool $isAppEnabledForUser = true): SecurityMiddleware {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->willReturn($isAppEnabledForUser);
		$remoteIpAddress = $this->createMock(IRemoteAddress::class);
		$remoteIpAddress->method('allowsAdminActions')->willReturn(true);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')
			->willReturn($isAdminUser);
		$subAdminManager = $this->createMock(ISubAdmin::class);
		$subAdminManager->method('isSubAdmin')
			->willReturn($isSubAdmin);

		return new SecurityMiddleware(
			$this->request,
			$this->reader,
			$this->navigationManager,
			$this->urlGenerator,
			$this->logger,
			'files',
			$isLoggedIn,
			$groupManager,
			$subAdminManager,
			$this->appManager,
			$this->l10n,
			$this->authorizedGroupMapper,
			$this->userSession,
			$remoteIpAddress
		);
	}

	public static function dataNoCSRFRequiredPublicPage(): array {
		return [
			['testAnnotationNoCSRFRequiredPublicPage'],
			['testAnnotationNoCSRFRequiredAttributePublicPage'],
			['testAnnotationPublicPageAttributeNoCSRFRequired'],
			['testAttributeNoCSRFRequiredPublicPage'],
		];
	}

	public static function dataPublicPage(): array {
		return [
			['testAnnotationPublicPage'],
			['testAttributePublicPage'],
		];
	}

	public static function dataNoCSRFRequired(): array {
		return [
			['testAnnotationNoCSRFRequired'],
			['testAttributeNoCSRFRequired'],
		];
	}

	public static function dataPublicPageStrictCookieRequired(): array {
		return [
			['testAnnotationPublicPageStrictCookieRequired'],
			['testAnnotationStrictCookieRequiredAttributePublicPage'],
			['testAnnotationPublicPageAttributeStrictCookiesRequired'],
			['testAttributePublicPageStrictCookiesRequired'],
		];
	}

	public static function dataNoCSRFRequiredPublicPageStrictCookieRequired(): array {
		return [
			['testAnnotationNoCSRFRequiredPublicPageStrictCookieRequired'],
			['testAttributeNoCSRFRequiredPublicPageStrictCookiesRequired'],
		];
	}

	public static function dataNoAdminRequiredNoCSRFRequired(): array {
		return [
			['testAnnotationNoAdminRequiredNoCSRFRequired'],
			['testAttributeNoAdminRequiredNoCSRFRequired'],
		];
	}

	public static function dataNoAdminRequiredNoCSRFRequiredPublicPage(): array {
		return [
			['testAnnotationNoAdminRequiredNoCSRFRequiredPublicPage'],
			['testAttributeNoAdminRequiredNoCSRFRequiredPublicPage'],
		];
	}

	public static function dataNoCSRFRequiredSubAdminRequired(): array {
		return [
			['testAnnotationNoCSRFRequiredSubAdminRequired'],
			['testAnnotationNoCSRFRequiredAttributeSubAdminRequired'],
			['testAnnotationSubAdminRequiredAttributeNoCSRFRequired'],
			['testAttributeNoCSRFRequiredSubAdminRequired'],
		];
	}

	public static function dataExAppRequired(): array {
		return [
			['testAnnotationExAppRequired'],
			['testAttributeExAppRequired'],
		];
	}

	/**
	 * @dataProvider dataNoCSRFRequiredPublicPage
	 */
	public function testSetNavigationEntry(string $method): void {
		$this->navigationManager->expects($this->once())
			->method('setActiveEntry')
			->with($this->equalTo('files'));

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}


	/**
	 * @param string $method
	 * @param string $test
	 */
	private function ajaxExceptionStatus($method, $test, $status) {
		$isLoggedIn = false;
		$isAdminUser = false;

		// isAdminUser requires isLoggedIn call to return true
		if ($test === 'isAdminUser') {
			$isLoggedIn = true;
		}

		$sec = $this->getMiddleware($isLoggedIn, $isAdminUser, false);

		try {
			$this->reader->reflect($this->controller, $method);
			$sec->beforeController($this->controller, $method);
		} catch (SecurityException $ex) {
			$this->assertEquals($status, $ex->getCode());
		}

		// add assertion if everything should work fine otherwise phpunit will
		// complain
		if ($status === 0) {
			$this->addToAssertionCount(1);
		}
	}

	public function testAjaxStatusLoggedInCheck(): void {
		$this->ajaxExceptionStatus(
			'testNoAnnotationNorAttribute',
			'isLoggedIn',
			Http::STATUS_UNAUTHORIZED
		);
	}

	/**
	 * @dataProvider dataNoCSRFRequired
	 */
	public function testAjaxNotAdminCheck(string $method): void {
		$this->ajaxExceptionStatus(
			$method,
			'isAdminUser',
			Http::STATUS_FORBIDDEN
		);
	}

	/**
	 * @dataProvider dataPublicPage
	 */
	public function testAjaxStatusCSRFCheck(string $method): void {
		$this->ajaxExceptionStatus(
			$method,
			'passesCSRFCheck',
			Http::STATUS_PRECONDITION_FAILED
		);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredPublicPage
	 */
	public function testAjaxStatusAllGood(string $method): void {
		$this->ajaxExceptionStatus(
			$method,
			'isLoggedIn',
			0
		);
		$this->ajaxExceptionStatus(
			$method,
			'isAdminUser',
			0
		);
		$this->ajaxExceptionStatus(
			$method,
			'passesCSRFCheck',
			0
		);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredPublicPage
	 */
	public function testNoChecks(string $method): void {
		$this->request->expects($this->never())
			->method('passesCSRFCheck')
			->willReturn(false);

		$sec = $this->getMiddleware(false, false, false);

		$this->reader->reflect($this->controller, $method);
		$sec->beforeController($this->controller, $method);
	}

	/**
	 * @param string $method
	 * @param string $expects
	 */
	private function securityCheck($method, $expects, $shouldFail = false) {
		// admin check requires login
		if ($expects === 'isAdminUser') {
			$isLoggedIn = true;
			$isAdminUser = !$shouldFail;
		} else {
			$isLoggedIn = !$shouldFail;
			$isAdminUser = false;
		}

		$sec = $this->getMiddleware($isLoggedIn, $isAdminUser, false);

		if ($shouldFail) {
			$this->expectException(SecurityException::class);
		} else {
			$this->addToAssertionCount(1);
		}

		$this->reader->reflect($this->controller, $method);
		$sec->beforeController($this->controller, $method);
	}


	/**
	 * @dataProvider dataPublicPage
	 */
	public function testCsrfCheck(string $method): void {
		$this->expectException(CrossSiteRequestForgeryException::class);

		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);
		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredPublicPage
	 */
	public function testNoCsrfCheck(string $method): void {
		$this->request->expects($this->never())
			->method('passesCSRFCheck')
			->willReturn(false);

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataPublicPage
	 */
	public function testPassesCsrfCheck(string $method): void {
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataPublicPage
	 */
	public function testFailCsrfCheck(string $method): void {
		$this->expectException(CrossSiteRequestForgeryException::class);

		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(false);
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataPublicPageStrictCookieRequired
	 */
	public function testStrictCookieRequiredCheck(string $method): void {
		$this->expectException(\OC\AppFramework\Middleware\Security\Exceptions\StrictCookieMissingException::class);

		$this->request->expects($this->never())
			->method('passesCSRFCheck');
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(false);

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredPublicPage
	 */
	public function testNoStrictCookieRequiredCheck(string $method): void {
		$this->request->expects($this->never())
			->method('passesStrictCookieCheck')
			->willReturn(false);

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredPublicPageStrictCookieRequired
	 */
	public function testPassesStrictCookieRequiredCheck(string $method): void {
		$this->request
			->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);

		$this->reader->reflect($this->controller, $method);
		$this->middleware->beforeController($this->controller, $method);
	}

	public static function dataCsrfOcsController(): array {
		return [
			[NormalController::class, false, false, true],
			[NormalController::class, false,  true, true],
			[NormalController::class,  true, false, true],
			[NormalController::class,  true,  true, true],

			[OCSController::class, false, false,  true],
			[OCSController::class, false,  true, false],
			[OCSController::class,  true, false, false],
			[OCSController::class,  true,  true, false],
		];
	}

	/**
	 * @dataProvider dataCsrfOcsController
	 * @param string $controllerClass
	 * @param bool $hasOcsApiHeader
	 * @param bool $hasBearerAuth
	 * @param bool $exception
	 */
	public function testCsrfOcsController(string $controllerClass, bool $hasOcsApiHeader, bool $hasBearerAuth, bool $exception): void {
		$this->request
			->method('getHeader')
			->willReturnCallback(function ($header) use ($hasOcsApiHeader, $hasBearerAuth) {
				if ($header === 'OCS-APIREQUEST' && $hasOcsApiHeader) {
					return 'true';
				}
				if ($header === 'Authorization' && $hasBearerAuth) {
					return 'Bearer TOKEN!';
				}
				return '';
			});
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);

		$controller = new $controllerClass('test', $this->request);

		try {
			$this->middleware->beforeController($controller, 'foo');
			$this->assertFalse($exception);
		} catch (CrossSiteRequestForgeryException $e) {
			$this->assertTrue($exception);
		}
	}

	/**
	 * @dataProvider dataNoAdminRequiredNoCSRFRequired
	 */
	public function testLoggedInCheck(string $method): void {
		$this->securityCheck($method, 'isLoggedIn');
	}

	/**
	 * @dataProvider dataNoAdminRequiredNoCSRFRequired
	 */
	public function testFailLoggedInCheck(string $method): void {
		$this->securityCheck($method, 'isLoggedIn', true);
	}

	/**
	 * @dataProvider dataNoCSRFRequired
	 */
	public function testIsAdminCheck(string $method): void {
		$this->securityCheck($method, 'isAdminUser');
	}

	/**
	 * @dataProvider dataNoCSRFRequiredSubAdminRequired
	 */
	public function testIsNotSubAdminCheck(string $method): void {
		$this->reader->reflect($this->controller, $method);
		$sec = $this->getMiddleware(true, false, false);

		$this->expectException(SecurityException::class);
		$sec->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredSubAdminRequired
	 */
	public function testIsSubAdminCheck(string $method): void {
		$this->reader->reflect($this->controller, $method);
		$sec = $this->getMiddleware(true, false, true);

		$sec->beforeController($this->controller, $method);
		$this->addToAssertionCount(1);
	}

	/**
	 * @dataProvider dataNoCSRFRequiredSubAdminRequired
	 */
	public function testIsSubAdminAndAdminCheck(string $method): void {
		$this->reader->reflect($this->controller, $method);
		$sec = $this->getMiddleware(true, true, true);

		$sec->beforeController($this->controller, $method);
		$this->addToAssertionCount(1);
	}

	/**
	 * @dataProvider dataNoCSRFRequired
	 */
	public function testFailIsAdminCheck(string $method): void {
		$this->securityCheck($method, 'isAdminUser', true);
	}

	/**
	 * @dataProvider dataNoAdminRequiredNoCSRFRequiredPublicPage
	 */
	public function testRestrictedAppLoggedInPublicPage(string $method): void {
		$middleware = $this->getMiddleware(true, false, false);
		$this->reader->reflect($this->controller, $method);

		$this->appManager->method('getAppPath')
			->with('files')
			->willReturn('foo');

		$this->appManager->method('isEnabledForUser')
			->with('files')
			->willReturn(false);

		$middleware->beforeController($this->controller, $method);
		$this->addToAssertionCount(1);
	}

	/**
	 * @dataProvider dataNoAdminRequiredNoCSRFRequiredPublicPage
	 */
	public function testRestrictedAppNotLoggedInPublicPage(string $method): void {
		$middleware = $this->getMiddleware(false, false, false);
		$this->reader->reflect($this->controller, $method);

		$this->appManager->method('getAppPath')
			->with('files')
			->willReturn('foo');

		$this->appManager->method('isEnabledForUser')
			->with('files')
			->willReturn(false);

		$middleware->beforeController($this->controller, $method);
		$this->addToAssertionCount(1);
	}

	/**
	 * @dataProvider dataNoAdminRequiredNoCSRFRequired
	 */
	public function testRestrictedAppLoggedIn(string $method): void {
		$middleware = $this->getMiddleware(true, false, false, false);
		$this->reader->reflect($this->controller, $method);

		$this->appManager->method('getAppPath')
			->with('files')
			->willReturn('foo');

		$this->expectException(AppNotEnabledException::class);
		$middleware->beforeController($this->controller, $method);
	}


	public function testAfterExceptionNotCaughtThrowsItAgain(): void {
		$ex = new \Exception();
		$this->expectException(\Exception::class);
		$this->middleware->afterException($this->controller, 'test', $ex);
	}

	public function testAfterExceptionReturnsRedirectForNotLoggedInUser(): void {
		$this->request = new Request(
			[
				'server' =>
					[
						'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
						'REQUEST_URI' => 'nextcloud/index.php/apps/specialapp'
					]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->middleware = $this->getMiddleware(false, false, false);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.login.showLoginForm',
				[
					'redirect_url' => 'nextcloud/index.php/apps/specialapp',
				]
			)
			->willReturn('http://localhost/nextcloud/index.php/login?redirect_url=nextcloud/index.php/apps/specialapp');
		$this->logger
			->expects($this->once())
			->method('debug');
		$response = $this->middleware->afterException(
			$this->controller,
			'test',
			new NotLoggedInException()
		);
		$expected = new RedirectResponse('http://localhost/nextcloud/index.php/login?redirect_url=nextcloud/index.php/apps/specialapp');
		$this->assertEquals($expected, $response);
	}

	public function testAfterExceptionRedirectsToWebRootAfterStrictCookieFail(): void {
		$this->request = new Request(
			[
				'server' => [
					'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'REQUEST_URI' => 'nextcloud/index.php/apps/specialapp',
				],
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);

		$this->middleware = $this->getMiddleware(false, false, false);
		$response = $this->middleware->afterException(
			$this->controller,
			'test',
			new StrictCookieMissingException()
		);

		$expected = new RedirectResponse(\OC::$WEBROOT . '/');
		$this->assertEquals($expected, $response);
	}


	/**
	 * @return array
	 */
	public static function exceptionProvider(): array {
		return [
			[
				new AppNotEnabledException(),
			],
			[
				new CrossSiteRequestForgeryException(),
			],
			[
				new NotAdminException(''),
			],
		];
	}

	/**
	 * @dataProvider exceptionProvider
	 * @param SecurityException $exception
	 */
	public function testAfterExceptionReturnsTemplateResponse(SecurityException $exception): void {
		$this->request = new Request(
			[
				'server' =>
					[
						'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
						'REQUEST_URI' => 'nextcloud/index.php/apps/specialapp'
					]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->middleware = $this->getMiddleware(false, false, false);
		$this->logger
			->expects($this->once())
			->method('debug');
		$response = $this->middleware->afterException(
			$this->controller,
			'test',
			$exception
		);
		$expected = new TemplateResponse('core', '403', ['message' => $exception->getMessage()], 'guest');
		$expected->setStatus($exception->getCode());
		$this->assertEquals($expected, $response);
	}

	public function testAfterAjaxExceptionReturnsJSONError(): void {
		$response = $this->middleware->afterException($this->controller, 'test',
			$this->secAjaxException);

		$this->assertTrue($response instanceof JSONResponse);
	}

	/**
	 * @dataProvider dataExAppRequired
	 */
	public function testExAppRequired(string $method): void {
		$middleware = $this->getMiddleware(true, false, false);
		$this->reader->reflect($this->controller, $method);

		$session = $this->createMock(ISession::class);
		$session->method('get')->with('app_api')->willReturn(true);
		$this->userSession->method('getSession')->willReturn($session);

		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->willReturn(true);

		$middleware->beforeController($this->controller, $method);
	}

	/**
	 * @dataProvider dataExAppRequired
	 */
	public function testExAppRequiredError(string $method): void {
		$middleware = $this->getMiddleware(true, false, false, false);
		$this->reader->reflect($this->controller, $method);

		$session = $this->createMock(ISession::class);
		$session->method('get')->with('app_api')->willReturn(false);
		$this->userSession->method('getSession')->willReturn($session);

		$this->expectException(ExAppRequiredException::class);
		$middleware->beforeController($this->controller, $method);
	}
}
