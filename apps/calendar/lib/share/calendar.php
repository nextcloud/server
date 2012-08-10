<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
* Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
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
	const FORMAT_CALENDAR = 1;

	/**
	* @brief Get the source of the item to be stored in the database
	* @param string Item
	* @param string Owner of the item
	* @return mixed|array|false Source
	*
	* Return an array if the item is file dependent, the array needs two keys: 'item' and 'file'
	* Return false if the item does not exist for the user
	*
	* The formatItems() function will translate the source returned back into the item
	*/
	public function isValidSource($itemSource, $uidOwner) {
		$calendar = OC_Calendar_App::getCalendar( $itemSource );
		if ($calendar || $calendar['userid'] != $uidOwner) {
			return false;
		}
		return true;
	}

	/**
	* @brief Get a unique name of the item for the specified user
	* @param string Item
	* @param string|false User the item is being shared with
	* @param array|null List of similar item names already existing as shared items
	* @return string Target name
	*
	* This function needs to verify that the user does not already have an item with this name.
	* If it does generate a new name e.g. name_#
	*/
	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		$calendar = OC_Calendar_App::getCalendar( $itemSource );
		$user_calendars = array();
		foreach(OC_Contacts_Addressbook::all($uid) as $user_calendar) {
			$user_calendars[] = $user_calendar['displayname'];
		}
		$name = $calendar['userid']."'s ".$calendar['displayname'];
		$suffix = '';
		while (in_array($name.$suffix, $user_calendars)) {
			$suffix++;
		}
		
		return $name.$suffix;
	}

	/**
	* @brief Converts the shared item sources back into the item in the specified format
	* @param array Shared items
	* @param int Format
	* @return ?
	*
	* The items array is a 3-dimensional array with the item_source as the first key and the share id as the second key to an array with the share info.
	* The key/value pairs included in the share info depend on the function originally called:
	* If called by getItem(s)Shared: id, item_type, item, item_source, share_type, share_with, permissions, stime, file_source
	* If called by getItem(s)SharedWith: id, item_type, item, item_source, item_target, share_type, share_with, permissions, stime, file_source, file_target
	* This function allows the backend to control the output of shared items with custom formats.
	* It is only called through calls to the public getItem(s)Shared(With) functions.
	*/
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
		$query = OCP\DB::prepare('SELECT id FROM *PREFIX*calendar_objects WHERE calendarid = ?');
		$result = $query->execute(array($itemSource));
		$sources = array();
		while ($object = $result->fetchRow()) {
			$sources[] = $object['id'];
		}
		return $sources;
	}

}