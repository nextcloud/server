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
 * CREATE TABLE contacts_addressbooks (
 * id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 * userid VARCHAR(255) NOT NULL,
 * displayname VARCHAR(255),
 * uri VARCHAR(100),
 * description TEXT,
 * ctag INT(11) UNSIGNED NOT NULL DEFAULT '1'
 * );
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
 * This class manages our addressbooks.
 */
class OC_Contacts_Addressbook{
	public static function allAddressbooks($uid){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE userid = ?' );
		$result = $stmt->execute(array($uid));
		
		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}

		return $addressbooks;
	}
	
	public static function allAddressbooksWherePrincipalURIIs($principaluri){
		$uid = self::extractUserID($principaluri);
		return self::allAddressbooks($uid);
	}

	public static function findAddressbook($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	public static function addAddressbook($userid,$name,$description){
		$all = self::allAddressbooks($userid);
		$uris = array();
		foreach($all as $i){
			$uris[] = $i['uri'];
		}

		$uri = self::createURI($name, $uris );

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_addressbooks (userid,displayname,uri,description,ctag) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,$description,1));

		return OC_DB::insertid();
	}

	public static function addAddressbookFromDAVData($principaluri,$uri,$name,$description){
		$userid = self::extractUserID($principaluri);
		
		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_addressbooks (userid,displayname,uri,description,ctag) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,$description,1));

		return OC_DB::insertid();
	}

	public static function editAddressbook($id,$name,$description){
		// Need these ones for checking uri
		$addressbook = self::find($id);

		if(is_null($name)){
			$name = $addressbook['name'];
		}
		if(is_null($description)){
			$description = $addressbook['description'];
		}
		
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_addressbooks SET displayname=?,description=?, ctag=ctag+1 WHERE id=?' );
		$result = $stmt->execute(array($name,$description,$id));

		return true;
	}

	public static function touchAddressbook($id){
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_addressbooks SET ctag = ctag + 1 WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	public static function deleteAddressbook($id){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_addressbooks WHERE id = ?' );
		$stmt->execute(array($id));
		
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_cards WHERE addressbookid = ?' );
		$stmt->execute(array($id));

		return true;
	}

	public static function allCards($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE addressbookid = ?' );
		$result = $stmt->execute(array($id));

		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}

		return $addressbooks;
	}
	
	public static function findCard($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	public static function findCardWhereDAVDataIs($aid,$uri){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*contacts_cards WHERE addressbookid = ? AND uri = ?' );
		$result = $stmt->execute(array($aid,$uri));

		return $result->fetchRow();
	}

	public static function addCard($id,$data){
		$fn = null;
		$uri = null;
		$card = Sabre_VObject_Reader::read($data);
		foreach($card->children as $property){
			if($property->name == 'FN'){
				$fn = $property->value;
			}
			elseif(is_null($uri) && $property->name == 'UID' ){
				$uri = $property->value.'.vcf';
			}
		}
		if(is_null($uri)) $uri = self::createUID().'.vcf';

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_cards (addressbookid,fullname,carddata,uri,lastmodified) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$fn,$data,$uri,time()));

		self::touchAddressbook($id);

		return OC_DB::insertid();
	}

	public static function addCardFromDAVData($id,$uri,$data){
		$fn = null;
		$card = Sabre_VObject_Reader::read($data);
		foreach($card->children as $property){
			if($property->name == 'FN'){
				$fn = $property->value;
			}
		}

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*contacts_cards (addressbookid,fullname,carddata,uri,lastmodified) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$fn,$data,$uri,time()));

		self::touchAddressbook($id);

		return OC_DB::insertid();
	}

	public static function editCard($id, $data){
		$oldcard = self::findCard($id);
		$fn = null;
		$card = Sabre_VObject_Reader::read($data);
		foreach($card->children as $property){
			if($property->name == 'FN'){
				$fn = $property->value;
			}
		}

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET fullname = ?,carddata = ?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($fn,$data,time(),$id));

		self::touchAddressbook($oldcard['addressbookid']);

		return true;
	}

	public static function editCardFromDAVData($aid,$uri,$data){
		$oldcard = self::findCardWhereDAVDataIs($aid,$uri);

		$fn = null;
		$card = Sabre_VObject_Reader::read($data);
		foreach($card->children as $property){
			if($property->name == 'FN'){
				$fn = $property->value;
			}
		}

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET fullname = ?,carddata = ?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($fn,$data,time(),$oldcard['id']));

		self::touchAddressbook($oldcard['addressbookid']);

		return true;
	}
	
	public static function deleteCard($id){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_cards WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	public static function deleteCardFromDAVData($aid,$uri){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*contacts_cards WHERE addressbookid = ? AND uri=?' );
		$stmt->execute(array($aid,$uri));

		return true;
	}
	
	public static function createURI($name,$existing){
		$name = strtolower($name);
		$newname = $name;
		$i = 1;
		while(in_array($newname,$existing)){
			$newname = $name.$i;
			$i = $i + 1;
		}
		return $newname;
	}

	public static function createUID(){
		return substr(md5(rand().time()),0,10);
	}
	
	public static function extractUserID($principaluri){
		list($prefix,$userid) = Sabre_DAV_URLUtil::splitPath($principaluri);
		return $userid;
	}

	public static function escapeSemicolons($value){
		foreach($value as &$i ){
			$i = implode("\\\\;", explode(';', $i));
		} unset($i);
		return implode(';',$value);
	}

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
			$temp['parameters'][$parameter->name] = $parameter->value;
		}
		return $temp;
	}
}
