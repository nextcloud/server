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
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE contacts_cards (
 * id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 * addressbookid INT(11) UNSIGNED NOT NULL,
 * fullname VARCHAR(255),
 * carddata TEXT,
 * uri VARCHAR(100),
 * lastmodified INT(11) UNSIGNED
 * );
 */

/**
 * This class manages our vCards
 */
class OC_Contacts_VCard{
	/**
	 * @brief Returns all cards of an address book
	 * @param integer $id
	 * @return array
	 *
	 * The cards are associative arrays. You'll find the original vCard in
	 * ['carddata']
	 */
	public static function all($id){
		$result = null;
		if(is_array($id)) {
			$id_sql = join(',', array_fill(0, count($id), '?'));
			$prep = 'SELECT * FROM *PREFIX*contacts_cards WHERE addressbookid IN ('.$id_sql.') ORDER BY fullname';
			try {
				$stmt = OC_DB::prepare( $prep );
				$result = $stmt->execute($id);
			} catch(Exception $e) {
				OC_Log::write('contacts','OC_Contacts_VCard:all:, exception: '.$e->getMessage(),OC_Log::DEBUG);
				OC_Log::write('contacts','OC_Contacts_VCard:all, ids: '.join(',', $id),OC_Log::DEBUG);
				OC_Log::write('contacts','SQL:'.$prep,OC_Log::DEBUG);
			}
		} elseif($id) {
			try {
				$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE addressbookid = ? ORDER BY fullname' );
				$result = $stmt->execute(array($id));
			} catch(Exception $e) {
				OC_Log::write('contacts','OC_Contacts_VCard:all:, exception: '.$e->getMessage(),OC_Log::DEBUG);
				OC_Log::write('contacts','OC_Contacts_VCard:all, ids: '. $id,OC_Log::DEBUG);
			}
		}
		$cards = array();
		if(!is_null($result)) {
			while( $row = $result->fetchRow()){
				$cards[] = $row;
			}
		}

		return $cards;
	}

