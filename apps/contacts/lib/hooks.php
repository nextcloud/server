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
	 * @brief Add default Addressbook for a certain user
	 * @param paramters parameters from postCreateUser-Hook
	 * @return array
	 */
	static public function createUser($parameters) {
		OC_Contacts_Addressbook::add($parameters['uid'],'default','Default Address Book');
		return true;
	}

	/**
	 * @brief Deletes all Addressbooks of a certain user
	 * @param paramters parameters from postDeleteUser-Hook
	 * @return array
	 */
	static public function deleteUser($parameters) {
		$addressbooks = OC_Contacts_Addressbook::all($parameters['uid']);

		foreach($addressbooks as $addressbook) {
			OC_Contacts_Addressbook::delete($addressbook['id']);
		}

		return true;
	}

	static public function getCalenderSources($parameters) {
		$base_url = OCP\Util::linkTo('calendar', 'ajax/events.php').'?calendar_id=';
		foreach(OC_Contacts_Addressbook::all(OCP\USER::getUser()) as $addressbook) {
			$parameters['sources'][] =
				array(
					'url' => $base_url.'birthday_'. $addressbook['id'],
					'backgroundColor' => '#cccccc',
					'borderColor' => '#888',
					'textColor' => 'black',
					'cache' => true,
					'editable' => false,
				);
		}
	}

	static public function getBirthdayEvents($parameters) {
		$name = $parameters['calendar_id'];
		if (strpos('birthday_', $name) != 0) {
			return;
		}
		$info = explode('_', $name);
		$aid = $info[1];
		OC_Contacts_App::getAddressbook($aid);
		foreach(OC_Contacts_VCard::all($aid) as $card){
			$vcard = OC_VObject::parse($card['carddata']);
			if (!$vcard) {
				continue;
			}
			$birthday = $vcard->BDAY;
			if ($birthday) {
				$date = new DateTime($birthday);
				$vevent = new OC_VObject('VEVENT');
				$vevent->setDateTime('LAST-MODIFIED', new DateTime($vcard->REV));
				$vevent->setDateTime('DTSTART', $date, Sabre_VObject_Element_DateTime::DATE);
				$vevent->setString('DURATION', 'P1D');
				// DESCRIPTION?
				$vevent->setString('RRULE', 'FREQ=YEARLY');
				$title = str_replace('{name}', $vcard->getAsString('FN'), OC_Contacts_App::$l10n->t('{name}\'s Birthday'));
				$parameters['events'][] = array(
					'id' => 0,//$card['id'],
					'vevent' => $vevent,
					'repeating' => true,
					'summary' => $title,
					);
			}
		}
	}
}
