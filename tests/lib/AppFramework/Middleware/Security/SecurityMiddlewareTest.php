<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\Exceptions\AppNotEnabledException;
use OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\Appframework\Middleware\Security\Exceptions\StrictCookieMissingException;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Security\CSP\ContentSecurityPolicy;
use OC\Security\CSP\ContentSecurityPolicyManager;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\Security\CSRF\CsrfToken;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;

class SecurityMiddlewareTest extends \Test\TestCase {

	/** @var SecurityMiddleware|\PHPUnit_Framework_MockObject_MockObject */
	private $middleware;
	/** @var Controller|\PHPUnit_Framework_MockObject_MockObject */
	private $controller;
	/** @var SecurityException */
	private $secException;
	/** @var SecurityException */
	private $secAjaxException;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ControllerMethodReflector */
	private $reader;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var INavigationManager|\PHPUnit_Framework_MockObject_MockObject */
	private $navigationManager;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ContentSecurityPolicyManager|\PHPUnit_Framework_MockObject_MockObject */
	private $contentSecurityPolicyManager;
	/** @var CsrfTokenManager|\PHPUnit_Framework_MockObject_MockObject */
	private $csrfTokenManager;
	/** @var ContentSecurityPolicyNonceManager|\PHPUnit_Framework_MockObject_MockObject */
	private $cspNonceManager;
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	protected function setUp() {
		parent::setUp();

		$this->controller = $this->createMock(Controller::class);
		$this->reader = new ControllerMethodReflector();
		$this->logger = $this->createMock(ILogger::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);
		$this->contentSecurityPolicyManager = $this->createMock(ContentSecurityPolicyManager::class);
		$this->csrfTokenManager = $this->createMock(CsrfTokenManager::class);
		$this->cspNonceManager = $this->createMock(ContentSecurityPolicyNonceManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->middleware = $this->getMiddleware(true, true);
		$this->secException = new SecurityException('hey', false);
		$this->secAjaxException = new SecurityException('hey', true);
	}

	/**
	 * @param bool $isLoggedIn
	 * @param bool $isAdminUser
	 * @return SecurityMiddleware
	 */
	private function getMiddleware($isLoggedIn, $isAdminUser) {
		return new SecurityMiddleware(
			$this->request,
			$this->reader,
			$this->navigationManager,
			$this->urlGenerator,
			$this->logger,
			$this->session,
			'files',
			$isLoggedIn,
			$isAdminUser,
			$this->contentSecurityPolicyManager,
			$this->csrfTokenManager,
			$this->cspNonceManager,
			$this->appManager
		);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testSetNavigationEntry(){
		$this->navigationManager->expects($this->once())
			->method('setActiveEntry')
			->with($this->equalTo('files'));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
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

		$sec = $this->getMiddleware($isLoggedIn, $isAdminUser);

		try {
			$this->reader->reflect(__CLASS__, $method);
			$sec->beforeController($this->controller, $method);
		} catch (SecurityException $ex){
			$this->assertEquals($status, $ex->getCode());
		}

		// add assertion if everything should work fine otherwise phpunit will
		// complain
		if ($status === 0) {
			$this->assertTrue(true);
		}
	}

	public function testAjaxStatusLoggedInCheck() {
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'isLoggedIn',
			Http::STATUS_UNAUTHORIZED
		);
	}

	/**
	 * @NoCSRFRequired
	 */
	public function testAjaxNotAdminCheck() {
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'isAdminUser',
			Http::STATUS_FORBIDDEN
		);
	}

