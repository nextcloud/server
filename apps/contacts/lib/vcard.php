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
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE addressbookid = ?' );
		$result = $stmt->execute(array($id));

		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}

		return $addressbooks;
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
	 * @brief Adds a card
	 * @param integer $id Addressbook id
	 * @param string $data  vCard file
	 * @return insertid
	 */
	public static function add($id,$data){
		$fn = null;
		$uri = null;

		$card = self::parse($data);
		if(!is_null($card)){
			// VCARD must have a version
			$hasversion = false;
			foreach($card->children as $property){
				if($property->name == 'FN'){
					$fn = $property->value;
				}
				elseif($property->name == 'VERSION'){
					$hasversion = true;
				}
				elseif(is_null($uri) && $property->name == 'UID' ){
					$uri = $property->value.'.vcf';
				}
			}
			if(is_null($uri)){
				$uid = self::createUID();
				$uri = $uid.'.vcf';
				$card->add(new Sabre_VObject_Property('UID',$uid));
				$data = $card->serialize();
			};
			// Add version if needed
			if(!$hasversion){
				$card->add(new Sabre_VObject_Property('VERSION','3.0'));
				$data = $card->serialize();
			}
		}
		else{
			// that's hard. Creating a UID and not saving it
			$uid = self::createUID();
			$uri = $uid.'.vcf';
		};

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_cards (addressbookid,fullname,carddata,uri,lastmodified) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$fn,$data,$uri,time()));

		OC_Contacts_Addressbook::touch($id);

		return OC_DB::insertid('*PREFIX*contacts_cards');
	}

	/**
	 * @brief Adds a card with the data provided by sabredav
	 * @param integer $id Addressbook id
	 * @param string $uri   the uri the card will have
	 * @param string $data  vCard file
	 * @return insertid
	 */
	public static function addFromDAVData($id,$uri,$data){
		$fn = null;
		$card = self::parse($data);
		if(!is_null($card)){
			foreach($card->children as $property){
				if($property->name == 'FN'){
					$fn = $property->value;
				}
			}
		}

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_cards (addressbookid,fullname,carddata,uri,lastmodified) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$fn,$data,$uri,time()));

		OC_Contacts_Addressbook::touch($id);

		return OC_DB::insertid('*PREFIX*contacts_cards');
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

		$card = self::parse($data);
		if(!is_null($card)){
			foreach($card->children as $property){
				if($property->name == 'FN'){
					$fn = $property->value;
				}
			}
		}

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
		$card = self::parse($data);
		if(!is_null($card)){
			foreach($card->children as $property){
				if($property->name == 'FN'){
					$fn = $property->value;
				}
			}
		}

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
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_cards WHERE addressbookid = ? AND uri=?' );
		$stmt->execute(array($aid,$uri));

		return true;
	}

	/**
	 * @brief Escapes semicolons
	 * @param string $value
	 * @return string
	 */
	public static function escapeSemicolons($value){
		foreach($value as &$i ){
			$i = implode("\\\\;", explode(';', $i));
		}
		return implode(';',$value);
	}

	/**
	 * @brief Creates an array out of a multivalue property
	 * @param string $value
	 * @return array
	 */
	public static function unescapeSemicolons($value){
		$array = explode(';',$value);
		for($i=0;$i<count($array);$i++){
			if(substr($array[$i],-2,2)=="\\\\"){
				if(isset($array[$i+1])){
					$array[$i] = substr($array[$i],0,count($array[$i])-2).';'.$array[$i+1];
					unset($array[$i+1]);
				}
				else{
					$array[$i] = substr($array[$i],0,count($array[$i])-2).';';
				}
				$i = $i - 1;
			}
		}
		return $array;
	}

	/**
	 * @brief Add property to vcard object
	 * @param object $vcard
	 * @param object $name of property
	 * @param object $value of property
	 * @param object $paramerters of property
	 */
	public static function addVCardProperty($vcard, $name, $value, $parameters=array()){
		if(is_array($value)){
			$value = OC_Contacts_VCard::escapeSemicolons($value);
		}
		$property = new Sabre_VObject_Property( $name, $value );
		$parameternames = array_keys($parameters);
		foreach($parameternames as $i){
			$values = $parameters[$i];
			if (!is_array($values)){
				$values = array($values);
			}
			foreach($values as $value){
				$property->add($i, $value);
			}
		}

		$vcard->add($property);
		return $property;
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
	 */
	public static function structureProperty($property){
		$value = $property->value;
		$value = htmlspecialchars($value);
		if($property->name == 'ADR' || $property->name == 'N'){
			$value = self::unescapeSemicolons($value);
		}
		$temp = array(
			'name' => $property->name,
			'value' => $value,
			'parameters' => array(),
			'checksum' => md5($property->serialize()));
		foreach($property->parameters as $parameter){
			// Faulty entries by kaddressbook
			if($parameter->name == 'TYPE' && $parameter->value == 'PREF'){
				$parameter->name = 'PREF';
				$parameter->value = '1';
			}
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
	 * @brief Parses a vcard file
	 * @param string vCard
	 * @return Sabre_VObject or null
	 *
	 * Will retun the vobject if sabre DAV is able to parse the file.
	 */
	public static function parse($data){
		try {
			$card = Sabre_VObject_Reader::read($data);
			return $card;
		} catch (Exception $e) {
			return null;
		}
	}
	public static function getTypesOfProperty($l, $prop){
		switch($prop){
		case 'ADR':
			return array(
				'WORK' => $l->t('Work'),
				'HOME' => $l->t('Home'),
			);
		case 'TEL':
			return array(
				'HOME'  =>  $l->t('Home'),
				'CELL'  =>  $l->t('Mobile'),
				'WORK'  =>  $l->t('Work'),
				'TEXT'  =>  $l->t('Text'),
				'VOICE' =>  $l->t('Voice'),
				'FAX'   =>  $l->t('Fax'),
				'VIDEO' =>  $l->t('Video'),
				'PAGER' =>  $l->t('Pager'),
			);
		}
	}
}
