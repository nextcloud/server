<?php
/**
 * @copyright 2014 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
use OCP\IUser;
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

		/** @var MockObject */
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$this->session->expects($this->exactly(2))->method('getUser')->willReturn($user);

		$this->config
			->method('getSystemValue')
			->willReturnCallback(fn (string $key, mixed $default) => match (true) {
				$key === 'cors.allowed-domains' => ['http://www.test.com'],
				default => $default,
			});

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

		/** @var MockObject */
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$this->session->expects($this->exactly(2))->method('getUser')->willReturn($user);

		$this->config
			->method('getSystemValue')
			->willReturnCallback(fn (string $key, mixed $default) => match (true) {
				$key === 'cors.allowed-domains' => ['http://www.test.com'],
				default => $default,
			});

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