	/**
	 * @PublicPage
	 */
	public function testAjaxStatusCSRFCheck() {
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'passesCSRFCheck',
			Http::STATUS_PRECONDITION_FAILED
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testAjaxStatusAllGood() {
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'isLoggedIn',
			0
		);
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'isAdminUser',
			0
		);
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'isSubAdminUser',
			0
		);
		$this->ajaxExceptionStatus(
			__FUNCTION__,
			'passesCSRFCheck',
			0
		);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testNoChecks(){
		$this->request->expects($this->never())
			->method('passesCSRFCheck')
			->will($this->returnValue(false));

		$sec = $this->getMiddleware(false, false);

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$sec->beforeController($this->controller, __FUNCTION__);
	}


	/**
	 * @param string $method
	 * @param string $expects
	 */
	private function securityCheck($method, $expects, $shouldFail=false){
		// admin check requires login
		if ($expects === 'isAdminUser') {
			$isLoggedIn = true;
			$isAdminUser = !$shouldFail;
		} else {
			$isLoggedIn = !$shouldFail;
			$isAdminUser = false;
		}

		$sec = $this->getMiddleware($isLoggedIn, $isAdminUser);

		if($shouldFail) {
			$this->setExpectedException('\OC\AppFramework\Middleware\Security\Exceptions\SecurityException');
		} else {
			$this->assertTrue(true);
		}

		$this->reader->reflect(__CLASS__, $method);
		$sec->beforeController($this->controller, $method);
	}


	/**
	 * @PublicPage
	 * @expectedException \OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException
	 */
	public function testCsrfCheck(){
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->will($this->returnValue(false));
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->will($this->returnValue(true));
		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testNoCsrfCheck(){
		$this->request->expects($this->never())
			->method('passesCSRFCheck')
			->will($this->returnValue(false));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	/**
	 * @PublicPage
	 */
	public function testPassesCsrfCheck(){
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->will($this->returnValue(true));
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->will($this->returnValue(true));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	/**
	 * @PublicPage
	 * @expectedException \OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException
	 */
	public function testFailCsrfCheck(){
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->will($this->returnValue(false));
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->will($this->returnValue(true));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	/**
	 * @PublicPage
	 * @StrictCookieRequired
	 * @expectedException \OC\Appframework\Middleware\Security\Exceptions\StrictCookieMissingException
	 */
	public function testStrictCookieRequiredCheck() {
		$this->request->expects($this->never())
			->method('passesCSRFCheck');
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->will($this->returnValue(false));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testNoStrictCookieRequiredCheck() {
		$this->request->expects($this->never())
			->method('passesStrictCookieCheck')
			->will($this->returnValue(false));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @StrictCookieRequired
	 */
	public function testPassesStrictCookieRequiredCheck() {
		$this->request
			->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function dataCsrfOcsController() {
		$controller = $this->getMockBuilder('OCP\AppFramework\Controller')
			->disableOriginalConstructor()
			->getMock();
		$ocsController = $this->getMockBuilder('OCP\AppFramework\OCSController')
			->disableOriginalConstructor()
			->getMock();

		return [
			[$controller, false, true],
			[$controller, true,  true],

			[$ocsController, false, true],
			[$ocsController, true,  false],
		];
	}

	/**
	 * @dataProvider dataCsrfOcsController
	 * @param Controller $controller
	 * @param bool $hasOcsApiHeader
	 * @param bool $exception
	 */
	public function testCsrfOcsController(Controller $controller, $hasOcsApiHeader, $exception) {
		$this->request
			->method('getHeader')
			->with('OCS-APIREQUEST')
			->willReturn($hasOcsApiHeader ? 'true' : null);
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(true);

		try {
			$this->middleware->beforeController($controller, 'foo');
			$this->assertFalse($exception);
		} catch (CrossSiteRequestForgeryException $e) {
			$this->assertTrue($exception);
		}
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function testLoggedInCheck(){
		$this->securityCheck(__FUNCTION__, 'isLoggedIn');
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function testFailLoggedInCheck(){
		$this->securityCheck(__FUNCTION__, 'isLoggedIn', true);
	}


	/**
	 * @NoCSRFRequired
	 */
	public function testIsAdminCheck(){
		$this->securityCheck(__FUNCTION__, 'isAdminUser');
	}


	/**
	 * @NoCSRFRequired
	 */
	public function testFailIsAdminCheck(){
		$this->securityCheck(__FUNCTION__, 'isAdminUser', true);
	}


	public function testAfterExceptionNotCaughtThrowsItAgain(){
		$ex = new \Exception();
		$this->setExpectedException('\Exception');
		$this->middleware->afterException($this->controller, 'test', $ex);
	}

	public function testAfterExceptionReturnsRedirectForNotLoggedInUser() {
		$this->request = new Request(
			[
				'server' =>
					[
						'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
						'REQUEST_URI' => 'nextcloud/index.php/apps/specialapp'
					]
			],
			$this->createMock(ISecureRandom::class),
			$this->createMock(IConfig::class)
		);
		$this->middleware = $this->getMiddleware(false, false);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.login.showLoginForm',
				[
					'redirect_url' => 'nextcloud/index.php/apps/specialapp',
				]
			)
			->will($this->returnValue('http://localhost/nextcloud/index.php/login?redirect_url=nextcloud/index.php/apps/specialapp'));
		$this->logger
			->expects($this->once())
			->method('debug')
			->with('Current user is not logged in');
		$response = $this->middleware->afterException(
			$this->controller,
			'test',
			new NotLoggedInException()
		);
		$expected = new RedirectResponse('http://localhost/nextcloud/index.php/login?redirect_url=nextcloud/index.php/apps/specialapp');
		$this->assertEquals($expected , $response);
	}

	public function testAfterExceptionRedirectsToWebRootAfterStrictCookieFail() {
		$this->request = new Request(
			[
				'server' => [
					'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'REQUEST_URI' => 'nextcloud/index.php/apps/specialapp',
				],
			],
			$this->createMock(ISecureRandom::class),
			$this->createMock(IConfig::class)
		);

		$this->middleware = $this->getMiddleware(false, false);
		$response = $this->middleware->afterException(
			$this->controller,
			'test',
			new StrictCookieMissingException()
		);

		$expected = new RedirectResponse(\OC::$WEBROOT);
		$this->assertEquals($expected , $response);
	}


	/**
	 * @return array
	 */
	public function exceptionProvider() {
		return [
			[
				new AppNotEnabledException(),
			],
			[
				new CrossSiteRequestForgeryException(),
			],
			[
				new NotAdminException(),
			],
		];
	}

	/**
	 * @dataProvider exceptionProvider
	 * @param SecurityException $exception
	 */
	public function testAfterExceptionReturnsTemplateResponse(SecurityException $exception) {
		$this->request = new Request(
			[
				'server' =>
					[
						'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
						'REQUEST_URI' => 'nextcloud/index.php/apps/specialapp'
					]
			],
			$this->createMock(ISecureRandom::class),
			$this->createMock(IConfig::class)
		);
		$this->middleware = $this->getMiddleware(false, false);
		$this->logger
			->expects($this->once())
			->method('debug')
			->with($exception->getMessage());
		$response = $this->middleware->afterException(
			$this->controller,
			'test',
			$exception
		);
		$expected = new TemplateResponse('core', '403', ['file' => $exception->getMessage()], 'guest');
		$expected->setStatus($exception->getCode());
		$this->assertEquals($expected , $response);
	}

	public function testAfterAjaxExceptionReturnsJSONError(){
		$response = $this->middleware->afterException($this->controller, 'test',
			$this->secAjaxException);

		$this->assertTrue($response instanceof JSONResponse);
	}

	public function testAfterController() {
		$this->cspNonceManager
			->expects($this->once())
			->method('browserSupportsCspV3')
			->willReturn(false);
		$response = $this->createMock(Response::class);
		$defaultPolicy = new ContentSecurityPolicy();
		$defaultPolicy->addAllowedImageDomain('defaultpolicy');
		$currentPolicy = new ContentSecurityPolicy();
		$currentPolicy->addAllowedConnectDomain('currentPolicy');
		$mergedPolicy = new ContentSecurityPolicy();
		$mergedPolicy->addAllowedMediaDomain('mergedPolicy');
		$response
			->expects($this->exactly(2))
			->method('getContentSecurityPolicy')
			->willReturn($currentPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('getDefaultPolicy')
			->willReturn($defaultPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('mergePolicies')
			->with($defaultPolicy, $currentPolicy)
			->willReturn($mergedPolicy);
		$response->expects($this->once())
			->method('setContentSecurityPolicy')
			->with($mergedPolicy);

		$this->middleware->afterController($this->controller, 'test', $response);
	}

	public function testAfterControllerEmptyCSP() {
		$response = $this->createMock(Response::class);
		$emptyPolicy = new EmptyContentSecurityPolicy();
		$response->expects($this->any())
			->method('getContentSecurityPolicy')
			->willReturn($emptyPolicy);
		$response->expects($this->never())
			->method('setContentSecurityPolicy');

		$this->middleware->afterController($this->controller, 'test', $response);
	}

	public function testAfterControllerWithContentSecurityPolicy3Support() {
		$this->cspNonceManager
			->expects($this->once())
			->method('browserSupportsCspV3')
			->willReturn(true);
		$token = $this->createMock(CsrfToken::class);
		$token
			->expects($this->once())
			->method('getEncryptedValue')
			->willReturn('MyEncryptedToken');
		$this->csrfTokenManager
			->expects($this->once())
			->method('getToken')
			->willReturn($token);
		$response = $this->createMock(Response::class);
		$defaultPolicy = new ContentSecurityPolicy();
		$defaultPolicy->addAllowedImageDomain('defaultpolicy');
		$currentPolicy = new ContentSecurityPolicy();
		$currentPolicy->addAllowedConnectDomain('currentPolicy');
		$mergedPolicy = new ContentSecurityPolicy();
		$mergedPolicy->addAllowedMediaDomain('mergedPolicy');
		$response
			->expects($this->exactly(2))
			->method('getContentSecurityPolicy')
			->willReturn($currentPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('getDefaultPolicy')
			->willReturn($defaultPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('mergePolicies')
			->with($defaultPolicy, $currentPolicy)
			->willReturn($mergedPolicy);
		$response->expects($this->once())
			->method('setContentSecurityPolicy')
			->with($mergedPolicy);

		$this->assertEquals($response, $this->middleware->afterController($this->controller, 'test', $response));
	}
}
