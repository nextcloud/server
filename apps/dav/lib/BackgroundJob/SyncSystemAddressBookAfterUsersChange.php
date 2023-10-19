<?php

declare(strict_types=1);

/**
 * @copyright 2023, Citharel Thomas <nextcloud@tcit.fr>
 *
 * @author Citharel Thomas <nextcloud@tcit.fr>
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
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IUser;

class SyncSystemAddressBookAfterUsersChange extends QueuedJob {
	private SyncService $syncService;

	public function __construct(SyncService $syncService, ITimeFactory $time) {
		parent::__construct($time);
		$this->syncService = $syncService;
	}

	/**
	 * @param IUser[] $argument
	 * @return void
	 */
	public function run($argument): void {
		foreach ($argument as $user) {
			$this->syncService->updateUser($user);
		}
	}
}
