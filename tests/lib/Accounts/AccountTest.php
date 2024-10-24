<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function testConstructor(): void {
		$user = $this->createMock(IUser::class);
		$account = new Account($user);
		$this->assertEquals($user, $account->getUser());
	}

	public function testSetProperty(): void {
		$user = $this->createMock(IUser::class);
		$property = new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, '');
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED);
		$this->assertEquals($property, $account->getProperty(IAccountManager::PROPERTY_WEBSITE));
	}

	public function testGetAndGetAllProperties(): void {
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

	public function testSetAllPropertiesFromJson(): void {
		$user = $this->createMock(IUser::class);
		$properties = [
			IAccountManager::PROPERTY_DISPLAYNAME => new AccountProperty(IAccountManager::PROPERTY_DISPLAYNAME, 'Steve', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_ADDRESS => new AccountProperty(IAccountManager::PROPERTY_ADDRESS, '123 Acorn Avenue', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_WEBSITE => new AccountProperty(IAccountManager::PROPERTY_WEBSITE, 'https://www.example.org', IAccountManager::SCOPE_FEDERATED, IAccountManager::VERIFIED, ''),
			IAccountManager::PROPERTY_EMAIL => new AccountProperty(IAccountManager::PROPERTY_EMAIL, 'steve@earth.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFICATION_IN_PROGRESS, ''),
			IAccountManager::PROPERTY_AVATAR => new AccountProperty(IAccountManager::PROPERTY_AVATAR, '', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_PHONE => new AccountProperty(IAccountManager::PROPERTY_PHONE, '+358407991028', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_TWITTER => new AccountProperty(IAccountManager::PROPERTY_TWITTER, 'therealsteve', IAccountManager::SCOPE_PRIVATE, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_ORGANISATION => new AccountProperty(IAccountManager::PROPERTY_ORGANISATION, 'Steve Incorporated', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_ROLE => new AccountProperty(IAccountManager::PROPERTY_ROLE, 'Founder', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_HEADLINE => new AccountProperty(IAccountManager::PROPERTY_HEADLINE, 'I am Steve', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_BIOGRAPHY => new AccountProperty(IAccountManager::PROPERTY_BIOGRAPHY, 'Steve is the best', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::PROPERTY_PROFILE_ENABLED => new AccountProperty(IAccountManager::PROPERTY_PROFILE_ENABLED, '1', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, ''),
			IAccountManager::COLLECTION_EMAIL => [
				new AccountProperty(IAccountManager::COLLECTION_EMAIL, 'steve@mars.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::NOT_VERIFIED, ''),
				new AccountProperty(IAccountManager::COLLECTION_EMAIL, 'steve@neptune.com', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, ''),
			],
		];
		$account = new Account($user);
		$account->setAllPropertiesFromJson(json_decode(json_encode($properties), true));
		$this->assertEquals($properties, $account->jsonSerialize());
	}

	public function testGetFilteredProperties(): void {
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

	public function testJsonSerialize(): void {
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
