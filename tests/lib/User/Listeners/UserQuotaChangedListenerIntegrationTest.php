<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User\Listeners;

use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

/**
 * End-to-end coverage for the quota change etag invalidation: unlike the
 * mock-based unit test this proves the listener is actually registered in
 * the server container, by going through the real setQuota() event chain.
 */
#[Group('DB')]
class UserQuotaChangedListenerIntegrationTest extends TestCase {
	private const TEST_USER = 'quota_etag_listener_test_user';

	#[\Override]
	protected function tearDown(): void {
		Server::get(IUserManager::class)->get(self::TEST_USER)?->delete();
		parent::tearDown();
	}

	public function testSetQuotaInvalidatesUserRootEtag(): void {
		$userManager = Server::get(IUserManager::class);
		$userManager->get(self::TEST_USER)?->delete();
		$user = $userManager->createUser(self::TEST_USER, 'correct-Horse-battery1!');
		$this->assertNotFalse($user);

		$userFolder = Server::get(IRootFolder::class)->getUserFolder(self::TEST_USER);
		$cache = $userFolder->getStorage()->getCache();
		$etagBefore = $cache->get($userFolder->getInternalPath())->getEtag();

		$user->setQuota('5 GB');

		$etagAfter = $cache->get($userFolder->getInternalPath())->getEtag();
		$this->assertNotEquals(
			$etagBefore,
			$etagAfter,
			'User root etag must change when the quota changes so clients re-fetch quota-available-bytes',
		);
	}
}
