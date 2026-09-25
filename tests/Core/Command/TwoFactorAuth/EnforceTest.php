<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Core\Command\TwoFactorAuth\Enforce;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class EnforceTest extends TestCase {
	/** @var CommandTester */
	private $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$command = $this->createInstanceWithMocks(Enforce::class);

		$this->command = new CommandTester($command);
	}

	public function testEnforce(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('setState')
			->with($this->equalTo(new EnforcementState(true)));
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('getState')
			->willReturn(new EnforcementState(true));

		$rc = $this->command->execute([
			'--on' => true,
		]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is enforced for all users', $display);
	}

	public function testEnforceForOneGroup(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('setState')
			->with($this->equalTo(new EnforcementState(true, ['twofactorers'])));
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('getState')
			->willReturn(new EnforcementState(true, ['twofactorers']));

		$rc = $this->command->execute([
			'--on' => true,
			'--group' => ['twofactorers'],
		]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is enforced for members of the group(s) twofactorers', $display);
	}

	public function testEnforceForAllExceptOneGroup(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('setState')
			->with($this->equalTo(new EnforcementState(true, [], ['yoloers'])));
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('getState')
			->willReturn(new EnforcementState(true, [], ['yoloers']));

		$rc = $this->command->execute([
			'--on' => true,
			'--exclude' => ['yoloers'],
		]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is enforced for all users, except members of yoloers', $display);
	}

	public function testDisableEnforced(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('setState')
			->with(new EnforcementState(false));
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('getState')
			->willReturn(new EnforcementState(false));

		$rc = $this->command->execute([
			'--off' => true,
		]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is not enforced', $display);
	}

	public function testCurrentStateEnabled(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('getState')
			->willReturn(new EnforcementState(true));

		$rc = $this->command->execute([]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is enforced for all users', $display);
	}

	public function testCurrentStateDisabled(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('getState')
			->willReturn(new EnforcementState(false));

		$rc = $this->command->execute([]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('Two-factor authentication is not enforced', $display);
	}
}
