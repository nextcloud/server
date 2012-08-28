<?php
/**
 * ownCloud - OC_Connector_Sabre_CalDAV_CalendarObject
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
 * This class overrides Sabre_CalDAV_CalendarObject::getACL()
 * to return read/write permissions based on user and shared state.
*/
class OC_Connector_Sabre_CalDAV_CalendarObject extends Sabre_CalDAV_CalendarObject {

	/**
	* Returns a list of ACE's for this node.
	*
	* Each ACE has the following properties:
	*   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
	*     currently the only supported privileges
	*   * 'principal', a url to the principal who owns the node
	*   * 'protected' (optional), indicating that this ACE is not allowed to
	*      be updated.
	*
	* @return array
	*/
	public function getACL() {

		$readprincipal = $this->getOwner();
		$writeprincipal = $this->getOwner();
		$uid = OC_Calendar_Calendar::extractUserID($this->getOwner());

		if($uid != OCP\USER::getUser()) {
			$sharedCalendar = OCP\Share::getItemSharedWithBySource('calendar', $this->$calendarInfo['id']);
			if ($sharedCalendar && ($sharedCalendar['permissions'] & OCP\Share::PERMISSION_READ)) {
				$readprincipal = 'principals/' . OCP\USER::getUser();
			}
			if ($sharedCalendar && ($sharedCalendar['permissions'] & OCP\Share::PERMISSION_UPDATE)) {
				$writeprincipal = 'principals/' . OCP\USER::getUser();
			}
		}

		return array(
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal,
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}write',
				'principal' => $writeprincipal,
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal . '/calendar-proxy-write',
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}write',
				'principal' => $writeprincipal . '/calendar-proxy-write',
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal . '/calendar-proxy-read',
				'protected' => true,
			),
		);

	}

}