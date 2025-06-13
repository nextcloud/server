<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC29;

use InvalidArgumentException;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SanitizeAccountPropertiesJobTest extends TestCase {

	private IUserManager&MockObject $userManager;
	private IAccountManager&MockObject $accountManager;
	private LoggerInterface&MockObject $logger;
	
	private SanitizeAccountPropertiesJob $job;

	protected function setUp(): void {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->job = new SanitizeAccountPropertiesJob(
			$this->createMock(ITimeFactory::class),
			$this->userManager,
			$this->accountManager,
			$this->logger,
		);
	}

	public function testParallel() {
		self::assertFalse($this->job->getAllowParallelRuns());
	}

	public function testRun(): void {
		$users = [
			$this->createMock(IUser::class),
			$this->createMock(IUser::class),
			$this->createMock(IUser::class),
		];
		$this->userManager
			->expects(self::once())
			->method('callForSeenUsers')
			->willReturnCallback(fn ($fn) => array_map($fn, $users));

		$property = $this->createMock(IAccountProperty::class);
		$property->expects(self::once())->method('getName')->willReturn(IAccountManager::PROPERTY_PHONE);
		$property->expects(self::once())->method('getScope')->willReturn(IAccountManager::SCOPE_LOCAL);

		$account1 = $this->createMock(IAccount::class);
		$account1->expects(self::once())
			->method('getProperty')
			->with(IAccountManager::PROPERTY_PHONE)
			->willReturn($property);
		$account1->expects(self::once())
			->method('setProperty')
			->with(IAccountManager::PROPERTY_PHONE, '', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);
		$account1->expects(self::once())
			->method('jsonSerialize')
			->willReturn([
				IAccountManager::PROPERTY_DISPLAYNAME => [],
				IAccountManager::PROPERTY_PHONE => [],
			]);

		$account2 = $this->createMock(IAccount::class);
		$account2->expects(self::never())
			->method('getProperty');
		$account2->expects(self::once())
			->method('jsonSerialize')
			->willReturn([
				IAccountManager::PROPERTY_DISPLAYNAME => [],
				IAccountManager::PROPERTY_PHONE => [],
			]);

		$account3 = $this->createMock(IAccount::class);
		$account3->expects(self::never())
			->method('getProperty');
		$account3->expects(self::once())
			->method('jsonSerialize')
			->willReturn([
				IAccountManager::PROPERTY_DISPLAYNAME => [],
			]);

		$this->accountManager
			->expects(self::exactly(3))
			->method('getAccount')
			->willReturnMap([
				[$users[0], $account1],
				[$users[1], $account2],
				[$users[2], $account3],
			]);
		$valid = false;
		$this->accountManager->expects(self::exactly(3))
			->method('updateAccount')
			->willReturnCallback(function (IAccount $account) use (&$account1, &$valid): void {
				if (!$valid && $account === $account1) {
					$valid = true;
					throw new InvalidArgumentException(IAccountManager::PROPERTY_PHONE);
				}
			});

		self::invokePrivate($this->job, 'run', [null]);
	}
}
