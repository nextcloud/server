<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Service;

use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class BackupCodeStorageTest extends TestCase {
	private IManager&MockObject $notificationManager;
	private string $testUID = 'test123456789';
	private BackupCodeStorage $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = Server::get(BackupCodeStorage::class);

		$this->notificationManager = $this->createMock(IManager::class);
		$this->notificationManager->method('createNotification')
			->willReturn(Server::get(IManager::class)->createNotification());
		$this->overwriteService(IManager::class, $this->notificationManager);
	}

	public function testSimpleWorkFlow(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$user->expects($this->any())
			->method('getUID')
			->willReturn($this->testUID);

		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($this->callback(function (INotification $notification) {
				return $notification->getUser() === $this->testUID
					&& $notification->getObjectType() === 'create'
					&& $notification->getObjectId() === 'codes'
					&& $notification->getApp() === 'twofactor_backupcodes';
			}));

		// Create codes
		$codes = $this->storage->createCodes($user, 5);
		$this->assertCount(5, $codes);
		$this->assertTrue($this->storage->hasBackupCodes($user));
		$initialState = [
			'enabled' => true,
			'total' => 5,
			'used' => 0,
		];
		$this->assertEquals($initialState, $this->storage->getBackupCodesState($user));

		// Use codes
		$code = $codes[2];
		$this->assertTrue($this->storage->validateCode($user, $code));
		// Code must not be used twice
		$this->assertFalse($this->storage->validateCode($user, $code));
		// Invalid codes are invalid
		$this->assertFalse($this->storage->validateCode($user, 'I DO NOT EXIST'));
		$stateAfter = [
			'enabled' => true,
			'total' => 5,
			'used' => 1,
		];
		$this->assertEquals($stateAfter, $this->storage->getBackupCodesState($user));

		// Deplete codes
		$this->assertTrue($this->storage->validateCode($user, $codes[0]));
		$this->assertTrue($this->storage->validateCode($user, $codes[1]));
		$this->assertTrue($this->storage->validateCode($user, $codes[3]));
		$this->assertTrue($this->storage->validateCode($user, $codes[4]));
		$stateAllUsed = [
			'enabled' => true,
			'total' => 5,
			'used' => 5,
		];
		$this->assertEquals($stateAllUsed, $this->storage->getBackupCodesState($user));
	}
}
