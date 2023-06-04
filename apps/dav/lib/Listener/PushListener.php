<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
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

use OCA\DAV\Db\PushKeyMapper;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Push\PushTransportManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class PushListener implements IEventListener {

	public function __construct(private PushTransportManager $pushTransportManager, private PushKeyMapper $pushKeyMapper) {
	}

	public function handle(Event $event): void {
		if ($event instanceof CalendarObjectCreatedEvent || $event instanceof CalendarObjectUpdatedEvent) {
			$data = $event->getCalendarData();
			$this->notify($data['uri'], $data['principaluri']);

		} elseif ($event instanceof CardCreatedEvent || $event instanceof CardUpdatedEvent || $event instanceof CardDeletedEvent) {
			$data = $event->getAddressBookData();
			$this->notify($data['uri'], $data['principaluri']);
		}
	}

	/**
	 * @return string[]
	 */
	private function pushKeysForData(string $principalUri, string $uri): array {
		$pushKeys = [];
		$pushKeys[] = $this->pushKeyMapper->getForPrincipal($principalUri);
		$pushKeys[] = $this->pushKeyMapper->getForUri($principalUri, $uri);
		return $pushKeys;
	}

	private function notify(string $principalUri, string $uri): array {
		$pushKeys = $this->pushKeysForData($principalUri, $uri);
		foreach ($pushKeys as $pushKey) {
			foreach ($this->pushTransportManager->getPushTransportProviders() as $pushTransportProvider) {
				$pushTransportProvider->davNotify($pushKey);
			}
		}
	}
}
