<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2022 Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\CalDAV;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\Component\VTodo;
use function max;

class EventComparisonService {

	/** @var string[] */
	private const EVENT_DIFF = [
		'RECURRENCE-ID',
		'RRULE',
		'SEQUENCE',
		'LAST-MODIFIED'
	];


	/**
	 * If found, remove the event from $eventsToFilter that
	 * is identical to the passed $filterEvent
	 * and return whether an identical event was found
	 *
	 * This function takes into account the SEQUENCE,
	 * RRULE, RECURRENCE-ID and LAST-MODIFIED parameters
	 *
	 * @param VEvent $filterEvent
	 * @param array $eventsToFilter
	 * @return bool true if there was an identical event found and removed, false if there wasn't
	 */
	private function removeIfUnchanged(VEvent $filterEvent, array &$eventsToFilter): bool {
		$filterEventData = [];
		foreach(self::EVENT_DIFF as $eventDiff) {
			$filterEventData[] = IMipService::readPropertyWithDefault($filterEvent, $eventDiff, '');
		}

		/** @var VEvent $component */
		foreach ($eventsToFilter as $k => $eventToFilter) {
			$eventToFilterData = [];
			foreach(self::EVENT_DIFF as $eventDiff) {
				$eventToFilterData[] = IMipService::readPropertyWithDefault($eventToFilter, $eventDiff, '');
			}
			// events are identical and can be removed
			if (empty(array_diff($filterEventData, $eventToFilterData))) {
				unset($eventsToFilter[$k]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Compare two VCalendars with each other and find all changed elements
	 *
	 * Returns an array of old and new events
	 *
	 * Old events are only detected if they are also changed
	 * If there is no corresponding old event for a VEvent, it
	 * has been newly created
	 *
	 * @param VCalendar $new
	 * @param VCalendar|null $old
	 * @return array<string, VEvent[]>
	 */
	public function findModified(VCalendar $new, ?VCalendar $old): array {
		$newEventComponents = $new->getComponents();

		foreach ($newEventComponents as $k => $event) {
			if(!$event instanceof VEvent) {
				unset($newEventComponents[$k]);
			}
		}

		if(empty($old)) {
			return ['old' => null, 'new' => $newEventComponents];
		}

		$oldEventComponents = $old->getComponents();
		if(is_array($oldEventComponents) && !empty($oldEventComponents)) {
			foreach ($oldEventComponents as $k => $event) {
				if(!$event instanceof VEvent) {
					unset($oldEventComponents[$k]);
					continue;
				}
				if($this->removeIfUnchanged($event, $newEventComponents)) {
					unset($oldEventComponents[$k]);
				}
			}
		}

		return ['old' => array_values($oldEventComponents), 'new' => array_values($newEventComponents)];
	}
}
