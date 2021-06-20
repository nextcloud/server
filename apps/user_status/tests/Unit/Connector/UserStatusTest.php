<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\UserStatus\Tests\Connector;

use OCA\UserStatus\Connector\UserStatus;
use Test\TestCase;
use OCA\UserStatus\Db;

class UserStatusTest extends TestCase {
	public function testUserStatus() {
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

	public function testUserStatusInvisible() {
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
