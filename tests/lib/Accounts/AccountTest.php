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
use OC\Accounts\AccountProperty;
use OCP\Accounts\IAccountManager;
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
		$property = new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED);
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED);
		$this->assertEquals($property, $account->getProperty(IAccountManager::PROPERTY_WEBSITE));
	}

	public function testGetProperties() {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::VISIBILITY_PRIVATE, IAccountManager::VERIFIED)
		];
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::VISIBILITY_PRIVATE, IAccountManager::VERIFIED);

		$this->assertEquals($properties, $account->getProperties());
	}

	public function testGetFilteredProperties() {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::VISIBILITY_PRIVATE, IAccountManager::VERIFIED),
			IAccountManager::PROPERTY_PHONE => new AccountProperty(IAccountManager::PROPERTY_PHONE, '123456', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::VERIFIED),
		];
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::VISIBILITY_PRIVATE, IAccountManager::VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_PHONE, '123456', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::VERIFIED);


		$this->assertEquals(
			[
				IAccountManager::PROPERTY_WEBSITE => $properties[IAccountManager::PROPERTY_WEBSITE],
				IAccountManager::PROPERTY_PHONE => $properties[IAccountManager::PROPERTY_PHONE],
			],
			$account->getFilteredProperties(IAccountManager::VISIBILITY_PUBLIC)
		);
		$this->assertEquals(
			[
				IAccountManager::PROPERTY_EMAIL => $properties[IAccountManager::PROPERTY_EMAIL],
				IAccountManager::PROPERTY_PHONE => $properties[IAccountManager::PROPERTY_PHONE],
			],
			$account->getFilteredProperties(null, IAccountManager::VERIFIED)
		);
		$this->assertEquals(
			[IAccountManager::PROPERTY_PHONE => $properties[IAccountManager::PROPERTY_PHONE]],
			$account->getFilteredProperties(IAccountManager::VISIBILITY_PUBLIC, IAccountManager::VERIFIED)
		);
	}

	public function testJsonSerialize() {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::VISIBILITY_PRIVATE, IAccountManager::VERIFIED)
		];
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::VISIBILITY_PUBLIC, IAccountManager::NOT_VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::VISIBILITY_PRIVATE, IAccountManager::VERIFIED);

		$this->assertEquals($properties, $account->jsonSerialize());
	}
}
