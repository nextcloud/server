<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

class OC_Share_Backend_Calendar implements OCP\Share_Backend_Collection {

	const FORMAT_CALENDAR = 0;

	private static $calendar;

	public function isValidSource($itemSource, $uidOwner) {
		if (self::$calendar = OC_Calendar_App::getCalendar($itemSource)) {
			return true;
		}
		return false;
	}

	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		if (isset(self::$calendar)) {
			return self::$calendar['displayname'];
		}
		return false;
	}

	public function formatItems($items, $format, $parameters = null) {
		$calendars = array();
		if ($format == self::FORMAT_CALENDAR) {
			foreach ($items as $item) {
				$calendar = OC_Calendar_App::getCalendar($item['item_source'], false);
				// TODO: really check $parameters['permissions'] == 'rw'/'r'
				if ($parameters['permissions'] == 'rw') {
					continue; // TODO
				}
				$calendar['displaynamename'] = $item['item_target'];
				$calendar['calendarid'] = $calendar['id'];
				$calendar['owner'] = $calendar['userid'];
				$calendars[] = $calendar;
			}
		}
		return $calendars;
	}

	public function getChildren($itemSource) {
		// TODO
	}

}