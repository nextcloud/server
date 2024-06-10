<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\ViewOnly;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<BeforeZipCreatedEvent|Event>
 */
class BeforeZipCreatedListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeZipCreatedEvent)) {
			return;
		}

		$dir = $event->getDirectory();
		$files = $event->getFiles();

		$pathsToCheck = [];
		foreach ($files as $file) {
			$pathsToCheck[] = $dir . '/' . $file;
		}

		// Check only for user/group shares. Don't restrict e.g. share links
		$user = $this->userSession->getUser();
		if ($user) {
			$viewOnlyHandler = new ViewOnly(
				$this->rootFolder->getUserFolder($user->getUID())
			);
			if (!$viewOnlyHandler->check($pathsToCheck)) {
				$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
				$event->setSuccessful(false);
			} else {
				$event->setSuccessful(true);
			}
		} else {
			$event->setSuccessful(true);
		}
	}
}
