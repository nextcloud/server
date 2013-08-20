<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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

use OC\AppFramework\Http\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;


class SecurityMiddlewareTest extends \PHPUnit_Framework_TestCase {

	private $middleware;
	private $controller;
	private $secException;
	private $secAjaxException;
	private $request;

	public function setUp() {
		$api = $this->getMock('OC\AppFramework\Core\API', array(), array('test'));
		$this->controller = $this->getMock('OC\AppFramework\Controller\Controller',
				array(), array($api, new Request()));

		$this->request = new Request();
		$this->middleware = new SecurityMiddleware($api, $this->request);
		$this->secException = new SecurityException('hey', false);
		$this->secAjaxException = new SecurityException('hey', true);
	}


	private function getAPI(){
		return $this->getMock('OC\AppFramework\Core\API',
					array('isLoggedIn', 'passesCSRFCheck', 'isAdminUser',
							'isSubAdminUser', 'activateNavigationEntry',
							'getUserId'),
					array('app'));
	}


	private function checkNavEntry($method, $shouldBeActivated=false){
		$api = $this->getAPI();

		if($shouldBeActivated){
			$api->expects($this->once())
				->method('activateNavigationEntry');
		} else {
			$api->expects($this->never())
				->method('activateNavigationEntry');
		}

		$sec = new SecurityMiddleware($api, $this->request);
		$sec->beforeController('\OC\AppFramework\Middleware\Security\SecurityMiddlewareTest', $method);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testSetNavigationEntry(){
		$this->checkNavEntry('testSetNavigationEntry', true);
	}


	private function ajaxExceptionStatus($method, $test, $status) {
		$api = $this->getAPI();
		$api->expects($this->any())
				->method($test)
				->will($this->returnValue(false));

		// isAdminUser requires isLoggedIn call to return true
		if ($test === 'isAdminUser') {
			$api->expects($this->any())
				->method('isLoggedIn')
				->will($this->returnValue(true));
		}

		$sec = new SecurityMiddleware($api, $this->request);

		try {
			$sec->beforeController('\OC\AppFramework\Middleware\Security\SecurityMiddlewareTest',
					$method);
		} catch (SecurityException $ex){
			$this->assertEquals($status, $ex->getCode());
		}
	}

	public function testAjaxStatusLoggedInCheck() {
		$this->ajaxExceptionStatus(
			'testAjaxStatusLoggedInCheck',
			'isLoggedIn',
			Http::STATUS_UNAUTHORIZED
		);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function testAjaxNotAdminCheck() {
		$this->ajaxExceptionStatus(
			'testAjaxNotAdminCheck',
			'isAdminUser',
			Http::STATUS_FORBIDDEN
		);
	}

	/**
	 * @PublicPage
	 */
	public function testAjaxStatusCSRFCheck() {
		$this->ajaxExceptionStatus(
			'testAjaxStatusCSRFCheck',
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
			'testAjaxStatusAllGood',
			'isLoggedIn',
			0
		);
		$this->ajaxExceptionStatus(
			'testAjaxStatusAllGood',
			'isAdminUser',
			0
		);
		$this->ajaxExceptionStatus(
			'testAjaxStatusAllGood',
			'isSubAdminUser',
			0
		);
		$this->ajaxExceptionStatus(
			'testAjaxStatusAllGood',
			'passesCSRFCheck',
			0
		);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function testNoChecks(){
		$api = $this->getAPI();
		$api->expects($this->never())
				->method('passesCSRFCheck')
				->will($this->returnValue(true));
		$api->expects($this->never())
				->method('isAdminUser')
				->will($this->returnValue(true));
		$api->expects($this->never())
				->method('isLoggedIn')
				->will($this->returnValue(true));

		$sec = new SecurityMiddleware($api, $this->request);
		$sec->beforeController('\OC\AppFramework\Middleware\Security\SecurityMiddlewareTest',
				'testNoChecks');
	}


	private function securityCheck($method, $expects, $shouldFail=false){
		$api = $this->getAPI();
		$api->expects($this->once())
				->method($expects)
				->will($this->returnValue(!$shouldFail));

		// admin check requires login
		if ($expects === 'isAdminUser') {
			$api->expects($this->once())
				->method('isLoggedIn')
				->will($this->returnValue(true));
		}

		$sec = new SecurityMiddleware($api, $this->request);

		if($shouldFail){
			$this->setExpectedException('\OC\AppFramework\Middleware\Security\SecurityException');
		} else {
			$this->setExpectedException(null);
		}

		$sec->beforeController('\OC\AppFramework\Middleware\Security\SecurityMiddlewareTest', $method);
	}


	/**
	 * @PublicPage
	 */
	public function testCsrfCheck(){
		$this->securityCheck('testCsrfCheck', 'passesCSRFCheck');
	}


	/**
	 * @PublicPage
	 */
	public function testFailCsrfCheck(){
		$this->securityCheck('testFailCsrfCheck', 'passesCSRFCheck', true);
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function testLoggedInCheck(){
		$this->securityCheck('testLoggedInCheck', 'isLoggedIn');
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function testFailLoggedInCheck(){
		$this->securityCheck('testFailLoggedInCheck', 'isLoggedIn', true);
	}


	/**
	 * @NoCSRFRequired
	 */
	public function testIsAdminCheck(){
		$this->securityCheck('testIsAdminCheck', 'isAdminUser');
	}


	/**
	 * @NoCSRFRequired
	 */
	public function testFailIsAdminCheck(){
		$this->securityCheck('testFailIsAdminCheck', 'isAdminUser', true);
	}


	public function testAfterExceptionNotCaughtThrowsItAgain(){
		$ex = new \Exception();
		$this->setExpectedException('\Exception');
		$this->middleware->afterException($this->controller, 'test', $ex);
	}


	public function testAfterExceptionReturnsRedirect(){
		$api = $this->getMock('OC\AppFramework\Core\API', array(), array('test'));
		$this->controller = $this->getMock('OC\AppFramework\Controller\Controller',
			array(), array($api, new Request()));

		$this->request = new Request(
			array('server' => array('HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')));
		$this->middleware = new SecurityMiddleware($api, $this->request);
		$response = $this->middleware->afterException($this->controller, 'test',
				$this->secException);

		$this->assertTrue($response instanceof RedirectResponse);
	}


	public function testAfterAjaxExceptionReturnsJSONError(){
		$response = $this->middleware->afterException($this->controller, 'test',
				$this->secAjaxException);

		$this->assertTrue($response instanceof JSONResponse);
	}


}
