<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Core\Command\TwoFactorAuth;

use OC\Core\Command\TwoFactorAuth\State;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class StateTest extends TestCase {
	/** @var IRegistry|MockObject */
	private $registry;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var CommandTester|MockObject */
	private $cmd;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(IRegistry::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$cmd = new State($this->registry, $this->userManager);
		$this->cmd = new CommandTester($cmd);
	}

	public function testWrongUID(): void {
		$this->cmd->execute([
			'uid' => 'nope',
		]);

		$output = $this->cmd->getDisplay();
		$this->assertStringContainsString('Invalid UID', $output);
	}

	public function testStateNoProvidersActive(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('eldora')
			->willReturn($user);
		$states = [
			'u2f' => false,
			'totp' => false,
		];
		$this->registry->expects($this->once())
			->method('getProviderStates')
			->with($user)
			->willReturn($states);

		$this->cmd->execute([
			'uid' => 'eldora',
		]);

		$output = $this->cmd->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is not enabled for user eldora', $output);
	}

	public function testStateOneProviderActive(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('mohamed')
			->willReturn($user);
		$states = [
			'u2f' => true,
			'totp' => false,
		];
		$this->registry->expects($this->once())
			->method('getProviderStates')
			->with($user)
			->willReturn($states);

		$this->cmd->execute([
			'uid' => 'mohamed',
		]);

		$output = $this->cmd->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is enabled for user mohamed', $output);
	}
}
