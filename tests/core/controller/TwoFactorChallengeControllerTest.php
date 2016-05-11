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

namespace OC\Core\Controller;

use Test\TestCase;

class TwoFactorChallengeControllerTest extends TestCase {

	private $request;
	private $twoFactorManager;
	private $userSession;
	private $session;
	private $urlGenerator;

	/** TwoFactorChallengeController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMock('\OCP\IRequest');
		$this->twoFactorManager = $this->getMockBuilder('\OC\Authentication\TwoFactorAuth\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMock('\OCP\IUserSession');
		$this->session = $this->getMock('\OCP\ISession');
		$this->urlGenerator = $this->getMock('\OCP\IURLGenerator');

		$this->controller = new TwoFactorChallengeController(
			'core', $this->request, $this->twoFactorManager, $this->userSession, $this->session, $this->urlGenerator
		);
	}

	public function testSelectChallenge() {
		$user = $this->getMock('\OCP\IUser');
		$providers = [
			'prov1',
			'prov2',
		];

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProviders')
			->with($user)
			->will($this->returnValue($providers));

		$expected = new \OCP\AppFramework\Http\TemplateResponse('core', 'twofactorselectchallenge', [
			'providers' => $providers,
			], 'guest');

		$this->assertEquals($expected, $this->controller->selectChallenge());
	}

	public function testShowChallenge() {
		$user = $this->getMock('\OCP\IUser');
		$provider = $this->getMockBuilder('\OCP\Authentication\TwoFactorAuth\IProvider')
			->disableOriginalConstructor()
			->getMock();
		$tmpl = $this->getMockBuilder('\OCP\Template')
			->disableOriginalConstructor()
			->getMock();

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProvider')
			->with($user, 'myprovider')
			->will($this->returnValue($provider));

		$this->session->expects($this->once())
			->method('exists')
			->with('two_factor_auth_error')
			->will($this->returnValue(true));
		$this->session->expects($this->once())
			->method('remove')
			->with('two_factor_auth_error');
		$provider->expects($this->once())
			->method('getTemplate')
			->with($user)
			->will($this->returnValue($tmpl));
		$tmpl->expects($this->once())
			->method('fetchPage')
			->will($this->returnValue('<html/>'));

		$expected = new \OCP\AppFramework\Http\TemplateResponse('core', 'twofactorshowchallenge', [
			'error' => true,
			'provider' => $provider,
			'template' => '<html/>',
			], 'guest');

		$this->assertEquals($expected, $this->controller->showChallenge('myprovider'));
	}

	public function testShowInvalidChallenge() {
		$user = $this->getMock('\OCP\IUser');

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProvider')
			->with($user, 'myprovider')
			->will($this->returnValue(null));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge')
			->will($this->returnValue('select/challenge/url'));

		$expected = new \OCP\AppFramework\Http\RedirectResponse('select/challenge/url');

		$this->assertEquals($expected, $this->controller->showChallenge('myprovider'));
	}

	public function testSolveChallenge() {
		$user = $this->getMock('\OCP\IUser');
		$provider = $this->getMockBuilder('\OCP\Authentication\TwoFactorAuth\IProvider')
			->disableOriginalConstructor()
			->getMock();

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProvider')
			->with($user, 'myprovider')
			->will($this->returnValue($provider));

		$this->twoFactorManager->expects($this->once())
			->method('verifyChallenge')
			->with('myprovider', $user, 'token')
			->will($this->returnValue(true));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('files/index/url'));

		$expected = new \OCP\AppFramework\Http\RedirectResponse('files/index/url');
		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token'));
	}

	public function testSolveChallengeInvalidProvider() {
		$user = $this->getMock('\OCP\IUser');

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProvider')
			->with($user, 'myprovider')
			->will($this->returnValue(null));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge')
			->will($this->returnValue('select/challenge/url'));

		$expected = new \OCP\AppFramework\Http\RedirectResponse('select/challenge/url');

		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token'));
	}

	public function testSolveInvalidChallenge() {
		$user = $this->getMock('\OCP\IUser');
		$provider = $this->getMockBuilder('\OCP\Authentication\TwoFactorAuth\IProvider')
			->disableOriginalConstructor()
			->getMock();

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProvider')
			->with($user, 'myprovider')
			->will($this->returnValue($provider));

		$this->twoFactorManager->expects($this->once())
			->method('verifyChallenge')
			->with('myprovider', $user, 'token')
			->will($this->returnValue(false));
		$this->session->expects($this->once())
			->method('set')
			->with('two_factor_auth_error', true);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.showChallenge', [
				'challengeProviderId' => 'myprovider',
			])
			->will($this->returnValue('files/index/url'));
		$provider->expects($this->once())
			->method('getId')
			->will($this->returnValue('myprovider'));

		$expected = new \OCP\AppFramework\Http\RedirectResponse('files/index/url');
		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token'));
	}

}