	/**
	 * @brief Returns a card
	 * @param integer $id
	 * @return associative array
	 */
	public static function find($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	/**
	 * @brief finds a card by its DAV Data
	 * @param integer $aid Addressbook id
	 * @param string $uri the uri ('filename')
	 * @return associative array
	 */
	public static function findWhereDAVDataIs($aid,$uri){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE addressbookid = ? AND uri = ?' );
		$result = $stmt->execute(array($aid,$uri));

		return $result->fetchRow();
	}

	/** 
	* @brief Format property TYPE parameters for upgrading from v. 2.1
	* @param $property Reference to a Sabre_VObject_Property.
	* In version 2.1 e.g. a phone can be formatted like: TEL;HOME;CELL:123456789
	* This has to be changed to either TEL;TYPE=HOME,CELL:123456789 or TEL;TYPE=HOME;TYPE=CELL:123456789 - both are valid.
	*/
	public static function formatPropertyTypes(&$property) {
		foreach($property->parameters as $key=>&$parameter){
			$types = OC_Contacts_App::getTypesOfProperty($property->name);
			if(is_array($types) && in_array(strtoupper($parameter->name), array_keys($types)) || strtoupper($parameter->name) == 'PREF') {
				$property->parameters[] = new Sabre_VObject_Parameter('TYPE', $parameter->name);
			}
			unset($property->parameters[$key]);
		}
	}

	/** 
	* @brief Decode properties for upgrading from v. 2.1
	* @param $property Reference to a Sabre_VObject_Property.
	* The only encoding allowed in version 3.0 is 'b' for binary. All encoded strings
	* must therefor be decoded and the parameters removed.
	*/
	public static function decodeProperty(&$property) {
		// Check out for encoded string and decode them :-[
		foreach($property->parameters as $key=>&$parameter){
			if(strtoupper($parameter->name) == 'ENCODING') {
				if(strtoupper($parameter->value) == 'QUOTED-PRINTABLE') { // what kind of other encodings could be used?
					$property->value = quoted_printable_decode($property->value);
					unset($property->parameters[$key]);
				}
			} elseif(strtoupper($parameter->name) == 'CHARSET') {
					unset($property->parameters[$key]);
			}
		}
	}

	/**
	* @brief Tries to update imported VCards to adhere to rfc2426 (VERSION: 3.0)
	* @param vcard An OC_VObject of type VCARD (passed by reference).
	*/
	protected static function updateValuesFromAdd(&$vcard) { // any suggestions for a better method name? ;-)
		$stringprops = array('N', 'FN', 'ORG', 'NICK', 'ADR', 'NOTE');
		$typeprops = array('ADR', 'TEL', 'EMAIL');
		$upgrade = false;
		$fn = $n = $uid = $email = null;
		$version = $vcard->getAsString('VERSION');
		// Add version if needed
		if($version && $version < '3.0') {
			$upgrade = true;
			OC_Log::write('contacts','OC_Contacts_VCard::updateValuesFromAdd. Updating from version: '.$version,OC_Log::DEBUG);
		}
		foreach($vcard->children as &$property){
			// Decode string properties and remove obsolete properties.
			if($upgrade && in_array($property->name, $stringprops)) {
				self::decodeProperty($property);
			}
			// Fix format of type parameters.
			if($upgrade && in_array($property->name, $typeprops)) {
				OC_Log::write('contacts','OC_Contacts_VCard::updateValuesFromAdd. before: '.$property->serialize(),OC_Log::DEBUG);
				self::formatPropertyTypes($property);
				OC_Log::write('contacts','OC_Contacts_VCard::updateValuesFromAdd. after: '.$property->serialize(),OC_Log::DEBUG);
			}
			if($property->name == 'FN'){
				$fn = $property->value;
			}
			if($property->name == 'N'){
				$n = $property->value;
			}
			if($property->name == 'UID'){
				$uid = $property->value;
			}
			if($property->name == 'EMAIL' && is_null($email)){ // only use the first email as substitute for missing N or FN.
				$email = $property->value;
			}
		}
		// Check for missing 'N', 'FN' and 'UID' properties
		if(!$fn) {
			if($n && $n != ';;;;'){
				$fn = join(' ', array_reverse(array_slice(explode(';', $n), 0, 2)));
			} elseif($email) {
				$fn = $email;
			} else {
				$fn = 'Unknown Name';
			}
			$vcard->setString('FN', $fn);
			OC_Log::write('contacts','OC_Contacts_VCard::updateValuesFromAdd. Added missing \'FN\' field: '.$fn,OC_Log::DEBUG);
		}
		if(!$n || $n = ';;;;'){ // Fix missing 'N' field. Ugly hack ahead ;-)
			$slice = array_reverse(array_slice(explode(' ', $fn), 0, 2)); // Take 2 first name parts of 'FN' and reverse.
			if(count($slice) < 2) { // If not enought, add one more...
				$slice[] = "";
			}
			$n = implode(';', $slice).';;;';
			$vcard->setString('N', $n);
			OC_Log::write('contacts','OC_Contacts_VCard::updateValuesFromAdd. Added missing \'N\' field: '.$n,OC_Log::DEBUG);
		}
		if(!$uid) {
			$vcard->setUID();
			OC_Log::write('contacts','OC_Contacts_VCard::updateValuesFromAdd. Added missing \'UID\' field: '.$uid,OC_Log::DEBUG);
		}
		$vcard->setString('VERSION','3.0');
		// Add product ID is missing.
		$prodid = trim($vcard->getAsString('PRODID'));
		if(!$prodid) {
			$appinfo = OC_App::getAppInfo('contacts');
			$prodid = '-//ownCloud//NONSGML '.$appinfo['name'].' '.$appinfo['version'].'//EN';
			$vcard->setString('PRODID', $prodid);
		}
		$now = new DateTime;
		$vcard->setString('REV', $now->format(DateTime::W3C));
	}

	/**
	 * @brief Adds a card
	 * @param integer $id Addressbook id
	 * @param string $data  vCard file
	 * @return insertid on success or null if card is not parseable.
	 */
	public static function add($id,$data){
		$fn = null;

		$card = OC_VObject::parse($data);
		if(!is_null($card)){
			OC_Contacts_App::$categories->loadFromVObject($card);
			self::updateValuesFromAdd($card);
			$data = $card->serialize();
		}
		else{
			OC_Log::write('contacts','OC_Contacts_VCard::add. Error parsing VCard: '.$data,OC_Log::ERROR);
			return null; // Ditch cards that can't be parsed by Sabre.
		};

		$fn = $card->getAsString('FN');
		$uid = $card->getAsString('UID');
		$uri = $uid.'.vcf';
		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_cards (addressbookid,fullname,carddata,uri,lastmodified) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$fn,$data,$uri,time()));
		$newid = OC_DB::insertid('*PREFIX*contacts_cards');

