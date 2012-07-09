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
 * This CardDAV backend uses PDO to store addressbooks
 */
class OC_Connector_Sabre_CardDAV extends Sabre_CardDAV_Backend_Abstract {
	/**
	 * Returns the list of addressbooks for a specific user.
	 *
	 * @param string $principaluri
	 * @return array
	 */
	public function getAddressBooksForUser($principaluri) {
		$data = OC_Contacts_Addressbook::allWherePrincipalURIIs($principaluri);
		$addressbooks = array();

		foreach($data as $i) {
			$addressbooks[] = array(
				'id'  => $i['id'],
				'uri' => $i['uri'],
				'principaluri' => 'principals/'.$i['userid'],
				'{DAV:}displayname' => $i['displayname'],
				'{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => $i['description'],
				'{http://calendarserver.org/ns/}getctag' => $i['ctag'],
			);
		}

		return $addressbooks;
	}


	/**
	 * Updates an addressbook's properties
	 *
	 * See Sabre_DAV_IProperties for a description of the mutations array, as
	 * well as the return value.
	 *
	 * @param mixed $addressbookid
	 * @param array $mutations
	 * @see Sabre_DAV_IProperties::updateProperties
	 * @return bool|array
	 */
	public function updateAddressBook($addressbookid, array $mutations) {
		$name = null;
		$description = null;

		foreach($mutations as $property=>$newvalue) {
			switch($property) {
				case '{DAV:}displayname' :
					$name = $newvalue;
					break;
				case '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' :
					$description = $newvalue;
					break;
				default :
					// If any unsupported values were being updated, we must
					// let the entire request fail.
					return false;
			}
		}

		OC_Contacts_Addressbook::edit($addressbookid,$name,$description);

		return true;

	}

	/**
	 * Creates a new address book
	 *
	 * @param string $principaluri
	 * @param string $url Just the 'basename' of the url.
	 * @param array $properties
	 * @return void
	 */
	public function createAddressBook($principaluri, $url, array $properties) {

		$displayname = null;
		$description = null;

		foreach($properties as $property=>$newvalue) {

			switch($property) {
				case '{DAV:}displayname' :
					$displayname = $newvalue;
					break;
				case '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' :
					$description = $newvalue;
					break;
				default :
					throw new Sabre_DAV_Exception_BadRequest('Unknown property: ' . $property);
			}

		}

		OC_Contacts_Addressbook::addFromDAVData($principaluri,$url,$name,$description);
	}

	/**
	 * Deletes an entire addressbook and all its contents
	 *
	 * @param int $addressbookid
	 * @return void
	 */
	public function deleteAddressBook($addressbookid) {
		OC_Contacts_Addressbook::delete($addressbookid);
	}

	/**
	 * Returns all cards for a specific addressbook id.
	 *
	 * @param mixed $addressbookid
	 * @return array
	 */
	public function getCards($addressbookid) {
		$data = OC_Contacts_VCard::all($addressbookid);
		$cards = array();
		foreach($data as $i){
			$cards[] = array(
				'id' => $i['id'],
				'carddata' => $i['carddata'],
				'uri' => $i['uri'],
				'lastmodified' => $i['lastmodified'] );
		}

		return $cards;
	}

	/**
	 * Returns a specfic card
	 *
	 * @param mixed $addressbookid
	 * @param string $carduri
	 * @return array
	 */
	public function getCard($addressbookid, $carduri) {
		return OC_Contacts_VCard::findWhereDAVDataIs($addressbookid,$carduri);

	}

	/**
	 * Creates a new card
	 *
	 * @param mixed $addressbookid
	 * @param string $carduri
	 * @param string $carddata
	 * @return bool
	 */
	public function createCard($addressbookid, $carduri, $carddata) {
		OC_Contacts_VCard::addFromDAVData($addressbookid, $carduri, $carddata);
		return true;
	}

	/**
	 * Updates a card
	 *
	 * @param mixed $addressbookid
	 * @param string $carduri
	 * @param string $carddata
	 * @return bool
	 */
	public function updateCard($addressbookid, $carduri, $carddata) {
		return OC_Contacts_VCard::editFromDAVData($addressbookid, $carduri, $carddata);
	}

	/**
	 * Deletes a card
	 *
	 * @param mixed $addressbookid
	 * @param string $carduri
	 * @return bool
	 */
	public function deleteCard($addressbookid, $carduri) {
		return OC_Contacts_VCard::deleteFromDAVData($addressbookid, $carduri);
	}
}
