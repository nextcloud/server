<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Listener;

use OCA\TwoFactorBackupCodes\BackgroundJob\RememberBackupCodesJob;
use OCA\TwoFactorBackupCodes\Listener\ProviderEnabled;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderEnabledTest extends TestCase {
	private IRegistry&MockObject $registy;
	private IJobList&MockObject $jobList;
	private ProviderEnabled $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->registy = $this->createMock(IRegistry::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->listener = new ProviderEnabled($this->registy, $this->jobList);
	}

	public function testHandleCodesGeneratedEventAlraedyBackupcodes(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('myUID');
		$event = $this->createMock(TwoFactorProviderForUserRegistered::class);
		$event->method('getUser')
			->willReturn($user);

		$this->registy->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => true,
			]);

		$this->jobList->expects($this->never())
			->method($this->anything());

		$this->listener->handle($event);
	}

	public function testHandleCodesGeneratedEventNoBackupcodes(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('myUID');
		$event = $this->createMock(TwoFactorProviderForUserRegistered::class);
		$event->method('getUser')
			->willReturn($user);

		$this->registy->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => false,
			]);

		$this->jobList->expects($this->once())
			->method('add')
			->with(
				$this->equalTo(RememberBackupCodesJob::class),
				$this->equalTo(['uid' => 'myUID'])
			);

		$this->listener->handle($event);
	}
}
