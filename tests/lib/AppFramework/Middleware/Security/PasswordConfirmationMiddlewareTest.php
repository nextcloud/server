<?php
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\NotConfirmedException;
use OC\AppFramework\Middleware\Security\PasswordConfirmationMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use Test\AppFramework\Middleware\Security\Mock\PasswordConfirmationMiddlewareController;
use Test\TestCase;

class PasswordConfirmationMiddlewareTest extends TestCase {
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $user;
	/** @var PasswordConfirmationMiddleware */
	private $middleware;
	/** @var PasswordConfirmationMiddlewareController */
	private $controller;
	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	protected function setUp(): void {
		$this->reflector = new ControllerMethodReflector();
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->user = $this->createMock(IUser::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->controller = new PasswordConfirmationMiddlewareController(
			'test',
			$this->createMock(IRequest::class)
		);

		$this->middleware = new PasswordConfirmationMiddleware(
			$this->reflector,
			$this->session,
			$this->userSession,
			$this->timeFactory
		);
	}

	public function testNoAnnotationNorAttribute() {
		$this->reflector->reflect($this->controller, __FUNCTION__);
		$this->session->expects($this->never())
			->method($this->anything());
		$this->userSession->expects($this->never())
			->method($this->anything());

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testDifferentAnnotation() {
		$this->reflector->reflect($this->controller, __FUNCTION__);
		$this->session->expects($this->never())
			->method($this->anything());
		$this->userSession->expects($this->never())
			->method($this->anything());

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testAnnotation($backend, $lastConfirm, $currentTime, $exception) {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')
			->willReturn($backend);
		$this->userSession->method('getUser')
			->willReturn($this->user);

		$this->session->method('get')
			->with('last-password-confirm')
			->willReturn($lastConfirm);

		$this->timeFactory->method('getTime')
			->willReturn($currentTime);

		$thrown = false;
		try {
			$this->middleware->beforeController($this->controller, __FUNCTION__);
		} catch (NotConfirmedException $e) {
			$thrown = true;
		}

		$this->assertSame($exception, $thrown);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testAttribute($backend, $lastConfirm, $currentTime, $exception) {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')
			->willReturn($backend);
		$this->userSession->method('getUser')
			->willReturn($this->user);

		$this->session->method('get')
			->with('last-password-confirm')
			->willReturn($lastConfirm);

		$this->timeFactory->method('getTime')
			->willReturn($currentTime);

		$thrown = false;
		try {
			$this->middleware->beforeController($this->controller, __FUNCTION__);
		} catch (NotConfirmedException $e) {
			$thrown = true;
		}

		$this->assertSame($exception, $thrown);
	}

	public function dataProvider() {
		return [
			['foo', 2000, 4000, true],
			['foo', 2000, 3000, false],
			['user_saml', 2000, 4000, false],
			['user_saml', 2000, 3000, false],
			['foo', 2000, 3815, false],
			['foo', 2000, 3816, true],
		];
	}
}
