<?php
/**
 * ownCloud - App Framework
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2014
 */


namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\CORSMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\Security\Bruteforce\Throttler;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;


class CORSMiddlewareTest extends \Test\TestCase {

	private $reflector;
	private $session;
	/** @var Throttler */
	private $throttler;

	protected function setUp() {
		parent::setUp();
		$this->reflector = new ControllerMethodReflector();
		$this->session = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()
			->getMock();
		$this->throttler =  $this->getMockBuilder('\OC\Security\Bruteforce\Throttler')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @CORS
	 */
	public function testSetCORSAPIHeader() {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test'
				]
			],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = $middleware->afterController($this, __FUNCTION__, new Response());
		$headers = $response->getHeaders();
		$this->assertEquals('test', $headers['Access-Control-Allow-Origin']);
	}


	public function testNoAnnotationNoCORSHEADER() {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test'
				]
			],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = $middleware->afterController($this, __FUNCTION__, new Response());
		$headers = $response->getHeaders();
		$this->assertFalse(array_key_exists('Access-Control-Allow-Origin', $headers));
	}


	/**
	 * @CORS
	 */
	public function testNoOriginHeaderNoCORSHEADER() {
		$request = new Request(
			[],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = $middleware->afterController($this, __FUNCTION__, new Response());
		$headers = $response->getHeaders();
		$this->assertFalse(array_key_exists('Access-Control-Allow-Origin', $headers));
	}


	/**
	 * @CORS
	 * @expectedException \OC\AppFramework\Middleware\Security\Exceptions\SecurityException
	 */
	public function testCorsIgnoredIfWithCredentialsHeaderPresent() {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test'
				]
			],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = new Response();
		$response->addHeader('AcCess-control-Allow-Credentials ', 'TRUE');
		$middleware->afterController($this, __FUNCTION__, $response);
	}

	/**
	 * @CORS
	 * @PublicPage
	 */
	public function testNoCORSShouldAllowCookieAuth() {
		$request = new Request(
			[],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$this->session->expects($this->never())
			->method('logout');
		$this->session->expects($this->never())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->will($this->returnValue(true));
		$this->reflector->reflect($this, __FUNCTION__);

		$middleware->beforeController($this, __FUNCTION__, new Response());
	}

	/**
	 * @CORS
	 */
	public function testCORSShouldRelogin() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->once())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->will($this->returnValue(true));
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$middleware->beforeController($this, __FUNCTION__, new Response());
	}

	/**
	 * @CORS
	 * @expectedException \OC\AppFramework\Middleware\Security\Exceptions\SecurityException
	 */
	public function testCORSShouldFailIfPasswordLoginIsForbidden() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->once())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->will($this->throwException(new \OC\Authentication\Exceptions\PasswordLoginForbiddenException));
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$middleware->beforeController($this, __FUNCTION__, new Response());
	}

	/**
	 * @CORS
	 * @expectedException \OC\AppFramework\Middleware\Security\Exceptions\SecurityException
	 */
	public function testCORSShouldNotAllowCookieAuth() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->once())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->will($this->returnValue(false));
		$this->reflector->reflect($this, __FUNCTION__);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$middleware->beforeController($this, __FUNCTION__, new Response());
	}

	public function testAfterExceptionWithSecurityExceptionNoStatus() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$response = $middleware->afterException($this, __FUNCTION__, new SecurityException('A security exception'));

		$expected = new JSONResponse(['message' => 'A security exception'], 500);
		$this->assertEquals($expected, $response);
	}

	public function testAfterExceptionWithSecurityExceptionWithStatus() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$response = $middleware->afterException($this, __FUNCTION__, new SecurityException('A security exception', 501));

		$expected = new JSONResponse(['message' => 'A security exception'], 501);
		$this->assertEquals($expected, $response);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage A regular exception
	 */
	public function testAfterExceptionWithRegularException() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock(),
			$this->getMockBuilder('\OCP\IConfig')->getMock()
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$middleware->afterException($this, __FUNCTION__, new \Exception('A regular exception'));
	}

}
