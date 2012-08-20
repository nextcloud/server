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

class OC_Share_Backend_Contact implements OCP\Share_Backend {

	const FORMAT_CONTACT = 0;

	private static $contact;

	public function isValidSource($itemSource, $uidOwner) {
		self::$contact = OC_Contacts_VCard::find($itemSource);
		if (self::$contact) {
			return true;
		}
		return false;
	}
	
	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		// TODO Get default addressbook and check for conflicts
		return self::$contact['fullname'];
	}
	
	public function formatItems($items, $format, $parameters = null) {
		$contacts = array();
		if ($format == self::FORMAT_CONTACT) {
			foreach ($items as $item) {
				$contact = OC_Contacts_VCard::find($item['item_source']);
				$contact['fullname'] = $item['item_target'];
				$contacts[] = $contact;
			}
		}
		return $contacts;
	}

}