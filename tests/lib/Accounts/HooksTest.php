<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Accounts;

use OC\Accounts\AccountManager;
use OC\Accounts\Hooks;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class HooksTest
 *
 * @package Test\Accounts
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class HooksTest extends TestCase {

	private LoggerInterface&MockObject $logger;
	private AccountManager&MockObject $accountManager;
	private Hooks $hooks;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->accountManager = $this->getMockBuilder(AccountManager::class)
			->disableOriginalConstructor()->getMock();

		$this->hooks = new Hooks($this->logger, $this->accountManager);
	}

	/**
	 *
	 * @param $params
	 * @param $data
	 * @param $setEmail
	 * @param $setDisplayName
	 * @param $error
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestChangeUserHook')]
	public function testChangeUserHook($params, $data, $setEmail, $setDisplayName, $error): void {
		if ($error) {
			$this->accountManager->expects($this->never())->method('updateAccount');
		} else {
			$account = $this->createMock(IAccount::class);
			$this->accountManager->expects($this->atLeastOnce())->method('getAccount')->willReturn($account);
			if ($setEmail) {
				$property = $this->createMock(IAccountProperty::class);
				$property->expects($this->atLeastOnce())
					->method('getValue')
					->willReturn($data[IAccountManager::PROPERTY_EMAIL]['value']);
				$property->expects($this->atLeastOnce())
					->method('setValue')
					->with($params['value']);

				$account->expects($this->atLeastOnce())
					->method('getProperty')
					->with(IAccountManager::PROPERTY_EMAIL)
					->willReturn($property);

				$this->accountManager->expects($this->once())
					->method('updateAccount')
					->with($account);
			} elseif ($setDisplayName) {
				$property = $this->createMock(IAccountProperty::class);
				$property->expects($this->atLeastOnce())
					->method('getValue')
					->willReturn($data[IAccountManager::PROPERTY_DISPLAYNAME]['value']);
				$property->expects($this->atLeastOnce())
					->method('setValue')
					->with($params['value']);

				$account->expects($this->atLeastOnce())
					->method('getProperty')
					->with(IAccountManager::PROPERTY_DISPLAYNAME)
					->willReturn($property);

				$this->accountManager->expects($this->once())
					->method('updateAccount')
					->with($account);
			} else {
				$this->accountManager->expects($this->never())->method('updateAccount');
			}
		}

		$params['user'] = $this->createMock(IUser::class);
		$this->hooks->changeUserHook($params['user'], $params['feature'], $params['value']);
	}

	public static function dataTestChangeUserHook(): array {
		return [
			[
				['feature' => '', 'value' => ''],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => ''],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => '']
				],
				false, false, true
			],
			[
				['feature' => 'foo', 'value' => 'bar'],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'oldMail@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'oldDisplayName']
				],
				false, false, false
			],
			[
				['feature' => 'eMailAddress', 'value' => 'newMail@example.com'],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'oldMail@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'oldDisplayName']
				],
				true, false, false
			],
			[
				['feature' => 'displayName', 'value' => 'newDisplayName'],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'oldMail@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'oldDisplayName']
				],
				false, true, false
			],
		];
	}
}
