<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Authentication\TwoFactorAuth;

use OC\Authentication\Exceptions\InvalidProviderException;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OC\Authentication\TwoFactorAuth\ProviderManager;
use OCP\Authentication\TwoFactorAuth\IActivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IDeactivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderManagerTest extends TestCase {
	/** @var ProviderLoader|MockObject */
	private $providerLoader;

	/** @var IRegistry|MockObject */
	private $registry;

	/** @var ProviderManager */
	private $providerManager;

	protected function setUp(): void {
		parent::setUp();

		$this->providerLoader = $this->createMock(ProviderLoader::class);
		$this->registry = $this->createMock(IRegistry::class);

		$this->providerManager = new ProviderManager(
			$this->providerLoader,
			$this->registry
		);
	}

	
	public function testTryEnableInvalidProvider(): void {
		$this->expectException(InvalidProviderException::class);

		$user = $this->createMock(IUser::class);
		$this->providerManager->tryEnableProviderFor('none', $user);
	}

	public function testTryEnableUnsupportedProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$this->providerLoader->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([
				'u2f' => $provider,
			]);
		$this->registry->expects($this->never())
			->method('enableProviderFor');

		$res = $this->providerManager->tryEnableProviderFor('u2f', $user);

		$this->assertFalse($res);
	}

	public function testTryEnableProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IActivatableByAdmin::class);
		$this->providerLoader->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([
				'u2f' => $provider,
			]);
		$provider->expects($this->once())
			->method('enableFor')
			->with($user);
		$this->registry->expects($this->once())
			->method('enableProviderFor')
			->with($provider, $user);

		$res = $this->providerManager->tryEnableProviderFor('u2f', $user);

		$this->assertTrue($res);
	}

	
	public function testTryDisableInvalidProvider(): void {
		$this->expectException(InvalidProviderException::class);

		$user = $this->createMock(IUser::class);
		$this->providerManager->tryDisableProviderFor('none', $user);
	}

	public function testTryDisableUnsupportedProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$this->providerLoader->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([
				'u2f' => $provider,
			]);
		$this->registry->expects($this->never())
			->method('disableProviderFor');

		$res = $this->providerManager->tryDisableProviderFor('u2f', $user);

		$this->assertFalse($res);
	}

	public function testTryDisableProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IDeactivatableByAdmin::class);
		$this->providerLoader->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([
				'u2f' => $provider,
			]);
		$provider->expects($this->once())
			->method('disableFor')
			->with($user);
		$this->registry->expects($this->once())
			->method('disableProviderFor')
			->with($provider, $user);

		$res = $this->providerManager->tryDisableProviderFor('u2f', $user);

		$this->assertTrue($res);
	}
}
