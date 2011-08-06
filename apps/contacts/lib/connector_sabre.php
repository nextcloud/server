<?php

/**
 * PDO CardDAV backend
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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
		$data = OC_Contacts_Addressbook::allAddressbooksWherePrincipalURIIs($principaluri);
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

		OC_Contacts_Addressbook::editAddressbook($addressbookid,$name,$description);

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

		OC_Contacts_Addressbook::addAddressbookFromDAVData($principaluri,$url,$name,$description);
	}

	/**
	 * Deletes an entire addressbook and all its contents
	 *
	 * @param int $addressbookid
	 * @return void
	 */
	public function deleteAddressBook($addressbookid) {
		OC_Contacts_Addressbook::deleteAddressbook($addressbookid);
	}

	/**
	 * Returns all cards for a specific addressbook id.
	 *
	 * @param mixed $addressbookid
	 * @return array
	 */
	public function getCards($addressbookid) {
		$data = OC_Contacts_Addressbook::allCards($addressbookid);
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
		return OC_Contacts_Addressbook::findCardWhereDAVDataIs($addressbookid,$carduri);

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
		OC_Contacts_Addressbook::addCardFromDAVData($addressbookid, $carduri, $carddata);
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
		return OC_Contacts_Addressbook::editCardFromDAVData($addressbookid, $carduri, $carddata);
	}

	/**
	 * Deletes a card
	 *
	 * @param mixed $addressbookid
	 * @param string $carduri
	 * @return bool
	 */
	public function deleteCard($addressbookid, $carduri) {
		return OC_Contacts_Addressbook::deleteCardFromDAVData($addressbookid, $carduri);
	}
}
