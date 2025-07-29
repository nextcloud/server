<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Connector;

use OCA\UserStatus\Connector\UserStatus;
use OCA\UserStatus\Db;
use Test\TestCase;

class UserStatusTest extends TestCase {
	public function testUserStatus(): void {
		$status = new Db\UserStatus();
		$status->setUserId('user2');
		$status->setStatus('away');
		$status->setStatusTimestamp(5000);
		$status->setIsUserDefined(false);
		$status->setCustomIcon('🏝');
		$status->setCustomMessage('On vacation');
		$status->setClearAt(60000);

		$userStatus = new UserStatus($status);
		$this->assertEquals('user2', $userStatus->getUserId());
		$this->assertEquals('away', $userStatus->getStatus());
		$this->assertEquals('On vacation', $userStatus->getMessage());
		$this->assertEquals('🏝', $userStatus->getIcon());

		$dateTime = $userStatus->getClearAt();
		$this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
		$this->assertEquals('60000', $dateTime->format('U'));
	}

	public function testUserStatusInvisible(): void {
		$status = new Db\UserStatus();
		$status->setUserId('user2');
		$status->setStatus('invisible');
		$status->setStatusTimestamp(5000);
		$status->setIsUserDefined(false);
		$status->setCustomIcon('🏝');
		$status->setCustomMessage('On vacation');
		$status->setClearAt(60000);

		$userStatus = new UserStatus($status);
		$this->assertEquals('user2', $userStatus->getUserId());
		$this->assertEquals('offline', $userStatus->getStatus());
		$this->assertEquals('On vacation', $userStatus->getMessage());
		$this->assertEquals('🏝', $userStatus->getIcon());
	}
}
