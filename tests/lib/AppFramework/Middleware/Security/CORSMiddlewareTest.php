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
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\User\Session;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
use Test\AppFramework\Middleware\Security\Mock\CORSMiddlewareController;

class CORSMiddlewareTest extends \Test\TestCase {
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var Session|MockObject */
	private $session;
	/** @var IThrottler|MockObject */
	private $throttler;
	/** @var CORSMiddlewareController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->reflector = new ControllerMethodReflector();
		$this->session = $this->createMock(Session::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->controller = new CORSMiddlewareController(
			'test',
			$this->createMock(IRequest::class)
		);
	}

	public function dataSetCORSAPIHeader(): array {
		return [
			['testSetCORSAPIHeader'],
			['testSetCORSAPIHeaderAttribute'],
		];
	}

	/**
	 * @dataProvider dataSetCORSAPIHeader
	 */
	public function testSetCORSAPIHeader(string $method): void {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test'
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = $middleware->afterController($this->controller, $method, new Response());
		$headers = $response->getHeaders();
		$this->assertEquals('test', $headers['Access-Control-Allow-Origin']);
	}

	public function testNoAnnotationNoCORSHEADER(): void {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test'
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = $middleware->afterController($this->controller, __FUNCTION__, new Response());
		$headers = $response->getHeaders();
		$this->assertFalse(array_key_exists('Access-Control-Allow-Origin', $headers));
	}

	public function dataNoOriginHeaderNoCORSHEADER(): array {
		return [
			['testNoOriginHeaderNoCORSHEADER'],
			['testNoOriginHeaderNoCORSHEADERAttribute'],
		];
	}

	/**
	 * @dataProvider dataNoOriginHeaderNoCORSHEADER
	 */
	public function testNoOriginHeaderNoCORSHEADER(string $method): void {
		$request = new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = $middleware->afterController($this->controller, $method, new Response());
		$headers = $response->getHeaders();
		$this->assertFalse(array_key_exists('Access-Control-Allow-Origin', $headers));
	}

	public function dataCorsIgnoredIfWithCredentialsHeaderPresent(): array {
		return [
			['testCorsIgnoredIfWithCredentialsHeaderPresent'],
			['testCorsAttributeIgnoredIfWithCredentialsHeaderPresent'],
		];
	}

	/**
	 * @dataProvider dataCorsIgnoredIfWithCredentialsHeaderPresent
	 */
	public function testCorsIgnoredIfWithCredentialsHeaderPresent(string $method): void {
		$this->expectException(\OC\AppFramework\Middleware\Security\Exceptions\SecurityException::class);

		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test'
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$response = new Response();
		$response->addHeader('AcCess-control-Allow-Credentials ', 'TRUE');
		$middleware->afterController($this->controller, $method, $response);
	}

	public function dataNoCORSOnAnonymousPublicPage(): array {
		return [
			['testNoCORSOnAnonymousPublicPage'],
			['testNoCORSOnAnonymousPublicPageAttribute'],
			['testNoCORSAttributeOnAnonymousPublicPage'],
			['testNoCORSAttributeOnAnonymousPublicPageAttribute'],
		];
	}

	/**
	 * @dataProvider dataNoCORSOnAnonymousPublicPage
	 */
	public function testNoCORSOnAnonymousPublicPage(string $method): void {
		$request = new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$this->session->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->session->expects($this->never())
			->method('logout');
		$this->session->expects($this->never())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->willReturn(true);
		$this->reflector->reflect($this->controller, $method);

		$middleware->beforeController($this->controller, $method);
	}

	public function dataCORSShouldNeverAllowCookieAuth(): array {
		return [
			['testCORSShouldNeverAllowCookieAuth'],
			['testCORSShouldNeverAllowCookieAuthAttribute'],
			['testCORSAttributeShouldNeverAllowCookieAuth'],
			['testCORSAttributeShouldNeverAllowCookieAuthAttribute'],
		];
	}

	/**
	 * @dataProvider dataCORSShouldNeverAllowCookieAuth
	 */
	public function testCORSShouldNeverAllowCookieAuth(string $method): void {
		$request = new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$this->session->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->never())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->willReturn(true);

		$this->expectException(SecurityException::class);
		$middleware->beforeController($this->controller, $method);
	}

	public function dataCORSShouldRelogin(): array {
		return [
			['testCORSShouldRelogin'],
			['testCORSAttributeShouldRelogin'],
		];
	}

	/**
	 * @dataProvider dataCORSShouldRelogin
	 */
	public function testCORSShouldRelogin(string $method): void {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->once())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->willReturn(true);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$middleware->beforeController($this->controller, $method);
	}

	public function dataCORSShouldFailIfPasswordLoginIsForbidden(): array {
		return [
			['testCORSShouldFailIfPasswordLoginIsForbidden'],
			['testCORSAttributeShouldFailIfPasswordLoginIsForbidden'],
		];
	}

	/**
	 * @dataProvider dataCORSShouldFailIfPasswordLoginIsForbidden
	 */
	public function testCORSShouldFailIfPasswordLoginIsForbidden(string $method): void {
		$this->expectException(\OC\AppFramework\Middleware\Security\Exceptions\SecurityException::class);

		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->once())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->will($this->throwException(new \OC\Authentication\Exceptions\PasswordLoginForbiddenException));
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$middleware->beforeController($this->controller, $method);
	}

	public function dataCORSShouldNotAllowCookieAuth(): array {
		return [
			['testCORSShouldNotAllowCookieAuth'],
			['testCORSAttributeShouldNotAllowCookieAuth'],
		];
	}

	/**
	 * @dataProvider dataCORSShouldNotAllowCookieAuth
	 */
	public function testCORSShouldNotAllowCookieAuth(string $method): void {
		$this->expectException(\OC\AppFramework\Middleware\Security\Exceptions\SecurityException::class);

		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->session->expects($this->once())
			->method('logout');
		$this->session->expects($this->once())
			->method('logClientIn')
			->with($this->equalTo('user'), $this->equalTo('pass'))
			->willReturn(false);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);

		$middleware->beforeController($this->controller, $method);
	}

	public function testAfterExceptionWithSecurityExceptionNoStatus() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$response = $middleware->afterException($this->controller, __FUNCTION__, new SecurityException('A security exception'));

		$expected = new JSONResponse(['message' => 'A security exception'], 500);
		$this->assertEquals($expected, $response);
	}

	public function testAfterExceptionWithSecurityExceptionWithStatus() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$response = $middleware->afterException($this->controller, __FUNCTION__, new SecurityException('A security exception', 501));

		$expected = new JSONResponse(['message' => 'A security exception'], 501);
		$this->assertEquals($expected, $response);
	}

	public function testAfterExceptionWithRegularException() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('A regular exception');

		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler);
		$middleware->afterException($this->controller, __FUNCTION__, new \Exception('A regular exception'));
	}
}
