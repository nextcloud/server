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
 * CREATE TABLE calendar_objects (
 *     id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *     calendarid INTEGER UNSIGNED NOT NULL,
 *     objecttype VARCHAR(40) NOT NULL,
 *     startdate DATETIME,
 *     enddate DATETIME,
 *     repeating INT(1),
 *     summary VARCHAR(255),
 *     calendardata TEXT,
 *     uri VARCHAR(100),
 *     lastmodified INT(11)
 * );
 * 
 * CREATE TABLE calendar_calendars (
 *     id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *     userid VARCHAR(255),
 *     displayname VARCHAR(100),
 *     uri VARCHAR(100),
 *     ctag INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     description TEXT,
 *     calendarorder INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     calendarcolor VARCHAR(10),
 *     timezone TEXT,
 *     components VARCHAR(20)
 * );
 */

/**
 * This class manages our addressbooks.
 */
class OC_Calendar_Calendar{
	public static function allCalendars($uid){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_calendars WHERE userid = ?' );
		$result = $stmt->execute(array($uid));
		
		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}

		return $addressbooks;
	}
	
	public static function allCakendarsWherePrincipalURIIs($principaluri){
		$uid = self::extractUserID($principaluri);
		return self::allCalendars($uid);
	}

	public static function findCalendar($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_calendars WHERE id = ?' );
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

	public static function touchCalendar($id){
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET ctag = ctag + 1 WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	public static function deleteCalendar($id){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_calendars WHERE id = ?' );
		$stmt->execute(array($id));
		
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE addressbookid = ?' );
		$stmt->execute(array($id));

		return true;
	}

	public static function allCalendarObjects($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ?' );
		$result = $stmt->execute(array($id));

		$addressbooks = array();
		while( $row = $result->fetchRow()){
			$addressbooks[] = $row;
		}

		return $addressbooks;
	}
	
	public static function findCalendarObject($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	public static function findCalendarObjectWhereDAVDataIs($cid,$uri){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri = ?' );
		$result = $stmt->execute(array($cid,$uri));

		return $result->fetchRow();
	}

	public static function addCard($id,$data){
		$object = Sabre_VObject_Reader::read($data);
		list($startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

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

	public static function addCalendarObjectFromDAVData($id,$uri,$data){
		$object = Sabre_VObject_Reader::read($data);
		list($startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*calendar_objects (calendarid,objecttye,startdate,enddate,repeating,summary,calendardata,uri,lastmodified) VALUES(?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$object->name,(is_null($startdate)?null:$startdate->format('Y-m-d H:i:s')),(is_null($enddate)?null:$enddate->format('Y-m-d H:i:s')),$repeating,$summary,$data,$uri,time()));

		self::touchCalendar($id);

		return OC_DB::insertid();
	}

	public static function editCalendarObject($id, $data){
		$oldobject = self::findCard($id);
		
		$object = Sabre_VObject_Reader::read($data);
		list($startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET objecttye=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($object->name,(is_null($startdate)?null:$startdate->format('Y-m-d H:i:s')),(is_null($enddate)?null:$enddate->format('Y-m-d H:i:s')),$repeating,$summary,$data,time(),$id));

		self::touchCalendar($id);

		return true;
	}

	public static function editCalendarObjectFromDAVData($cid,$uri,$data){
		$oldobject = self::findCardWhereDAVDataIs($cid,$uri);
		
		$object = Sabre_VObject_Reader::read($data);
		list($startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*contacts_cards SET objecttye=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($object->name,(is_null($startdate)?null:$startdate->format('Y-m-d H:i:s')),(is_null($enddate)?null:$enddate->format('Y-m-d H:i:s')),$repeating,$summary,$data,time(),$oldobject['id']));

		self::touchCalendar($oldobject['calendarid']);

		return true;
	}
	
	public static function deleteCalendarObject($id){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	public static function deleteCalendarObjectFromDAVData($cid,$uri){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri=?' );
		$stmt->execute(array($cid,$uri));

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

	protected static function extractData($object){
		$return = array(null,null,'',false,null);
		foreach($object->children as $property){
			if($property->name == 'DTSTART'){
				$return[0] = $property->getDateTime();
			}
			elseif($property->name == 'DTEND'){
				$return[1] = $property->getDateTime();
			}
			elseif($property->name == 'SUMMARY'){
				$return[2] = $property->value;
			}
			elseif($property->name == 'RRULE'){
				$return[3] = true;
			}
			elseif($property->name == 'UID'){
				$return[4] = $property->value;
			}
		}
		return $return;
	}
}
