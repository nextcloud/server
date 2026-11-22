<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Security;

use OCP\Security\ICredentialsManager;
use OCP\Server;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class CredentialsManagerTest extends \Test\TestCase {
	#[\PHPUnit\Framework\Attributes\DataProvider('credentialsProvider')]
	public function testWithDB($userId, $identifier): void {
		$credentialsManager = Server::get(ICredentialsManager::class);

		$secrets = 'Open Sesame';

		$credentialsManager->store($userId, $identifier, $secrets);
		$received = $credentialsManager->retrieve($userId, $identifier);

		$this->assertSame($secrets, $received);

		$removedRows = $credentialsManager->delete($userId, $identifier);
		$this->assertSame(1, $removedRows);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('credentialsProvider')]
	public function testUpdate($userId, $identifier): void {
		$credentialsManager = Server::get(ICredentialsManager::class);

		$secrets = 'Open Sesame';
		$secretsRev = strrev($secrets);

		$credentialsManager->store($userId, $identifier, $secrets);
		$credentialsManager->store($userId, $identifier, $secretsRev);
		$received = $credentialsManager->retrieve($userId, $identifier);

		$this->assertSame($secretsRev, $received);
	}

	public static function credentialsProvider(): array {
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
