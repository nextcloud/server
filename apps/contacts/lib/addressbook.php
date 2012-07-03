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
	 * @return array or false.
	 */
	public static function all($uid){
		try {
			$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE userid = ? ORDER BY displayname' );
			$result = $stmt->execute(array($uid));
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(),OCP\Util::ERROR);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.' uid: '.$uid,OCP\Util::DEBUG);
			return false;
		}


		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}
		$addressbooks = array_merge($addressbooks, OCP\Share::getItemsSharedWith('addressbook', OC_Contacts_Share::FORMAT_ADDRESSBOOKS));
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
	 * @return associative array or false.
	 */
	public static function find($id){
		try {
			$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE id = ?' );
			$result = $stmt->execute(array($id));
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', exception: '.$e->getMessage(),OCP\Util::ERROR);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', id: '.$id,OCP\Util::DEBUG);
			return false;
		}

		return $result->fetchRow();
	}

	/**
	 * @brief Adds default address book
	 * @return $id ID of the newly created addressbook or false on error.
	 */
	public static function addDefault($uid = null){
		if(is_null($uid)) {
			$uid = OCP\USER::getUser();
		}
		$id = self::add($uid,'default','Default Address Book');
		if($id !== false) {
			self::setActive($id, true);
		}
		return $id;
	}
	
	/**
	 * @brief Creates a new address book
	 * @param string $userid
	 * @param string $name
	 * @param string $description
	 * @return insertid
	 */
	public static function add($uid,$name,$description=''){
		$all = self::all($uid);
		$uris = array();
		foreach($all as $i){
			$uris[] = $i['uri'];
		}

		$uri = self::createURI($name, $uris );
		try {
			$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*contacts_addressbooks (userid,displayname,uri,description,ctag) VALUES(?,?,?,?,?)' );
			$result = $stmt->execute(array($uid,$name,$uri,$description,1));
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', exception: '.$e->getMessage(),OCP\Util::ERROR);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', uid: '.$uid,OCP\Util::DEBUG);
			return false;
		}

		return OCP\DB::insertid('*PREFIX*contacts_addressbooks');
	}

	/**
	 * @brief Creates a new address book from the data sabredav provides
	 * @param string $principaluri
	 * @param string $uri
	 * @param string $name
	 * @param string $description
	 * @return insertid or false
	 */
	public static function addFromDAVData($principaluri,$uri,$name,$description){
		$uid = self::extractUserID($principaluri);

		try {
			$stmt = OCP\DB::prepare('INSERT INTO *PREFIX*contacts_addressbooks (userid,displayname,uri,description,ctag) VALUES(?,?,?,?,?)');
			$result = $stmt->execute(array($uid,$name,$uri,$description,1));
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', exception: '.$e->getMessage(),OCP\Util::ERROR);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', uid: '.$uid,OCP\Util::DEBUG);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', uri: '.$uri,OCP\Util::DEBUG);
			return false;
		}

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

		try {
			$stmt = OCP\DB::prepare('UPDATE *PREFIX*contacts_addressbooks SET displayname=?,description=?, ctag=ctag+1 WHERE id=?');
			$result = $stmt->execute(array($name,$description,$id));
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', exception: '.$e->getMessage(),OCP\Util::ERROR);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', id: '.$id,OCP\Util::DEBUG);
			return false;
		}

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
		if(!$prefbooks){
			$addressbooks = OC_Contacts_Addressbook::all($uid);
			if(count($addressbooks) == 0){
				self::addDefault($uid);
			}
		}
		$prefbooks = OCP\Config::getUserValue($uid,'contacts','openaddressbooks',null);
		return explode(';',$prefbooks);
	}

	/**
	 * @brief Returns the list of active addressbooks for a specific user.
	 * @param string $uid
	 * @return array
	 */
	public static function active($uid){
		if(is_null($uid)){
			$uid = OCP\USER::getUser();
		}
		$active = self::activeIds($uid);
		$shared = OCP\Share::getItemsSharedWith('addressbook', OC_Contacts_Share::FORMAT_ADDRESSBOOKS);
		$addressbooks = array();
		$ids_sql = join(',', array_fill(0, count($active), '?'));
		$prep = 'SELECT * FROM *PREFIX*contacts_addressbooks WHERE id IN ('.$ids_sql.') ORDER BY displayname';
		try {
			$stmt = OCP\DB::prepare( $prep );
			$result = $stmt->execute($active);
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', exception: '.$e->getMessage(),OCP\Util::ERROR);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', uid: '.$uid,OCP\Util::DEBUG);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', ids: '.join(',', $active),OCP\Util::DEBUG);
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', SQL:'.$prep,OCP\Util::DEBUG);
		}

		while( $row = $result->fetchRow()){
			// Insert formatted shared addressbook instead
			if ($row['userid'] != $uid) {
				foreach ($shared as $addressbook) {
					if ($addressbook['id'] == $row['id']) {
						$addressbooks[] = $addressbook;
						break;
					}
				}
			} else {
				$addressbooks[] = $row;
			}
		}
		if(!count($addressbooks)) {
			self::addDefault($uid);
		}
		return $addressbooks;
	}

	/**
	 * @brief Activates an addressbook
	 * @param integer $id
	 * @param boolean $active
	 * @return boolean
	 */
	public static function setActive($id,$active){
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
		self::setActive($id, false);
		try {
			$stmt = OCP\DB::prepare( 'DELETE FROM *PREFIX*contacts_addressbooks WHERE id = ?' );
			$stmt->execute(array($id));
		} catch(Exception $e) {
			OCP\Util::writeLog('contacts',__CLASS__.'::'.__METHOD__.', exception for '.$id.': '.$e->getMessage(),OCP\Util::ERROR);
			return false;
		}
		
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
