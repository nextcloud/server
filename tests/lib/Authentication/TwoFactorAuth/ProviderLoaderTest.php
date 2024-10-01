<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Authentication\TwoFactorAuth;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCP\App\IAppManager;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderLoaderTest extends TestCase {
	/** @var IAppManager|MockObject */
	private $appManager;

	/** @var IUser|MockObject */
	private $user;

	/** @var RegistrationContext|MockObject */
	private $registrationContext;

	/** @var ProviderLoader */
	private $loader;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->user = $this->createMock(IUser::class);

		$this->registrationContext = $this->createMock(RegistrationContext::class);
		$coordinator = $this->createMock(Coordinator::class);
		$coordinator->method('getRegistrationContext')
			->willReturn($this->registrationContext);

		$this->loader = new ProviderLoader($this->appManager, $coordinator);
	}


	public function testFailHardIfProviderCanNotBeLoaded(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Could not load two-factor auth provider \\OCA\\MyFaulty2faApp\\DoesNotExist');

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($this->user)
			->willReturn(['mail', 'twofactor_totp']);
		$this->appManager
			->method('getAppInfo')
			->willReturnMap([
				['mail', false, null, []],
				['twofactor_totp', false, null, [
					'two-factor-providers' => [
						'\\OCA\\MyFaulty2faApp\\DoesNotExist',
					],
				]],
			]);

		$this->loader->getProviders($this->user);
	}

	public function testGetProviders(): void {
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

	public function testGetProvidersBootstrap(): void {
		$provider = $this->createMock(IProvider::class);
		$provider->method('getId')->willReturn('test');

		\OC::$server->registerService('\\OCA\\TwoFactorTest\\Provider', function () use ($provider) {
			return $provider;
		});

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($this->user)
			->willReturn([]);

		$this->registrationContext->method('getTwoFactorProviders')
			->willReturn([
				new ServiceRegistration('twofactor_test', '\\OCA\\TwoFactorTest\\Provider')
			]);

		$providers = $this->loader->getProviders($this->user);

		$this->assertCount(1, $providers);
		$this->assertArrayHasKey('test', $providers);
		$this->assertSame($provider, $providers['test']);
	}
}
