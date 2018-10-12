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

use Test\TestCase;

/**
 * Class AccountPropertyTest
 *
 * @package Test\Accounts
 */
class AccountPropertyTest extends TestCase {

	public function testConstructor() {
		$accountProperty = new AccountProperty(
			AccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			AccountManager::VISIBILITY_PUBLIC,
			AccountManager::VERIFIED
		);
		$this->assertEquals(AccountManager::PROPERTY_WEBSITE, $accountProperty->getName());
		$this->assertEquals('https://example.com', $accountProperty->getValue());
		$this->assertEquals(AccountManager::VISIBILITY_PUBLIC, $accountProperty->getScope());
		$this->assertEquals(AccountManager::VERIFIED, $accountProperty->getVerified());
	}

	public function testSetValue() {
		$accountProperty = new AccountProperty(
			AccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			AccountManager::VISIBILITY_PUBLIC,
			AccountManager::VERIFIED
		);
		$actualReturn = $accountProperty->setValue('https://example.org');
		$this->assertEquals('https://example.org', $accountProperty->getValue());
		$this->assertEquals('https://example.org', $actualReturn->getValue());
	}

	public function testSetScope() {
		$accountProperty = new AccountProperty(
			AccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			AccountManager::VISIBILITY_PUBLIC,
			AccountManager::VERIFIED
		);
		$actualReturn = $accountProperty->setScope(AccountManager::VISIBILITY_PRIVATE);
		$this->assertEquals(AccountManager::VISIBILITY_PRIVATE, $accountProperty->getScope());
		$this->assertEquals(AccountManager::VISIBILITY_PRIVATE, $actualReturn->getScope());
	}

	public function testSetVerified() {
		$accountProperty = new AccountProperty(
			AccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			AccountManager::VISIBILITY_PUBLIC,
			AccountManager::VERIFIED
		);
		$actualReturn = $accountProperty->setVerified(AccountManager::NOT_VERIFIED);
		$this->assertEquals(AccountManager::NOT_VERIFIED, $accountProperty->getVerified());
		$this->assertEquals(AccountManager::NOT_VERIFIED, $actualReturn->getVerified());
	}

	public function testJsonSerialize() {
		$accountProperty = new AccountProperty(
			AccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			AccountManager::VISIBILITY_PUBLIC,
			AccountManager::VERIFIED
		);
		$this->assertEquals([
			'name' => AccountManager::PROPERTY_WEBSITE,
			'value' => 'https://example.com',
			'scope' => AccountManager::VISIBILITY_PUBLIC,
			'verified' => AccountManager::VERIFIED
		], $accountProperty->jsonSerialize());
	}


}
