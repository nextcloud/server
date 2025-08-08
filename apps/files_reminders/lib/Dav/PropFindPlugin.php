<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Dav;

use DateTimeInterface;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Node;
use OCA\FilesReminders\Service\ReminderService;
use OCP\Files\Folder;
use OCP\IUser;
use OCP\IUserSession;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class PropFindPlugin extends ServerPlugin {

	public const REMINDER_DUE_DATE_PROPERTY = '{http://nextcloud.org/ns}reminder-due-date';

	public function __construct(
		private ReminderService $reminderService,
		private IUserSession $userSession,
	) {
	}

	public function initialize(Server $server): void {
		$server->on('preloadCollection', $this->preloadCollection(...));
		$server->on('propFind', [$this, 'propFind']);
	}

	private function preloadCollection(
		PropFind $propFind,
		ICollection $collection,
	): void {
		if ($collection instanceof Directory && $propFind->getStatus(
			static::REMINDER_DUE_DATE_PROPERTY
		) !== null) {
			$folder = $collection->getNode();
			$this->cacheFolder($folder);
		}
	}

	public function propFind(PropFind $propFind, INode $node) {
		if (!in_array(static::REMINDER_DUE_DATE_PROPERTY, $propFind->getRequestedProperties())) {
			return;
		}

		if (!($node instanceof Node)) {
			return;
		}

		$propFind->handle(
			static::REMINDER_DUE_DATE_PROPERTY,
			function () use ($node) {
				$user = $this->userSession->getUser();
				if (!($user instanceof IUser)) {
					return '';
				}

				$fileId = $node->getId();
				$reminder = $this->reminderService->getDueForUser($user, $fileId, false);
				if ($reminder === null) {
					return '';
				}

				return $reminder->getDueDate()->format(DateTimeInterface::ATOM); // ISO 8601
			},
		);
	}

	private function cacheFolder(Folder $folder): void {
		$user = $this->userSession->getUser();
		if (!($user instanceof IUser)) {
			return;
		}
		$this->reminderService->cacheFolder($user, $folder);
	}
}
