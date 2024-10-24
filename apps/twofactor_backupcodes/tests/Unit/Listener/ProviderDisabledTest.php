<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Listener;

use OCA\TwoFactorBackupCodes\BackgroundJob\RememberBackupCodesJob;
use OCA\TwoFactorBackupCodes\Listener\ProviderDisabled;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use Test\TestCase;

class ProviderDisabledTest extends TestCase {
	private IRegistry $registy;
	private IJobList $jobList;
	private ProviderDisabled $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->registy = $this->createMock(IRegistry::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->listener = new ProviderDisabled($this->registy, $this->jobList);
	}

	public function testHandleGenericEvent(): void {
		$event = $this->createMock(Event::class);
		$this->jobList->expects($this->never())
			->method($this->anything());

		$this->listener->handle($event);
	}

	public function testHandleStillActiveProvider(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('myUID');
		$event = $this->createMock(TwoFactorProviderForUserUnregistered::class);
		$event->method('getUser')
			->willReturn($user);

		$this->registy->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => false,
				'foo' => true,
			]);

		$this->jobList->expects($this->never())
			->method($this->anything());

		$this->listener->handle($event);
	}

	public function testHandleNoActiveProvider(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('myUID');
		$event = $this->createMock(TwoFactorProviderForUserUnregistered::class);
		$event->method('getUser')
			->willReturn($user);

		$this->registy->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => false,
				'foo' => false,
			]);

		$this->jobList->expects($this->once())
			->method('remove')
			->with(
				$this->equalTo(RememberBackupCodesJob::class),
				$this->equalTo(['uid' => 'myUID'])
			);

		$this->listener->handle($event);
	}
}
