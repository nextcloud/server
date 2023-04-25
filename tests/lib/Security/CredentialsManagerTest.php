<?php

declare(strict_types=1);

/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Security;

use OCP\Security\ICredentialsManager;
use OCP\Server;

/**
 * @group DB
 */
class CredentialsManagerTest extends \Test\TestCase {
	/**
	 * @dataProvider credentialsProvider
	 */
	public function testWithDB($userId, $identifier) {
		$credentialsManager = Server::get(ICredentialsManager::class);

		$secrets = 'Open Sesame';

		$credentialsManager->store($userId, $identifier, $secrets);
		$received = $credentialsManager->retrieve($userId, $identifier);

		$this->assertSame($secrets, $received);

		$removedRows = $credentialsManager->delete($userId, $identifier);
		$this->assertSame(1, $removedRows);
	}

	/**
	 * @dataProvider credentialsProvider
	 */
	public function testUpdate($userId, $identifier): void {
		$credentialsManager = Server::get(ICredentialsManager::class);

		$secrets = 'Open Sesame';
		$secretsRev = strrev($secrets);

		$credentialsManager->store($userId, $identifier, $secrets);
		$credentialsManager->store($userId, $identifier, $secretsRev);
		$received = $credentialsManager->retrieve($userId, $identifier);

		$this->assertSame($secretsRev, $received);
	}

	public function credentialsProvider(): array {
		return [
			[
				'alice',
				'privateCredentials'
			],
			[
				'',
				'systemCredentials',
			],
		];
	}
}
