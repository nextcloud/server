<?php

declare(strict_types=1);

/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\SyncSystemAddressBookAfterUsersChange;
use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use Test\TestCase;

class SyncSystemAddressBookAfterUserChangeTest extends TestCase {
	private SyncService|MockObject $syncService;

	private SyncSystemAddressBookAfterUsersChange $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->syncService = $this->createMock(SyncService::class);
		$timeFactory = $this->createMock(ITimeFactory::class);

		$this->backgroundJob = new SyncSystemAddressBookAfterUsersChange(
			$this->syncService, $timeFactory);
	}

	public function testRun(): void {
		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);

		$this->syncService->expects($this->exactly(2))->method('updateUser')->withConsecutive([$user1], [$user2]);

		$this->backgroundJob->run([$user1, $user2]);
	}
}
