<?php
/**
 * ownCloud - OC_Connector_Sabre_CalDAV_UserCalendars
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus (thomas@tanghus.net)
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
 *
 */

/**
 * This class overrides Sabre_CalDAV_UserCalendars::getChildren()
 * to instantiate OC_Connector_Sabre_CalDAV_Calendars.
*/
class OC_Connector_Sabre_CalDAV_UserCalendars extends Sabre_CalDAV_UserCalendars {

	/**
	* Returns a list of calendars
	*
	* @return array
	*/
	public function getChildren() {

		$calendars = $this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']);
		$objs = array();
		foreach($calendars as $calendar) {
			$objs[] = new OC_Connector_Sabre_CalDAV_Calendar($this->principalBackend, $this->caldavBackend, $calendar);
		}
		$objs[] = new Sabre_CalDAV_Schedule_Outbox($this->principalInfo['uri']);
		return $objs;

	}

}