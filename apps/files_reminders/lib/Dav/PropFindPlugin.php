<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Dav;

use DateTimeInterface;
use OCA\DAV\Connector\Sabre\Node;
use OCA\FilesReminders\Service\ReminderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;
use OCP\IUserSession;
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
		$server->on('propFind', [$this, 'propFind']);
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
				try {
					$reminder = $this->reminderService->getDueForUser($user, $fileId);
				} catch (DoesNotExistException $e) {
					return '';
				}

				return $reminder->getDueDate()->format(DateTimeInterface::ATOM); // ISO 8601
			},
		);
	}
}
