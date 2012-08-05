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
 */
/**
 * This class manages our addressbooks.
 */
class OC_Contacts_Addressbook{
	/**
	 * @brief Returns the list of addressbooks for a specific user.
	 * @param string $uid
	 * @return array
	 */
	public static function all($uid){
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE userid = ? ORDER BY displayname' );
		$result = $stmt->execute(array($uid));

		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}

		return $addressbooks;
	}

	/**
	 * @brief Returns the list of addressbooks for a principal (DAV term of user)
	 * @param string $principaluri
	 * @return array
	 */
	public static function allWherePrincipalURIIs($principaluri){
		$uid = self::extractUserID($principaluri);
		return self::all($uid);
	}

	/**
	 * @brief Gets the data of one address book
	 * @param integer $id
	 * @return associative array
	 */
	public static function find($id){
		try {
			$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE id = ?' );
			$result = $stmt->execute(array($id));
			return $result->fetchRow();
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts', __CLASS__.'::'.__METHOD__.', exception: '.$e->getMessage(), OCP\Util::ERROR);
			OCP\Util::writeLog('contacts', __CLASS__.'::'.__METHOD__.', id: '.$id, OCP\Util::DEBUG);
			return false;
		}
	}

	/**
	 * @brief Creates a new address book
	 * @param string $userid
	 * @param string $name
	 * @param string $description
	 * @return insertid
	 */
	public static function add($userid,$name,$description=''){
		$all = self::all($userid);
		$uris = array();
		foreach($all as $i){
			$uris[] = $i['uri'];
		}

		$uri = self::createURI($name, $uris );

		$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*contacts_addressbooks (userid,displayname,uri,description,ctag) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,$description,1));

		return OCP\DB::insertid('*PREFIX*contacts_addressbooks');
	}

	/**
	 * @brief Creates a new address book from the data sabredav provides
	 * @param string $principaluri
	 * @param string $uri
	 * @param string $name
	 * @param string $description
	 * @return insertid
	 */
	public static function addFromDAVData($principaluri,$uri,$name,$description){
		$userid = self::extractUserID($principaluri);

		$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*contacts_addressbooks (userid,displayname,uri,description,ctag) VALUES(?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,$description,1));

		return OCP\DB::insertid('*PREFIX*contacts_addressbooks');
	}

	/**
	 * @brief Edits an addressbook
	 * @param integer $id
	 * @param string $name
	 * @param string $description
	 * @return boolean
	 */
	public static function edit($id,$name,$description){
		// Need these ones for checking uri
		$addressbook = self::find($id);

		if(is_null($name)){
			$name = $addressbook['name'];
		}
		if(is_null($description)){
			$description = $addressbook['description'];
		}

		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*contacts_addressbooks SET displayname=?,description=?, ctag=ctag+1 WHERE id=?' );
		$result = $stmt->execute(array($name,$description,$id));

		return true;
	}

	public static function cleanArray($array, $remove_null_number = true){
		$new_array = array();

		$null_exceptions = array();

		foreach ($array as $key => $value){
			$value = trim($value);

			if($remove_null_number){
				$null_exceptions[] = '0';
			}

			if(!in_array($value, $null_exceptions) && $value != "")	{
				$new_array[] = $value;
			}
		}
		return $new_array;
	}

	/**
	 * @brief Get active addressbooks for a user.
	 * @param integer $uid User id. If null current user will be used.
	 * @return array
	 */
	public static function activeIds($uid = null){
		if(is_null($uid)){
			$uid = OCP\USER::getUser();
		}
		$prefbooks = OCP\Config::getUserValue($uid,'contacts','openaddressbooks',null);
		$prefbooks = explode(';',$prefbooks);
		for ($i = 0; $i < count($prefbooks); $i++) {
			if(!$prefbooks[$i] || !self::find($prefbooks[$i])) {
				unset($prefbooks[$i]);
			}
		}
		if(!$prefbooks){
			OCP\Util::writeLog('contacts','OC_Contacts_Addressbook:activeIds:, No active addressbooks',OCP\Util::DEBUG);
			$addressbooks = OC_Contacts_Addressbook::all($uid);
			if(count($addressbooks) == 0){
				OCP\Util::writeLog('contacts','OC_Contacts_Addressbook:activeIds:, No addressbooks',OCP\Util::DEBUG);
				$id = self::add($uid,'default','Default Address Book');
				OCP\Util::writeLog('contacts','OC_Contacts_Addressbook:activeIds:, Created addressbook: '.$id,OCP\Util::DEBUG);
				self::setActive($id, true);
				$addressbooks = OC_Contacts_Addressbook::all($uid);
			}
			$prefbooks[] = $addressbooks[0]['id'];
			OCP\Config::setUserValue($uid,'contacts','openaddressbooks',implode(';',$prefbooks));
		}
		return $prefbooks;
	}

	/**
	 * @brief Returns the list of active addressbooks for a specific user.
	 * @param string $uid
	 * @return array
	 */
	public static function active($uid){
		$active = self::activeIds($uid);
		$addressbooks = array();
		if(!$active) {
			return $addressbooks;
		}
		$ids_sql = join(',', array_fill(0, count($active), '?'));
		$prep = 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE id IN ('.$ids_sql.') ORDER BY displayname';
		try {
			$stmt = OCP\DB::prepare( $prep );
			$result = $stmt->execute($active);
			while( $row = $result->fetchRow()){
				$addressbooks[] = $row;
			}
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts','OC_Contacts_Addressbook:active:, exception: '.$e->getMessage(),OCP\Util::DEBUG);
			OCP\Util::writeLog('contacts','OC_Contacts_Addressbook:active, ids: '.join(',', $active),OCP\Util::DEBUG);
			OCP\Util::writeLog('contacts','OC_Contacts_Addressbook::active, SQL:'.$prep,OCP\Util::DEBUG);
			return array();
		}

		return $addressbooks;
	}

	/**
	 * @brief Activates an addressbook
	 * @param integer $id
	 * @param integer $name
	 * @return boolean
	 */
	public static function setActive($id,$active=true){
		// Need these ones for checking uri
		//$addressbook = self::find($id);

		if(is_null($id)){
			$id = 0;
		}

		$openaddressbooks = self::activeIds();
		if($active) {
			if(!in_array($id, $openaddressbooks)) {
				$openaddressbooks[] = $id;
			}
		} else {
			if(in_array($id, $openaddressbooks)) {
				unset($openaddressbooks[array_search($id, $openaddressbooks)]);
			}
		}
		// NOTE: Ugly hack...
		$openaddressbooks = self::cleanArray($openaddressbooks, false);
		sort($openaddressbooks, SORT_NUMERIC);
		// FIXME: I alway end up with a ';' prepending when imploding the array..?
		OCP\Config::setUserValue(OCP\USER::getUser(),'contacts','openaddressbooks',implode(';', $openaddressbooks));

		return true;
	}

	/**
	 * @brief Checks if an addressbook is active.
	 * @param integer $id ID of the address book.
	 * @return boolean
	 */
	public static function isActive($id){
		//OCP\Util::writeLog('contacts','OC_Contacts_Addressbook::isActive('.$id.'):'.in_array($id, self::activeIds()), OCP\Util::DEBUG);
		return in_array($id, self::activeIds());
	}

	/**
	 * @brief removes an address book
	 * @param integer $id
	 * @return boolean
	 */
	public static function delete($id){
		// FIXME: There's no error checking at all.
		self::setActive($id, false);
		$stmt = OCP\DB::prepare( 'DELETE FROM *PREFIX*contacts_addressbooks WHERE id = ?' );
		$stmt->execute(array($id));

		$cards = OC_Contacts_VCard::all($id);
		foreach($cards as $card){
			OC_Contacts_VCard::delete($card['id']);
		}

		return true;
	}

	/**
	 * @brief Updates ctag for addressbook
	 * @param integer $id
	 * @return boolean
	 */
	public static function touch($id){
		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*contacts_addressbooks SET ctag = ctag + 1 WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief Creates a URI for Addressbook
	 * @param string $name name of the addressbook
	 * @param array  $existing existing addressbook URIs
	 * @return string new name
	 */
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

	/**
	 * @brief gets the userid from a principal path
	 * @return string
	 */
	public static function extractUserID($principaluri){
		list($prefix,$userid) = Sabre_DAV_URLUtil::splitPath($principaluri);
		return $userid;
	}
}
