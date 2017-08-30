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
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
use Test\AppFramework\Middleware\Security\Mock\CORSMiddlewareController;

class CORSMiddlewareTest extends \Test\TestCase {
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var IUserSession|MockObject */
	private $session;
	/** @var IThrottler|MockObject */
	private $throttler;
	/** @var IConfig|MockObject */
	private $config;
	/** @var CORSMiddlewareController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		/** @var MockObject */
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getUserValue')->willReturn('["http:\/\/www.test.com"]');
		$this->config->method('setUserValue')->willReturn(true);

		$this->reflector = new ControllerMethodReflector();
		$this->session = $this->createMock(IUserSession::class);
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

		$this->session = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$this->session = $userSession;
	}

	/**
	 * @dataProvider dataSetCORSAPIHeader
	 */
	public function testSetCORSAPIHeader(string $method): void {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'http://www.test.com'
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware(
			$request,
			$this->reflector,
			$this->session,
			$this->throttler,
			$this->config
		);

		$response = $middleware->afterController($this->controller, $method, new Response());
		$headers = $response->getHeaders();
		$this->assertEquals('http://www.test.com', $headers['Access-Control-Allow-Origin']);
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
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler, $this->config);

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
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler, $this->config);

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
					'HTTP_ORIGIN' => 'http://www.test.com',
				]
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->reflector->reflect($this->controller, $method);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler, $this->config);

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

	public function testAfterExceptionWithSecurityExceptionNoStatus() {
		$request = new Request(
			['server' => [
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'pass'
			]],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler, $this->config);
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
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler, $this->config);
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
		$middleware = new CORSMiddleware($request, $this->reflector, $this->session, $this->throttler, $this->config);
		$middleware->afterException($this->controller, __FUNCTION__, new \Exception('A regular exception'));
	}
}
