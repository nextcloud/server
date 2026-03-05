<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function __construct(
		private StatusService $statusService,
	) {
	}

	public function process(array $entries): void {
		$uids = array_filter(
			array_map(fn (IEntry $entry): ?string => $entry->getProperty('UID'), $entries)
		);

		$statuses = $this->statusService->findByUserIds($uids);
		/** @var array<string, UserStatus> $indexed */
		$indexed = array_combine(
			array_map(fn (UserStatus $status) => $status->getUserId(), $statuses),
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
