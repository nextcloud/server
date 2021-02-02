<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\DAV\Listener;

use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use function strlen;
use function strpos;
use function substr;

class CalendarContactInteractionListener implements IEventListener {
	private const URI_USERS = 'principals/users/';

	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var IUserSession */
	private $userManager;

	/** @var Principal */
	private $principalConnector;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IEventDispatcher $dispatcher,
								IUserSession $userManager,
								Principal $principalConnector,
								LoggerInterface $logger) {
		$this->dispatcher = $dispatcher;
		$this->userManager = $userManager;
		$this->principalConnector = $principalConnector;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (($user = $this->userManager->getUser()) === null) {
			// Without user context we can't do anything
			return;
		}

		if ($event instanceof CalendarObjectCreatedEvent || $event instanceof CalendarObjectUpdatedEvent) {
			// users: href => principal:principals/users/admin
			// TODO: parse (email) attendees from the VCARD
			foreach ($event->getShares() as $share) {
				if (!isset($share['href'])) {
					continue;
				}
				$this->emitFromUri($share['href'], $user);
			}
		}

		if ($event instanceof CalendarShareUpdatedEvent && !empty($event->getAdded())) {
			// group: href => principal:principals/groups/admin
			// users: href => principal:principals/users/admin
			foreach ($event->getAdded() as $added) {
				if (!isset($added['href'])) {
					// Nothing to work with
					continue;
				}
				$this->emitFromUri($added['href'], $user);
			}
		}
	}

	private function emitFromUri(string $uri, IUser $user): void {
		$principal = $this->principalConnector->findByUri(
			$uri,
			$this->principalConnector->getPrincipalPrefix()
		);
		if ($principal === null) {
			// Invalid principal
			return;
		}
		if (strpos($principal, self::URI_USERS) !== 0) {
			// Not a user principal
			return;
		}

		$uid = substr($principal, strlen(self::URI_USERS));
		$this->dispatcher->dispatchTyped(
			(new ContactInteractedWithEvent($user))->setUid($uid)
		);
	}
}
