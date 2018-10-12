<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Accounts;

use OC\Accounts\Account;
use OC\Accounts\AccountManager;
use OC\Accounts\AccountProperty;
use OCP\IUser;
use Test\TestCase;

/**
 * Class AccountTest
 *
 * @package Test\Accounts
 */
class AccountTest extends TestCase {

	public function testConstructor() {
		$user = $this->createMock(IUser::class);
		$account = new Account($user);
		$this->assertEquals($user, $account->getUser());
	}

	public function testSetProperty() {
		$user = $this->createMock(IUser::class);
		$property = new AccountProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED);
		$account = new Account($user);
		$account->setProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED);
		$this->assertEquals($property, $account->getProperty(AccountManager::PROPERTY_WEBSITE));
	}

	public function testGetProperties() {
		$user = $this->createMock(IUser::class);
		$properties = [
			AccountManager::PROPERTY_WEBSITE => new AccountProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED),
			AccountManager::PROPERTY_EMAIL => new AccountProperty(AccountManager::PROPERTY_EMAIL, 'user@example.com', AccountManager::VISIBILITY_PRIVATE, AccountManager::VERIFIED)
		];
		$account = new Account($user);
		$account->setProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED);
		$account->setProperty(AccountManager::PROPERTY_EMAIL, 'user@example.com', AccountManager::VISIBILITY_PRIVATE, AccountManager::VERIFIED);

		$this->assertEquals($properties, $account->getProperties());
	}

	public function testGetFilteredProperties() {
		$user = $this->createMock(IUser::class);
		$properties = [
			AccountManager::PROPERTY_WEBSITE => new AccountProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED),
			AccountManager::PROPERTY_EMAIL => new AccountProperty(AccountManager::PROPERTY_EMAIL, 'user@example.com', AccountManager::VISIBILITY_PRIVATE, AccountManager::VERIFIED),
			AccountManager::PROPERTY_PHONE => new AccountProperty(AccountManager::PROPERTY_PHONE, '123456', AccountManager::VISIBILITY_PUBLIC, AccountManager::VERIFIED),
		];
		$account = new Account($user);
		$account->setProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED);
		$account->setProperty(AccountManager::PROPERTY_EMAIL, 'user@example.com', AccountManager::VISIBILITY_PRIVATE, AccountManager::VERIFIED);
		$account->setProperty(AccountManager::PROPERTY_PHONE, '123456', AccountManager::VISIBILITY_PUBLIC, AccountManager::VERIFIED);


		$this->assertEquals(
			[
				AccountManager::PROPERTY_WEBSITE => $properties[AccountManager::PROPERTY_WEBSITE],
				AccountManager::PROPERTY_PHONE => $properties[AccountManager::PROPERTY_PHONE],
			],
			$account->getFilteredProperties(AccountManager::VISIBILITY_PUBLIC)
		);
		$this->assertEquals(
			[
				AccountManager::PROPERTY_EMAIL => $properties[AccountManager::PROPERTY_EMAIL],
				AccountManager::PROPERTY_PHONE => $properties[AccountManager::PROPERTY_PHONE],
			],
			$account->getFilteredProperties(null, AccountManager::VERIFIED)
		);
		$this->assertEquals(
			[AccountManager::PROPERTY_PHONE => $properties[AccountManager::PROPERTY_PHONE]],
			$account->getFilteredProperties(AccountManager::VISIBILITY_PUBLIC, AccountManager::VERIFIED)
		);
	}

	public function testJsonSerialize() {
		$user = $this->createMock(IUser::class);
		$properties = [
			AccountManager::PROPERTY_WEBSITE => new AccountProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED),
			AccountManager::PROPERTY_EMAIL => new AccountProperty(AccountManager::PROPERTY_EMAIL, 'user@example.com', AccountManager::VISIBILITY_PRIVATE, AccountManager::VERIFIED)
		];
		$account = new Account($user);
		$account->setProperty(AccountManager::PROPERTY_WEBSITE, 'https://example.com', AccountManager::VISIBILITY_PUBLIC, AccountManager::NOT_VERIFIED);
		$account->setProperty(AccountManager::PROPERTY_EMAIL, 'user@example.com', AccountManager::VISIBILITY_PRIVATE, AccountManager::VERIFIED);

		$this->assertEquals($properties, $account->jsonSerialize());
	}

}
