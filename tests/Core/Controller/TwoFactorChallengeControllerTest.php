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

namespace Test\Core\Controller;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\Authentication\TwoFactorAuth\ProviderSet;
use OC\Core\Controller\TwoFactorChallengeController;
use OC_Util;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorException;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Template;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class TwoFactorChallengeControllerTest extends TestCase {

	/** @var IRequest|PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var Manager|PHPUnit_Framework_MockObject_MockObject */
	private $twoFactorManager;

	/** @var IUserSession|PHPUnit_Framework_MockObject_MockObject */
	private $userSession;

	/** @var ISession|PHPUnit_Framework_MockObject_MockObject */
	private $session;

	/** @var IURLGenerator|PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	/** @var TwoFactorChallengeController|PHPUnit_Framework_MockObject_MockObject */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->twoFactorManager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->session = $this->createMock(ISession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->controller = $this->getMockBuilder(TwoFactorChallengeController::class)
			->setConstructorArgs([
				'core',
				$this->request,
				$this->twoFactorManager,
				$this->userSession,
				$this->session,
				$this->urlGenerator,
			])
			->setMethods(['getLogoutUrl'])
			->getMock();
		$this->controller->expects($this->any())
			->method('getLogoutUrl')
			->willReturn('logoutAttribute');
	}

	public function testSelectChallenge() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$p1 = $this->createMock(IProvider::class);
		$p1->method('getId')->willReturn('p1');
		$backupProvider = $this->createMock(IProvider::class);
		$backupProvider->method('getId')->willReturn('backup_codes');
		$providerSet = new ProviderSet([$p1, $backupProvider], true);

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->with($user)
			->will($this->returnValue($providerSet));

		$expected = new StandaloneTemplateResponse('core', 'twofactorselectchallenge', [
			'providers' => [
				$p1,
			],
			'providerMissing' => true,
			'backupProvider' => $backupProvider,
			'redirect_url' => '/some/url',
			'logout_url' => 'logoutAttribute',
			], 'guest');

		$this->assertEquals($expected, $this->controller->selectChallenge('/some/url'));
	}

	public function testShowChallenge() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$provider->method('getId')->willReturn('myprovider');
		$backupProvider = $this->createMock(IProvider::class);
		$backupProvider->method('getId')->willReturn('backup_codes');
		$tmpl = $this->createMock(Template::class);
		$providerSet = new ProviderSet([$provider, $backupProvider], true);

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->with($user)
			->will($this->returnValue($providerSet));
		$provider->expects($this->once())
			->method('getId')
			->will($this->returnValue('u2f'));
		$backupProvider->expects($this->once())
			->method('getId')
			->will($this->returnValue('backup_codes'));

		$this->session->expects($this->once())
			->method('exists')
			->with('two_factor_auth_error')
			->will($this->returnValue(true));
		$this->session->expects($this->exactly(2))
			->method('remove')
			->with($this->logicalOr($this->equalTo('two_factor_auth_error'), $this->equalTo('two_factor_auth_error_message')));
		$provider->expects($this->once())
			->method('getTemplate')
			->with($user)
			->will($this->returnValue($tmpl));
		$tmpl->expects($this->once())
			->method('fetchPage')
			->will($this->returnValue('<html/>'));

		$expected = new StandaloneTemplateResponse('core', 'twofactorshowchallenge', [
			'error' => true,
			'provider' => $provider,
			'backupProvider' => $backupProvider,
			'logout_url' => 'logoutAttribute',
			'template' => '<html/>',
			'redirect_url' => '/re/dir/ect/url',
			'error_message' => null,
			], 'guest');

		$this->assertEquals($expected, $this->controller->showChallenge('myprovider', '/re/dir/ect/url'));
	}

	public function testShowInvalidChallenge() {
		$user = $this->createMock(IUser::class);
		$providerSet = new ProviderSet([], false);

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->with($user)
			->will($this->returnValue($providerSet));
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge')
			->will($this->returnValue('select/challenge/url'));

		$expected = new RedirectResponse('select/challenge/url');

		$this->assertEquals($expected, $this->controller->showChallenge('myprovider', 'redirect/url'));
	}

	public function testSolveChallenge() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);

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

		$expected = new RedirectResponse(OC_Util::getDefaultPageUrl());
		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token'));
	}

	public function testSolveValidChallengeAndRedirect() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);

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
			->willReturn(true);
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('redirect url')
			->willReturn('redirect/url');

		$expected = new RedirectResponse('redirect/url');
		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token', 'redirect%20url'));
	}

	public function testSolveChallengeInvalidProvider() {
		$user = $this->getMockBuilder(IUser::class)->getMock();

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

		$expected = new RedirectResponse('select/challenge/url');

		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token'));
	}

	public function testSolveInvalidChallenge() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);

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
				'redirect_url' => '/url',
			])
			->will($this->returnValue('files/index/url'));
		$provider->expects($this->once())
			->method('getId')
			->will($this->returnValue('myprovider'));

		$expected = new RedirectResponse('files/index/url');
		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token', '/url'));
	}

	public function testSolveChallengeTwoFactorException() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$exception = new TwoFactorException("2FA failed");

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
			->will($this->throwException($exception));
		$this->session->expects($this->at(0))
			->method('set')
			->with('two_factor_auth_error_message', "2FA failed");
		$this->session->expects($this->at(1))
			->method('set')
			->with('two_factor_auth_error', true);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.showChallenge', [
				'challengeProviderId' => 'myprovider',
				'redirect_url' => '/url',
			])
			->will($this->returnValue('files/index/url'));
		$provider->expects($this->once())
			->method('getId')
			->will($this->returnValue('myprovider'));

		$expected = new RedirectResponse('files/index/url');
		$this->assertEquals($expected, $this->controller->solveChallenge('myprovider', 'token', '/url'));
	}

}
