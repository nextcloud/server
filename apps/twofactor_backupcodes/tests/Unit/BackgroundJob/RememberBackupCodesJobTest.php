<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\BackgroundJob;

use OCA\TwoFactorBackupCodes\BackgroundJob\RememberBackupCodesJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Server;
use Test\TestCase;

class RememberBackupCodesJobTest extends TestCase {

	/** @var IRegistry|\PHPUnit\Framework\MockObject\MockObject */
	private $registry;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $time;

	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $notificationManager;

	/** @var IJobList|\PHPUnit\Framework\MockObject\MockObject */
	private $jobList;

	/** @var RememberBackupCodesJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(IRegistry::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')
			->willReturn(10000000);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->job = new RememberBackupCodesJob(
			$this->registry,
			$this->userManager,
			$this->time,
			$this->notificationManager,
			$this->jobList
		);
	}

	public function testInvalidUID(): void {
		$this->userManager->method('get')
			->with('invalidUID')
			->willReturn(null);

		$this->notificationManager->expects($this->never())
			->method($this->anything());
		$this->jobList->expects($this->once())
			->method('remove')
			->with(
				RememberBackupCodesJob::class,
				['uid' => 'invalidUID']
			);
		$this->jobList->expects($this->never())
			->method('add');

		self::invokePrivate($this->job, 'run', [['uid' => 'invalidUID']]);
	}

	public function testBackupCodesGenerated(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('validUID');
		$user->method('isEnabled')
			->willReturn(true);

		$this->userManager->method('get')
			->with('validUID')
			->willReturn($user);

		$this->registry->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => true
			]);

		$this->jobList->expects($this->once())
			->method('remove')
			->with(
				RememberBackupCodesJob::class,
				['uid' => 'validUID']
			);

		$this->notificationManager->expects($this->never())
			->method($this->anything());

		self::invokePrivate($this->job, 'run', [['uid' => 'validUID']]);
	}

	public function testNoActiveProvider(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('validUID');
		$this->userManager->method('get')
			->with('validUID')
			->willReturn($user);

		$this->registry->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => false,
				'foo' => false,
			]);

		$this->jobList->expects($this->once())
			->method('remove')
			->with(
				RememberBackupCodesJob::class,
				['uid' => 'validUID']
			);

		$this->notificationManager->expects($this->never())
			->method($this->anything());

		self::invokePrivate($this->job, 'run', [['uid' => 'validUID']]);
	}

	public function testNotificationSend(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('validUID');
		$user->method('isEnabled')
			->willReturn(true);
		$this->userManager->method('get')
			->with('validUID')
			->willReturn($user);

		$this->registry->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => false,
				'foo' => true,
			]);

		$this->jobList->expects($this->never())
			->method($this->anything());

		$date = new \DateTime();
		$date->setTimestamp($this->time->getTime());

		$this->notificationManager->method('createNotification')
			->willReturn(Server::get(IManager::class)->createNotification());

		$this->notificationManager->expects($this->once())
			->method('notify')
			->with($this->callback(function (INotification $n) {
				return $n->getApp() === 'twofactor_backupcodes' &&
					$n->getUser() === 'validUID' &&
					$n->getDateTime()->getTimestamp() === 10000000 &&
					$n->getObjectType() === 'create' &&
					$n->getObjectId() === 'codes' &&
					$n->getSubject() === 'create_backupcodes';
			}));

		self::invokePrivate($this->job, 'run', [['uid' => 'validUID']]);
	}

	public function testNotificationNotSendForDisabledUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('validUID');
		$user->method('isEnabled')
			->willReturn(false);
		$this->userManager->method('get')
			->with('validUID')
			->willReturn($user);

		$this->registry->method('getProviderStates')
			->with($user)
			->willReturn([
				'backup_codes' => false,
				'foo' => true,
			]);

		$this->jobList->expects($this->once())
			->method('remove')
			->with(
				RememberBackupCodesJob::class,
				['uid' => 'validUID']
			);

		$date = new \DateTime();
		$date->setTimestamp($this->time->getTime());

		$this->notificationManager->method('createNotification')
			->willReturn(Server::get(IManager::class)->createNotification());

		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($this->callback(function (INotification $n) {
				return $n->getApp() === 'twofactor_backupcodes' &&
					$n->getUser() === 'validUID' &&
					$n->getObjectType() === 'create' &&
					$n->getObjectId() === 'codes' &&
					$n->getSubject() === 'create_backupcodes';
			}));

		$this->notificationManager->expects($this->never())
			->method('notify');

		self::invokePrivate($this->job, 'run', [['uid' => 'validUID']]);
	}
}
