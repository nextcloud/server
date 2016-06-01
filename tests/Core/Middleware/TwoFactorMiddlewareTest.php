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

use OC\Core\Middleware\TwoFactorMiddleware;
use OC\AppFramework\Http\Request;
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

	protected function setUp() {
		parent::setUp();

		$this->twoFactorManager = $this->getMockBuilder('\OC\Authentication\TwoFactorAuth\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->getMock('\OCP\ISession');
		$this->urlGenerator = $this->getMock('\OCP\IURLGenerator');
		$this->reflector = $this->getMock('\OCP\AppFramework\Utility\IControllerMethodReflector');
		$this->request = new Request(
			[
				'server' => [
					'REQUEST_URI' => 'test/url'
				]
			],
			$this->getMock('\OCP\Security\ISecureRandom'),
			$this->getMock('\OCP\IConfig')
		);

		$this->middleware = new TwoFactorMiddleware($this->twoFactorManager, $this->userSession, $this->session, $this->urlGenerator, $this->reflector, $this->request);
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

		$this->middleware->beforeController(null, 'index');
	}

	public function testBeforeControllerPublicPage() {
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->with('PublicPage')
			->will($this->returnValue(true));
		$this->userSession->expects($this->never())
			->method('isLoggedIn');

		$this->middleware->beforeController(null, 'create');
	}

	public function testBeforeControllerNoTwoFactorCheckNeeded() {
		$user = $this->getMock('\OCP\IUser');

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

		$this->middleware->beforeController(null, 'index');
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\TwoFactorAuthRequiredException
	 */
	public function testBeforeControllerTwoFactorAuthRequired() {
		$user = $this->getMock('\OCP\IUser');

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
			->will($this->returnValue(true));

		$this->middleware->beforeController(null, 'index');
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\UserAlreadyLoggedInException
	 */
	public function testBeforeControllerUserAlreadyLoggedIn() {
		$user = $this->getMock('\OCP\IUser');

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

		$this->assertEquals($expected, $this->middleware->afterException(null, 'index', $ex));
	}

	public function testAfterException() {
		$ex = new \OC\Authentication\Exceptions\UserAlreadyLoggedInException();

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('redirect/url'));
		$expected = new \OCP\AppFramework\Http\RedirectResponse('redirect/url');

		$this->assertEquals($expected, $this->middleware->afterException(null, 'index', $ex));
	}

}
