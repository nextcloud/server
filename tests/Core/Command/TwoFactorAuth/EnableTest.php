<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\ProviderManager;
use OC\Core\Command\TwoFactorAuth\Enable;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class EnableTest extends TestCase {
	/** @var CommandTester */
	private $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$cmd = $this->createInstanceWithMocks(Enable::class);
		$this->command = new CommandTester($cmd);
	}

	public function testInvalidUID(): void {
		$this->mocks[IUserManager::class]->expects($this->once())
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
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('belle')
			->willReturn($user);
		$this->mocks[ProviderManager::class]->expects($this->once())
			->method('tryEnableProviderFor')
			->with('totp', $user)
			->willReturn(false);

		$rc = $this->command->execute([
			'uid' => 'belle',
			'provider_id' => 'totp',
		]);

		$this->assertEquals(2, $rc);
		$this->assertStringContainsString('The provider does not support this operation', $this->command->getDisplay());
	}

	public function testEnabled(): void {
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('belle')
			->willReturn($user);
		$this->mocks[ProviderManager::class]->expects($this->once())
			->method('tryEnableProviderFor')
			->with('totp', $user)
			->willReturn(true);

		$rc = $this->command->execute([
			'uid' => 'belle',
			'provider_id' => 'totp',
		]);

		$this->assertEquals(0, $rc);
		$this->assertStringContainsString('Two-factor provider totp enabled for user belle', $this->command->getDisplay());
	}
}
