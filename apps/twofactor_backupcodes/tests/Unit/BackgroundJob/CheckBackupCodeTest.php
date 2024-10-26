<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\BackgroundJob;

use OC\Authentication\TwoFactorAuth\Manager;
use OCA\TwoFactorBackupCodes\BackgroundJob\CheckBackupCodes;
use OCA\TwoFactorBackupCodes\BackgroundJob\RememberBackupCodesJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CheckBackupCodeTest extends TestCase {

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var IRegistry|MockObject */
	private $registry;

	/** @var Manager|MockObject */
	private $manager;

	/** @var IUser|MockObject */
	private $user;

	/** @var CheckBackupCodes */
	private $checkBackupCodes;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->registry = $this->createMock(IRegistry::class);
		$this->manager = $this->createMock(Manager::class);

		$this->user = $this->createMock(IUser::class);

		$this->userManager->method('callForSeenUsers')
			->willReturnCallback(function (\Closure $e): void {
				$e($this->user);
			});

		$this->checkBackupCodes = new CheckBackupCodes(
			$this->createMock(ITimeFactory::class),
			$this->userManager,
			$this->jobList,
			$this->manager,
			$this->registry
		);
	}

	public function testRunAlreadyGenerated(): void {
		$this->user->method('isEnabled')
			->willReturn(true);

		$this->registry->method('getProviderStates')
			->with($this->user)
			->willReturn(['backup_codes' => true]);
		$this->manager->method('isTwoFactorAuthenticated')
			->with($this->user)
			->willReturn(true);
		$this->jobList->expects($this->never())
			->method($this->anything());

		$this->invokePrivate($this->checkBackupCodes, 'run', [[]]);
	}

	public function testRun(): void {
		$this->user->method('getUID')
			->willReturn('myUID');
		$this->user->method('isEnabled')
			->willReturn(true);

		$this->registry->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([
				'backup_codes' => false,
			]);
		$this->jobList->expects($this->once())
			->method('add')
			->with(
				$this->equalTo(RememberBackupCodesJob::class),
				['uid' => 'myUID']
			);
		$this->manager->method('isTwoFactorAuthenticated')
			->with($this->user)
			->willReturn(true);

		$this->invokePrivate($this->checkBackupCodes, 'run', [[]]);
	}

	public function testRunDisabledUser(): void {
		$this->user->method('getUID')
			->willReturn('myUID');
		$this->user->method('isEnabled')
			->willReturn(false);

		$this->registry->expects($this->never())
			->method('getProviderStates')
			->with($this->user);

		$this->jobList->expects($this->never())
			->method('add');

		$this->invokePrivate($this->checkBackupCodes, 'run', [[]]);
	}

	public function testRunNoProviders(): void {
		$this->user->method('isEnabled')
			->willReturn(true);

		$this->registry->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([
				'backup_codes' => false,
			]);
		$this->jobList->expects($this->never())
			->method($this->anything());
		$this->manager->method('isTwoFactorAuthenticated')
			->with($this->user)
			->willReturn(false);

		$this->invokePrivate($this->checkBackupCodes, 'run', [[]]);
	}
}