		OC_Contacts_Addressbook::touch($id);

		return $newid;
	}

	/**
	 * @brief Adds a card with the data provided by sabredav
	 * @param integer $id Addressbook id
	 * @param string $uri   the uri the card will have
	 * @param string $data  vCard file
	 * @return insertid
	 */
	public static function addFromDAVData($id,$uri,$data){
		$card = OC_VObject::parse($data);
		if(!is_null($card)){
			OC_Contacts_App::$categories->loadFromVObject($card);
			self::updateValuesFromAdd($card);
			$data = $card->serialize();
		} else {
			OC_Log::write('contacts','OC_Contacts_VCard::addFromDAVData. Error parsing VCard: '.$data, OC_Log::ERROR);
			return null; // Ditch cards that can't be parsed by Sabre.
		};
		$fn = $card->getAsString('FN');

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_cards (addressbookid,fullname,carddata,uri,lastmodified) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$fn,$data,$uri,time()));
		$newid = OC_DB::insertid('*PREFIX*contacts_cards');

		OC_Contacts_Addressbook::touch($id);

		return $newid;
	}

	/**
	 * @brief edits a card
	 * @param integer $id id of card
	 * @param string $data  vCard file
	 * @return boolean
	 */
	public static function edit($id, $data){
		$oldcard = self::find($id);
		$fn = null;

		$card = OC_VObject::parse($data);
		if(!is_null($card)){
			OC_Contacts_App::$categories->loadFromVObject($card);
			foreach($card->children as $property){
				if($property->name == 'FN'){
					$fn = $property->value;
					break;
				}
			}
		} else {
			return false;
		}
		$now = new DateTime;
		$card->setString('REV', $now->format(DateTime::W3C));
		$data = $card->serialize();

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET fullname = ?,carddata = ?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($fn,$data,time(),$id));

		OC_Contacts_Addressbook::touch($oldcard['addressbookid']);

		return true;
	}

	/**
	 * @brief edits a card with the data provided by sabredav
	 * @param integer $id Addressbook id
	 * @param string $uri   the uri of the card
	 * @param string $data  vCard file
	 * @return boolean
	 */
	public static function editFromDAVData($aid,$uri,$data){
		$oldcard = self::findWhereDAVDataIs($aid,$uri);

		$fn = null;
		$card = OC_VObject::parse($data);
		if(!is_null($card)){
			OC_Contacts_App::$categories->loadFromVObject($card);
			foreach($card->children as $property){
				if($property->name == 'FN'){
					$fn = $property->value;
					break;
				}
			}
		}
		$now = new DateTime;
		$card->setString('REV', $now->format(DateTime::W3C));
		$data = $card->serialize();

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET fullname = ?,carddata = ?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($fn,$data,time(),$oldcard['id']));

		OC_Contacts_Addressbook::touch($oldcard['addressbookid']);

		return true;
	}

	/**
	 * @brief deletes a card
	 * @param integer $id id of card
	 * @return boolean
	 */
	public static function delete($id){
		// FIXME: Add error checking.
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_cards WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief Creates a UID
	 * @return string
	 */
	public static function createUID(){
		return substr(md5(rand().time()),0,10);
	}

	/**
	 * @brief deletes a card with the data provided by sabredav
	 * @param integer $aid Addressbook id
	 * @param string $uri the uri of the card
	 * @return boolean
	 */
	public static function deleteFromDAVData($aid,$uri){
		// FIXME: Add error checking. Deleting a card gives an Kontact/Akonadi error.
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_cards WHERE addressbookid = ? AND uri=?' );
		$stmt->execute(array($aid,$uri));
		OC_Contacts_Addressbook::touch($aid);

		return true;
	}

	/**
	 * @brief Data structure of vCard
	 * @param object $property
	 * @return associative array
	 *
	 * look at code ...
	 */
	public static function structureContact($object){
		$details = array();
		foreach($object->children as $property){
			$temp = self::structureProperty($property);
			if(array_key_exists($property->name,$details)){
				$details[$property->name][] = $temp;
			}
			else{
				$details[$property->name] = array($temp);
			}
		}
		return $details;
	}

	/**
	 * @brief Data structure of properties
	 * @param object $property
	 * @return associative array
	 *
	 * returns an associative array with
	 * ['name'] name of property
	 * ['value'] htmlspecialchars escaped value of property
	 * ['parameters'] associative array name=>value
	 * ['checksum'] checksum of whole property
	 * NOTE: $value is not escaped anymore. It shouldn't make any difference
	 * but we should look out for any problems.
	 */
	public static function structureProperty($property){
		$value = $property->value;
		//$value = htmlspecialchars($value);
		if($property->name == 'ADR' || $property->name == 'N'){
			$value = OC_VObject::unescapeSemicolons($value);
		}
		$temp = array(
			'name' => $property->name,
			'value' => $value,
			'parameters' => array(),
			'checksum' => md5($property->serialize()));
		foreach($property->parameters as $parameter){
			// Faulty entries by kaddressbook
			// Actually TYPE=PREF is correct according to RFC 2426
			// but this way is more handy in the UI. Tanghus.
			if($parameter->name == 'TYPE' && $parameter->value == 'PREF'){
				$parameter->name = 'PREF';
				$parameter->value = '1';
			}
			// NOTE: Apparently Sabre_VObject_Reader can't always deal with value list parameters
			// like TYPE=HOME,CELL,VOICE. Tanghus.
			if ($property->name == 'TEL' && $parameter->name == 'TYPE'){
				if (isset($temp['parameters'][$parameter->name])){
					$temp['parameters'][$parameter->name][] = $parameter->value;
				}
				else{
					$temp['parameters'][$parameter->name] = array($parameter->value);
				}
			}
			else{
				$temp['parameters'][$parameter->name] = $parameter->value;
			}
		}
		return $temp;
	}

	/**
	 * @brief Move card(s) to an address book
	 * @param integer $aid Address book id
	 * @param $id Array or integer of cards to be moved.
	 * @return boolean
	 *
	 */
	public static function moveToAddressBook($aid, $id){
		OC_Contacts_App::getAddressbook($aid); // check for user ownership.
		if(is_array($id)) {
			$id_sql = join(',', array_fill(0, count($id), '?'));
			$prep = 'UPDATE *PREFIX*contacts_cards SET addressbookid = ? WHERE id IN ('.$id_sql.')';
			try {
				$stmt = OC_DB::prepare( $prep );
				//$aid = array($aid);
				$vals = array_merge((array)$aid, $id);
				$result = $stmt->execute($vals);
			} catch(Exception $e) {
				OC_Log::write('contacts','OC_Contacts_VCard::moveToAddressBook:, exception: '.$e->getMessage(),OC_Log::DEBUG);
				OC_Log::write('contacts','OC_Contacts_VCard::moveToAddressBook, ids: '.join(',', $vals),OC_Log::DEBUG);
				OC_Log::write('contacts','SQL:'.$prep,OC_Log::DEBUG);
				return false;
			}
		} else {
			try {
				$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET addressbookid = ? WHERE id = ?' );
				$result = $stmt->execute(array($aid, $id));
			} catch(Exception $e) {
				OC_Log::write('contacts','OC_Contacts_VCard::moveToAddressBook:, exception: '.$e->getMessage(),OC_Log::DEBUG);
				OC_Log::write('contacts','OC_Contacts_VCard::moveToAddressBook, id: '.$id,OC_Log::DEBUG);
				return false;
			}
		}

		OC_Contacts_Addressbook::touch($aid);
		return true;
	}

}
