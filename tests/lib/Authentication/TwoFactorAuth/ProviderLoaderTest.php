<?php

/**
 * Created by PhpStorm.
 * User: christoph
 * Date: 04.06.18
 * Time: 13:29
 */

namespace lib\Authentication\TwoFactorAuth;

use Exception;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCP\App\IAppManager;
use OCP\Authentication\TwoFactorAuth\IProvider;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class ProviderLoaderTest extends TestCase {

	/** @var IAppManager|PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	/** @var IUser|PHPUnit_Framework_MockObject_MockObject */
	private $user;

	/** @var ProviderLoader */
	private $loader;

	protected function setUp() {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->user = $this->createMock(\OCP\IUser::class);

		$this->loader = new ProviderLoader($this->appManager);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Could not load two-factor auth provider \OCA\MyFaulty2faApp\DoesNotExist
	 */
	public function testFailHardIfProviderCanNotBeLoaded() {
		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($this->user)
			->willReturn(['mail', 'twofactor_totp']);
		$this->appManager
			->method('getAppInfo')
			->will($this->returnValueMap([
				['mail', false, null, []],
				['twofactor_totp', false, null, [
					'two-factor-providers' => [
						'\\OCA\\MyFaulty2faApp\\DoesNotExist',
					],
				]],
			]));

		$this->loader->getProviders($this->user);
	}

	public function testGetProviders() {
		$provider = $this->createMock(IProvider::class);
		$provider->method('getId')->willReturn('test');
		\OC::$server->registerService('\\OCA\\TwoFactorTest\\Provider', function () use ($provider) {
			return $provider;
		});
		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($this->user)
			->willReturn(['twofactor_test']);
		$this->appManager
			->method('getAppInfo')
			->with('twofactor_test')
			->willReturn(['two-factor-providers' => [
				'\\OCA\\TwoFactorTest\\Provider',
			]]);

		$providers = $this->loader->getProviders($this->user);

		$this->assertCount(1, $providers);
		$this->assertArrayHasKey('test', $providers);
		$this->assertSame($provider, $providers['test']);
	}

}
