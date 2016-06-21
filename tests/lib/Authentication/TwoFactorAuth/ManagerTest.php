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

namespace Test\Authentication\TwoFactorAuth;

use Test\TestCase;
use OC\Authentication\TwoFactorAuth\Manager;

class ManagerTest extends TestCase {

	/** @var \OCP\IUser|\PHPUnit_Framework_MockObject_MockObject */
	private $user;

	/** @var \OC\App\AppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	/** @var \OCP\ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;

	/** @var Manager */
	private $manager;

	/** @var \OCP\IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var \OCP\Authentication\TwoFactorAuth\IProvider|\PHPUnit_Framework_MockObject_MockObject */
	private $fakeProvider;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getMock('\OCP\IUser');
		$this->appManager = $this->getMockBuilder('\OC\App\AppManager')
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->getMock('\OCP\ISession');
		$this->config = $this->getMock('\OCP\IConfig');

		$this->manager = $this->getMockBuilder('\OC\Authentication\TwoFactorAuth\Manager')
			->setConstructorArgs([$this->appManager, $this->session, $this->config])
			->setMethods(['loadTwoFactorApp']) // Do not actually load the apps
			->getMock();

		$this->fakeProvider = $this->getMock('\OCP\Authentication\TwoFactorAuth\IProvider');
		$this->fakeProvider->expects($this->any())
			->method('getId')
			->will($this->returnValue('email'));
		$this->fakeProvider->expects($this->any())
			->method('isTwoFactorAuthEnabledForUser')
			->will($this->returnValue(true));
		\OC::$server->registerService('\OCA\MyCustom2faApp\FakeProvider', function() {
			return $this->fakeProvider;
		});
	}

	private function prepareProviders() {
		$this->appManager->expects($this->any())
			->method('getEnabledAppsForUser')
			->with($this->user)
			->will($this->returnValue(['mycustom2faapp']));

		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('mycustom2faapp')
			->will($this->returnValue([
					'two-factor-providers' => [
						'\OCA\MyCustom2faApp\FakeProvider',
					],
		]));

		$this->manager->expects($this->once())
			->method('loadTwoFactorApp')
			->with('mycustom2faapp');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Could not load two-factor auth provider \OCA\MyFaulty2faApp\DoesNotExist
	 */
	public function testFailHardIfProviderCanNotBeLoaded() {
		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($this->user)
			->will($this->returnValue(['faulty2faapp']));
		$this->manager->expects($this->once())
			->method('loadTwoFactorApp')
			->with('faulty2faapp');

		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('faulty2faapp')
			->will($this->returnValue([
					'two-factor-providers' => [
						'\OCA\MyFaulty2faApp\DoesNotExist',
					],
		]));

		$this->manager->getProviders($this->user);
	}

	public function testIsTwoFactorAuthenticated() {
		$this->prepareProviders();

		$this->user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('user123'));
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user123', 'core', 'two_factor_auth_disabled', 0)
			->will($this->returnValue(0));

		$this->assertTrue($this->manager->isTwoFactorAuthenticated($this->user));
	}

	public function testGetProvider() {
		$this->prepareProviders();

		$this->assertSame($this->fakeProvider, $this->manager->getProvider($this->user, 'email'));
	}

	public function testGetInvalidProvider() {
		$this->prepareProviders();

		$this->assertSame(null, $this->manager->getProvider($this->user, 'nonexistent'));
	}

	public function testGetProviders() {
		$this->prepareProviders();
		$expectedProviders = [
			'email' => $this->fakeProvider,
		];

		$this->assertEquals($expectedProviders, $this->manager->getProviders($this->user));
	}

	public function testVerifyChallenge() {
		$this->prepareProviders();

		$challenge = 'passme';
		$this->fakeProvider->expects($this->once())
			->method('verifyChallenge')
			->with($this->user, $challenge)
			->will($this->returnValue(true));
		$this->session->expects($this->once())
			->method('remove')
			->with('two_factor_auth_uid');

		$this->assertEquals(true, $this->manager->verifyChallenge('email', $this->user, $challenge));
	}

	public function testVerifyChallengeInvalidProviderId() {
		$this->prepareProviders();

		$challenge = 'passme';
		$this->fakeProvider->expects($this->never())
			->method('verifyChallenge')
			->with($this->user, $challenge);
		$this->session->expects($this->never())
			->method('remove');

		$this->assertEquals(false, $this->manager->verifyChallenge('dontexist', $this->user, $challenge));
	}

	public function testVerifyInvalidChallenge() {
		$this->prepareProviders();

		$challenge = 'dontpassme';
		$this->fakeProvider->expects($this->once())
			->method('verifyChallenge')
			->with($this->user, $challenge)
			->will($this->returnValue(false));
		$this->session->expects($this->never())
			->method('remove');

		$this->assertEquals(false, $this->manager->verifyChallenge('email', $this->user, $challenge));
	}

	public function testNeedsSecondFactor() {
		$this->session->expects($this->once())
			->method('exists')
			->with('two_factor_auth_uid')
			->will($this->returnValue(false));

		$this->assertEquals(false, $this->manager->needsSecondFactor());
	}

	public function testPrepareTwoFactorLogin() {
		$this->user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('ferdinand'));

		$this->session->expects($this->once())
			->method('set')
			->with('two_factor_auth_uid', 'ferdinand');

		$this->manager->prepareTwoFactorLogin($this->user);
	}

}
