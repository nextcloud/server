<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
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
 * This class contains all hooks.
 */
class OC_Contacts_Hooks{
	/**
	 * @brief Deletes all Addressbooks of a certain user
	 * @param paramters parameters from postDeleteUser-Hook
	 * @return array
	 */
	public function deleteUser($parameters) {
		$addressbooks = OC_Contacts_Addressbook::all($parameters['uid']);

		foreach($addressbooks as $addressbook) {
			OC_Contacts_Addressbook::delete($addressbook['id']);
		}

		return true;
	}
}
