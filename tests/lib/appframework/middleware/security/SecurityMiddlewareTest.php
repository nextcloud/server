<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;


class SecurityMiddlewareTest extends \PHPUnit_Framework_TestCase {

	private $middleware;
	private $controller;
	private $secException;
	private $secAjaxException;
	private $request;
	private $reader;
	private $logger;
	private $navigationManager;
	private $urlGenerator;

	public function setUp() {
		$this->controller = $this->getMockBuilder('OCP\AppFramework\Controller')
			->disableOriginalConstructor()
				->getMock();
		$this->reader = new ControllerMethodReflector();
		$this->logger = $this->getMockBuilder(
				'OCP\ILogger')
				->disableOriginalConstructor()
				->getMock();
		$this->navigationManager = $this->getMockBuilder(
				'OCP\INavigationManager')
				->disableOriginalConstructor()
				->getMock();
		$this->urlGenerator = $this->getMockBuilder(
				'OCP\IURLGenerator')
				->disableOriginalConstructor()
				->getMock();
		$this->request = $this->getMockBuilder(
				'OCP\IRequest')
				->disableOriginalConstructor()
				->getMock();
		$this->middleware = $this->getMiddleware(true, true);
		$this->secException = new SecurityException('hey', false);
		$this->secAjaxException = new SecurityException('hey', true);
	}


	private function getMiddleware($isLoggedIn, $isAdminUser){
		return new SecurityMiddleware(
			$this->request,
			$this->reader,
			$this->navigationManager,
			$this->urlGenerator,
			$this->logger,
			'files',
			$isLoggedIn,
			$isAdminUser
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
		$this->middleware->beforeController(__CLASS__, __FUNCTION__);
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
			$sec->beforeController(__CLASS__, $method);
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
		$sec->beforeController(__CLASS__, __FUNCTION__);
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

		if($shouldFail){
			$this->setExpectedException('\OC\AppFramework\Middleware\Security\SecurityException');
		} else {
			$this->assertTrue(true);
		}

		$this->reader->reflect(__CLASS__, $method);
		$sec->beforeController(__CLASS__, $method);
	}


	/**
	 * @PublicPage
	 * @expectedException \OC\AppFramework\Middleware\Security\SecurityException
	 */
	public function testCsrfCheck(){
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->will($this->returnValue(false));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController(__CLASS__, __FUNCTION__);
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
		$this->middleware->beforeController(__CLASS__, __FUNCTION__);
	}


	/**
	 * @PublicPage
	 */
	public function testFailCsrfCheck(){
		$this->request->expects($this->once())
			->method('passesCSRFCheck')
			->will($this->returnValue(true));

		$this->reader->reflect(__CLASS__, __FUNCTION__);
		$this->middleware->beforeController(__CLASS__, __FUNCTION__);
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


	public function testAfterExceptionReturnsRedirect(){
		$this->request = new Request(
			array('server' =>
				array('HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'REQUEST_URI' => 'owncloud/index.php/apps/specialapp')
			)
		);
		$this->middleware = $this->getMiddleware(true, true);
		$response = $this->middleware->afterException($this->controller, 'test',
				$this->secException);

		$this->assertTrue($response instanceof RedirectResponse);
		$this->assertEquals('?redirect_url=owncloud%2Findex.php%2Fapps%2Fspecialapp', $response->getRedirectURL());
	}


	public function testAfterAjaxExceptionReturnsJSONError(){
		$response = $this->middleware->afterException($this->controller, 'test',
				$this->secAjaxException);

		$this->assertTrue($response instanceof JSONResponse);
	}


}
