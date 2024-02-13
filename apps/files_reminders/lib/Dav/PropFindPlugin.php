<?php

declare(strict_types=1);

/**
 * @copyright 2024 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
