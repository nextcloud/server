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
use OC\Accounts\AccountPropertyCollection;
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
		$property = new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, '');
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED);
		$this->assertEquals($property, $account->getProperty(IAccountManager::PROPERTY_WEBSITE));
	}

	public function testGetAndGetAllProperties() {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::SCOPE_LOCAL, IAccountManager::VERIFIED, '')
		];
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::SCOPE_LOCAL, IAccountManager::VERIFIED);

		$col = new AccountPropertyCollection(IAccountManager::COLLECTION_EMAIL);
		$additionalProperty = new AccountProperty($col->getName(), 'second@example.org', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, '');
		$col->addProperty($additionalProperty);
		$account->setPropertyCollection($col);

		$this->assertEquals($properties, $account->getProperties());
		$properties[] = $additionalProperty;
		$this->assertEquals(array_values($properties), \iterator_to_array($account->getAllProperties()));
	}

	public function testGetFilteredProperties() {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::SCOPE_LOCAL, IAccountManager::VERIFIED, ''),
			IAccountManager::PROPERTY_PHONE => new AccountProperty(IAccountManager::PROPERTY_PHONE, '123456', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFIED, ''),
		];
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::SCOPE_LOCAL, IAccountManager::VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_PHONE, '123456', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFIED);

		$col = new AccountPropertyCollection(IAccountManager::COLLECTION_EMAIL);
		$additionalProperty1 = new AccountProperty($col->getName(), 'second@example.org', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, '');
		$additionalProperty2 = new AccountProperty($col->getName(), 'third@example.org', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFIED, '');
		$col->addProperty($additionalProperty1);
		$col->addProperty($additionalProperty2);
		$account->setPropertyCollection($col);


		$this->assertEquals(
			[
				IAccountManager::PROPERTY_WEBSITE => $properties[IAccountManager::PROPERTY_WEBSITE],
				IAccountManager::PROPERTY_PHONE => $properties[IAccountManager::PROPERTY_PHONE],
				IAccountManager::COLLECTION_EMAIL . '#0' => $additionalProperty1,
				IAccountManager::COLLECTION_EMAIL . '#1' => $additionalProperty2,
			],
			$account->getFilteredProperties(IAccountManager::SCOPE_PUBLISHED)
		);
		$this->assertEquals(
			[
				IAccountManager::PROPERTY_EMAIL => $properties[IAccountManager::PROPERTY_EMAIL],
				IAccountManager::PROPERTY_PHONE => $properties[IAccountManager::PROPERTY_PHONE],
				IAccountManager::COLLECTION_EMAIL . '#0' => $additionalProperty2,
			],
			$account->getFilteredProperties(null, IAccountManager::VERIFIED)
		);
		$this->assertEquals(
			[
				IAccountManager::PROPERTY_PHONE => $properties[IAccountManager::PROPERTY_PHONE],
				IAccountManager::COLLECTION_EMAIL . '#0' => $additionalProperty2,
			],
			$account->getFilteredProperties(IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFIED),
		);
	}

	public function testJsonSerialize() {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::SCOPE_LOCAL, IAccountManager::VERIFIED, ''),
			IAccountManager::COLLECTION_EMAIL => [
				new AccountProperty(IAccountManager::COLLECTION_EMAIL, 'apple@orange.com', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED, ''),
				new AccountProperty(IAccountManager::COLLECTION_EMAIL, 'banana@orange.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFICATION_IN_PROGRESS, ''),
				new AccountProperty(IAccountManager::COLLECTION_EMAIL, 'kiwi@watermelon.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFIED, ''),
			],
		];

		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED);
		$account->setProperty(IAccountManager::PROPERTY_EMAIL, 'user@example.com', IAccountManager::SCOPE_LOCAL, IAccountManager::VERIFIED);

		$col = new AccountPropertyCollection(IAccountManager::COLLECTION_EMAIL);
		$col->setProperties([
			new AccountProperty($col->getName(), 'apple@orange.com', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED, ''),
			new AccountProperty($col->getName(), 'banana@orange.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFICATION_IN_PROGRESS, ''),
			new AccountProperty($col->getName(), 'kiwi@watermelon.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFIED, ''),
		]);
		$account->setPropertyCollection($col);

		$this->assertEquals($properties, $account->jsonSerialize());
	}
}
