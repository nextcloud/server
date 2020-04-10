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

use OC\Accounts\AccountProperty;
use OCP\Accounts\IAccountManager;
use Test\TestCase;

/**
 * Class AccountPropertyTest
 *
 * @package Test\Accounts
 */
class AccountPropertyTest extends TestCase {
	public function testConstructor() {
		$accountProperty = new AccountProperty(
			IAccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			IAccountManager::VISIBILITY_PUBLIC,
			IAccountManager::VERIFIED
		);
		$this->assertEquals(IAccountManager::PROPERTY_WEBSITE, $accountProperty->getName());
		$this->assertEquals('https://example.com', $accountProperty->getValue());
		$this->assertEquals(IAccountManager::VISIBILITY_PUBLIC, $accountProperty->getScope());
		$this->assertEquals(IAccountManager::VERIFIED, $accountProperty->getVerified());
	}

	public function testSetValue() {
		$accountProperty = new AccountProperty(
			IAccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			IAccountManager::VISIBILITY_PUBLIC,
			IAccountManager::VERIFIED
		);
		$actualReturn = $accountProperty->setValue('https://example.org');
		$this->assertEquals('https://example.org', $accountProperty->getValue());
		$this->assertEquals('https://example.org', $actualReturn->getValue());
	}

	public function testSetScope() {
		$accountProperty = new AccountProperty(
			IAccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			IAccountManager::VISIBILITY_PUBLIC,
			IAccountManager::VERIFIED
		);
		$actualReturn = $accountProperty->setScope(IAccountManager::VISIBILITY_PRIVATE);
		$this->assertEquals(IAccountManager::VISIBILITY_PRIVATE, $accountProperty->getScope());
		$this->assertEquals(IAccountManager::VISIBILITY_PRIVATE, $actualReturn->getScope());
	}

	public function testSetVerified() {
		$accountProperty = new AccountProperty(
			IAccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			IAccountManager::VISIBILITY_PUBLIC,
			IAccountManager::VERIFIED
		);
		$actualReturn = $accountProperty->setVerified(IAccountManager::NOT_VERIFIED);
		$this->assertEquals(IAccountManager::NOT_VERIFIED, $accountProperty->getVerified());
		$this->assertEquals(IAccountManager::NOT_VERIFIED, $actualReturn->getVerified());
	}

	public function testJsonSerialize() {
		$accountProperty = new AccountProperty(
			IAccountManager::PROPERTY_WEBSITE,
			'https://example.com',
			IAccountManager::VISIBILITY_PUBLIC,
			IAccountManager::VERIFIED
		);
		$this->assertEquals([
			'name' => IAccountManager::PROPERTY_WEBSITE,
			'value' => 'https://example.com',
			'scope' => IAccountManager::VISIBILITY_PUBLIC,
			'verified' => IAccountManager::VERIFIED
		], $accountProperty->jsonSerialize());
	}
}
