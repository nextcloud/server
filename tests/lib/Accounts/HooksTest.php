<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
 * @group DB
 */
class HooksTest extends TestCase {

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var AccountManager|MockObject */
	private $accountManager;

	/** @var Hooks */
	private $hooks;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->accountManager = $this->getMockBuilder(AccountManager::class)
			->disableOriginalConstructor()->getMock();

		$this->hooks = new Hooks($this->logger, $this->accountManager);
	}

	/**
	 * @dataProvider dataTestChangeUserHook
	 *
	 * @param $params
	 * @param $data
	 * @param $setEmail
	 * @param $setDisplayName
	 * @param $error
	 */
	public function testChangeUserHook($params, $data, $setEmail, $setDisplayName, $error) {
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

		$this->hooks->changeUserHook($params['user'], $params['feature'], $params['value']);
	}

	public function dataTestChangeUserHook() {
		$user = $this->createMock(IUser::class);
		return [
			[
				['user' => $user, 'feature' => '', 'value' => ''],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => ''],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => '']
				],
				false, false, true
			],
			[
				['user' => $user, 'feature' => 'foo', 'value' => 'bar'],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'oldMail@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'oldDisplayName']
				],
				false, false, false
			],
			[
				['user' => $user, 'feature' => 'eMailAddress', 'value' => 'newMail@example.com'],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'oldMail@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'oldDisplayName']
				],
				true, false, false
			],
			[
				['user' => $user, 'feature' => 'displayName', 'value' => 'newDisplayName'],
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'oldMail@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'oldDisplayName']
				],
				false, true, false
			],
		];
	}
}
