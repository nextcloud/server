<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\ProviderManager;
use OC\Core\Command\TwoFactorAuth\Disable;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class DisableTest extends TestCase {
	/** @var ProviderManager|MockObject */
	private $providerManager;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var CommandTester */
	private $command;

	protected function setUp(): void {
		parent::setUp();

		$this->providerManager = $this->createMock(ProviderManager::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$cmd = new Disable($this->providerManager, $this->userManager);
		$this->command = new CommandTester($cmd);
	}

	public function testInvalidUID(): void {
		$this->userManager->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn(null);

		$rc = $this->command->execute([
			'uid' => 'nope',
			'provider_id' => 'nope',
		]);

		$this->assertEquals(1, $rc);
		$this->assertStringContainsString('Invalid UID', $this->command->getDisplay());
	}

	public function testEnableNotSupported(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('ricky')
			->willReturn($user);
		$this->providerManager->expects($this->once())
			->method('tryDisableProviderFor')
			->with('totp', $user)
			->willReturn(false);

		$rc = $this->command->execute([
			'uid' => 'ricky',
			'provider_id' => 'totp',
		]);

		$this->assertEquals(2, $rc);
		$this->assertStringContainsString('The provider does not support this operation', $this->command->getDisplay());
	}

	public function testEnabled(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('ricky')
			->willReturn($user);
		$this->providerManager->expects($this->once())
			->method('tryDisableProviderFor')
			->with('totp', $user)
			->willReturn(true);

		$rc = $this->command->execute([
			'uid' => 'ricky',
			'provider_id' => 'totp',
		]);

		$this->assertEquals(0, $rc);
		$this->assertStringContainsString('Two-factor provider totp disabled for user ricky', $this->command->getDisplay());
	}
}
