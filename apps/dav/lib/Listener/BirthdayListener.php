<?php

declare(strict_types=1);

/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class BirthdayListener implements IEventListener {
	private BirthdayService $birthdayService;

	public function __construct(BirthdayService $birthdayService) {
		$this->birthdayService = $birthdayService;
	}

	public function handle(Event $event): void {
		if ($event instanceof CardCreatedEvent || $event instanceof CardUpdatedEvent) {
			$cardData = $event->getCardData();

			$this->birthdayService->onCardChanged($event->getAddressBookId(), $cardData['uri'], $cardData['carddata']);
		}

		if ($event instanceof CardDeletedEvent) {
			$cardData = $event->getCardData();
			$this->birthdayService->onCardDeleted($event->getAddressBookId(), $cardData['uri']);
		}
	}
}
