<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace Test\Core\Middleware;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Middleware\TwoFactorMiddleware;
use OC\AppFramework\Http\Request;
use OC\User\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IConfig;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class TwoFactorMiddlewareTest extends TestCase {

	private $twoFactorManager;
	private $userSession;
	private $session;
	private $urlGenerator;
	private $reflector;
	private $request;

	/** @var TwoFactorMiddleware */
	private $middleware;

	/** @var Controller */
	private $controller;

	protected function setUp() {
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
		$this->request = new Request(
			[
				'server' => [
					'REQUEST_URI' => 'test/url'
				]
			],
			$this->createMock(ISecureRandom::class),
			$this->createMock(IConfig::class)
		);

		$this->middleware = new TwoFactorMiddleware($this->twoFactorManager, $this->userSession, $this->session, $this->urlGenerator, $this->reflector, $this->request);
		$this->controller = $this->createMock(Controller::class);
	}

	public function testBeforeControllerNotLoggedIn() {
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->with('PublicPage')
			->will($this->returnValue(false));
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(false));

		$this->userSession->expects($this->never())
			->method('getUser');

		$this->middleware->beforeController($this->controller, 'index');
	}

	public function testBeforeControllerPublicPage() {
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->with('PublicPage')
			->will($this->returnValue(true));
		$this->userSession->expects($this->never())
			->method('isLoggedIn');

		$this->middleware->beforeController($this->controller, 'create');
	}

	public function testBeforeControllerNoTwoFactorCheckNeeded() {
		$user = $this->createMock(IUser::class);

		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->with('PublicPage')
			->will($this->returnValue(false));
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(false));

		$this->middleware->beforeController($this->controller, 'index');
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\TwoFactorAuthRequiredException
	 */
	public function testBeforeControllerTwoFactorAuthRequired() {
		$user = $this->createMock(IUser::class);

		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->with('PublicPage')
			->will($this->returnValue(false));
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(true));
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->with($user)
			->will($this->returnValue(true));

		$this->middleware->beforeController($this->controller, 'index');
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\UserAlreadyLoggedInException
	 */
	public function testBeforeControllerUserAlreadyLoggedIn() {
		$user = $this->createMock(IUser::class);

		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->with('PublicPage')
			->will($this->returnValue(false));
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->will($this->returnValue(true));
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->with($user)
			->will($this->returnValue(true));
		$this->twoFactorManager->expects($this->once())
			->method('needsSecondFactor')
			->with($user)
			->will($this->returnValue(false));

		$twoFactorChallengeController = $this->getMockBuilder('\OC\Core\Controller\TwoFactorChallengeController')
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->beforeController($twoFactorChallengeController, 'index');
	}

	public function testAfterExceptionTwoFactorAuthRequired() {
		$ex = new \OC\Authentication\Exceptions\TwoFactorAuthRequiredException();

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge')
			->will($this->returnValue('test/url'));
		$expected = new \OCP\AppFramework\Http\RedirectResponse('test/url');

		$this->assertEquals($expected, $this->middleware->afterException($this->controller, 'index', $ex));
	}

	public function testAfterException() {
		$ex = new \OC\Authentication\Exceptions\UserAlreadyLoggedInException();

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('redirect/url'));
		$expected = new \OCP\AppFramework\Http\RedirectResponse('redirect/url');

		$this->assertEquals($expected, $this->middleware->afterException($this->controller, 'index', $ex));
	}

}
