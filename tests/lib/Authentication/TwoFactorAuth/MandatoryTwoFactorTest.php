<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MandatoryTwoFactorTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var IGroupManager|MockObject */
	private $groupManager;

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->mandatoryTwoFactor = new MandatoryTwoFactor($this->config, $this->groupManager);
	}

	public function testIsNotEnforced(): void {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false', 'false'],
				['twofactor_enforced_groups', [], []],
				['twofactor_enforced_excluded_groups', [], []],
			]);

		$state = $this->mandatoryTwoFactor->getState();

		$this->assertFalse($state->isEnforced());
	}

	public function testIsEnforced(): void {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false', 'true'],
				['twofactor_enforced_groups', [], []],
				['twofactor_enforced_excluded_groups', [], []],
			]);

		$state = $this->mandatoryTwoFactor->getState();

		$this->assertTrue($state->isEnforced());
	}

	public function testIsNotEnforcedForAnybody(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false', 'false'],
				['twofactor_enforced_groups', [], []],
				['twofactor_enforced_excluded_groups', [], []],
			]);

		$isEnforced = $this->mandatoryTwoFactor->isEnforcedFor($user);

		$this->assertFalse($isEnforced);
	}

	public function testIsEnforcedForAGroupMember(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false', 'true'],
				['twofactor_enforced_groups', [], ['twofactorers']],
				['twofactor_enforced_excluded_groups', [], []],
			]);
		$this->groupManager->method('isInGroup')
			->willReturnCallback(function ($user, $group) {
				return $user === 'user123' && $group === 'twofactorers';
			});

		$isEnforced = $this->mandatoryTwoFactor->isEnforcedFor($user);

		$this->assertTrue($isEnforced);
	}

	public function testIsEnforcedForOtherGroups(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false', 'true'],
				['twofactor_enforced_groups', [], ['twofactorers']],
				['twofactor_enforced_excluded_groups', [], []],
			]);
		$this->groupManager->method('isInGroup')
			->willReturn(false);

		$isEnforced = $this->mandatoryTwoFactor->isEnforcedFor($user);

		$this->assertFalse($isEnforced);
	}

	public function testIsEnforcedButMemberOfExcludedGroup(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false', 'true'],
				['twofactor_enforced_groups', [], []],
				['twofactor_enforced_excluded_groups', [], ['yoloers']],
			]);
		$this->groupManager->method('isInGroup')
			->willReturnCallback(function ($user, $group) {
				return $user === 'user123' && $group === 'yoloers';
			});

		$isEnforced = $this->mandatoryTwoFactor->isEnforcedFor($user);

		$this->assertFalse($isEnforced);
	}

	public function testSetEnforced(): void {
		$this->config
			->expects($this->exactly(3))
			->method('setSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'true'],
				['twofactor_enforced_groups', []],
				['twofactor_enforced_excluded_groups', []],
			]);

		$this->mandatoryTwoFactor->setState(new EnforcementState(true));
	}

	public function testSetEnforcedForGroups(): void {
		$this->config
			->expects($this->exactly(3))
			->method('setSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'true'],
				['twofactor_enforced_groups', ['twofactorers']],
				['twofactor_enforced_excluded_groups', ['yoloers']],
			]);

		$this->mandatoryTwoFactor->setState(new EnforcementState(true, ['twofactorers'], ['yoloers']));
	}

	public function testSetNotEnforced(): void {
		$this->config
			->expects($this->exactly(3))
			->method('setSystemValue')
			->willReturnMap([
				['twofactor_enforced', 'false'],
				['twofactor_enforced_groups', []],
				['twofactor_enforced_excluded_groups', []],
			]);

		$this->mandatoryTwoFactor->setState(new EnforcementState(false));
	}
}
