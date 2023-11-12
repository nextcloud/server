<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\UserStatus\ContactsMenu;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use OCP\Contacts\ContactsMenu\IBulkProvider;
use OCP\Contacts\ContactsMenu\IEntry;
use function array_combine;
use function array_filter;
use function array_map;

class StatusProvider implements IBulkProvider {

	public function __construct(private StatusService $statusService) {
	}

	public function process(array $entries): void {
		$uids = array_filter(
			array_map(fn (IEntry $entry): ?string => $entry->getProperty('UID'), $entries)
		);

		$statuses = $this->statusService->findByUserIds($uids);
		/** @var array<string, UserStatus> $indexed */
		$indexed = array_combine(
			array_map(fn(UserStatus $status) => $status->getUserId(), $statuses),
			$statuses
		);

		foreach ($entries as $entry) {
			$uid = $entry->getProperty('UID');
			if ($uid !== null && isset($indexed[$uid])) {
				$status = $indexed[$uid];
				$entry->setStatus(
					$status->getStatus(),
					$status->getCustomMessage(),
					$status->getStatusMessageTimestamp(),
					$status->getCustomIcon(),
				);
			}
		}
	}

}
