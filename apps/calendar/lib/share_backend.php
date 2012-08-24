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

class OC_Share_Backend_Calendar extends OCP\Share_Backend {

	public function getSource($item, $uid) {
		$query = OCP\DB::prepare('SELECT `id` FROM `*PREFIX*calendar_calendars` WHERE `userid` = ? AND `displayname` = ?',1);
		return $query->execute(array($uid, $item))->fetchAll();
	}

	public function generateTarget($item, $uid) {
		
	}

	public function getItems($sources) {
		
	}

}

class OC_Share_Backend_Event extends OCP\Share_Backend {

}


?>