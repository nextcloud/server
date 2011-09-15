<?php
/**
 * ownCloud - Calendar
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
 *     active INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     ctag INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     description TEXT,
 *     calendarorder INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     calendarcolor VARCHAR(10),
 *     timezone TEXT,
 *     components VARCHAR(20)
 * );
 */

/**
 * This class manages our calendars
 */
class OC_Calendar_Calendar{
	/**
	 * @brief Returns the list of calendars for a specific user.
	 * @param string $uid User ID
	 * @param boolean $active
	 * @return array
	 *
	 * TODO: what is active for?
	 */
	public static function allCalendars($uid, $active=null){
		$values = array($uid);
		$active_where = '';
		if (!is_null($active) && $active){
			$active_where = ' AND active = ?';
			$values[] = $active;
		}
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_calendars WHERE userid = ?' . $active_where );
		$result = $stmt->execute($values);

		$calendars = array();
		while( $row = $result->fetchRow()){
			$calendars[] = $row;
		}

		return $calendars;
	}

	/**
	 * @brief Returns the list of calendars for a principal (DAV term of user)
	 * @param string $principaluri
	 * @return array
	 */
	public static function allCalendarsWherePrincipalURIIs($principaluri){
		$uid = self::extractUserID($principaluri);
		return self::allCalendars($uid);
	}

	/**
	 * @brief Gets the data of one calendar
	 * @param integer $id
	 * @return associative array
	 */
	public static function findCalendar($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_calendars WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	/**
	 * @brief Creates a new calendar
	 * @param string $userid
	 * @param string $name
	 * @param string $description
	 * @param string $components Default: "VEVENT,VTODO,VJOURNAL"
	 * @param string $timezone Default: null
	 * @param integer $order Default: 1
	 * @param string $color Default: null
	 * @return insertid
	 */
	public static function addCalendar($userid,$name,$description,$components='VEVENT,VTODO,VJOURNAL',$timezone=null,$order=0,$color=null){
		$all = self::allCalendars($userid);
		$uris = array();
		foreach($all as $i){
			$uris[] = $i['uri'];
		}

		$uri = self::createURI($name, $uris );

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*calendar_calendars (userid,displayname,uri,ctag,description,calendarorder,calendarcolor,timezone,components) VALUES(?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,1,$description,$order,$color,$timezone,$components));

		return OC_DB::insertid();
	}

	/**
	 * @brief Creates a new calendar from the data sabredav provides
	 * @param string $principaluri
	 * @param string $uri
	 * @param string $name
	 * @param string $description
	 * @param string $components
	 * @param string $timezone
	 * @param integer $order
	 * @param string $color
	 * @return insertid
	 */
	public static function addCalendarFromDAVData($principaluri,$uri,$name,$description,$components,$timezone,$order,$color){
		$userid = self::extractUserID($principaluri);

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*calendar_calendars (userid,displayname,uri,ctag,description,calendarorder,calendarcolor,timezone,components) VALUES(?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,1,$description,$order,$color,$timezone,$components));

		return OC_DB::insertid();
	}

	/**
	 * @brief Edits a calendar
	 * @param integer $id
	 * @param string $name Default: null
	 * @param string $description Default: null
	 * @param string $components Default: null
	 * @param string $timezone Default: null
	 * @param integer $order Default: null
	 * @param string $color Default: null
	 * @return boolean
	 *
	 * Values not null will be set
	 */
	public static function editCalendar($id,$name=null,$description=null,$components=null,$timezone=null,$order=null,$color=null){
		// Need these ones for checking uri
		$calendar = self::findCalendar($id);

		// Keep old stuff
		if(is_null($name)) $name = $calendar['name'];
		if(is_null($description)) $description = $calendar['description'];
		if(is_null($components)) $components = $calendar['components'];
		if(is_null($timezone)) $timezone = $calendar['timezone'];
		if(is_null($order)) $order = $calendar['calendarorder'];
		if(is_null($color)) $color = $calendar['color'];

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET displayname=?,description=?,calendarorder=?,calendarcolor=?,timezone=?,components=?,ctag=ctag+1 WHERE id=?' );
		$result = $stmt->execute(array($name,$description,$order,$color,$timezone,$components,$id));

		return true;
	}

	/**
	 * @brief Sets a calendar (in)active
	 * @param integer $id
	 * @param boolean $active
	 * @return boolean
	 */
	public static function setCalendarActive($id,$active){
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET active = ? WHERE id = ?' );
		$stmt->execute(array($active, $id));

		return true;
	}

	/**
	 * @brief Updates ctag for calendar
	 * @param integer $id
	 * @return boolean
	 */
	public static function touchCalendar($id){
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET ctag = ctag + 1 WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief removes a calendar
	 * @param integer $id
	 * @return boolean
	 */
	public static function deleteCalendar($id){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_calendars WHERE id = ?' );
		$stmt->execute(array($id));

		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE calendarid = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief Returns all objects of a calendar
	 * @param integer $id
	 * @return array
	 *
	 * The objects are associative arrays. You'll find the original vObject in
	 * ['carddata']
	 */
	public static function allCalendarObjects($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ?' );
		$result = $stmt->execute(array($id));

		$calendarobjects = array();
		while( $row = $result->fetchRow()){
			$calendarobjects[] = $row;
		}

		return $calendarobjects;
	}

	/**
	 * @brief Returns an object
	 * @param integer $id
	 * @return associative array
	 */
	public static function findCalendarObject($id){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	/**
	 * @brief finds an object by its DAV Data
	 * @param integer $cid Calendar id
	 * @param string $uri the uri ('filename')
	 * @return associative array
	 */
	public static function findCalendarObjectWhereDAVDataIs($cid,$uri){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri = ?' );
		$result = $stmt->execute(array($cid,$uri));

		return $result->fetchRow();
	}

	/**
	 * @brief Adds an object
	 * @param integer $id Calendar id
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addCalendarObject($id,$data){
		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		if(is_null($uid)){
			$uid = self::createUID();
			$object->add('UID',$uid);
			$data = $object->serialize();
		}

		$uri = 'owncloud-'.md5($data.rand().time()).'.ics';

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*calendar_objects (calendarid,objecttype,startdate,enddate,repeating,summary,calendardata,uri,lastmodified) VALUES(?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time()));

		self::touchCalendar($id);

		return OC_DB::insertid();
	}

	/**
	 * @brief Adds an object with the data provided by sabredav
	 * @param integer $id Calendar id
	 * @param string $uri   the uri the card will have
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addCalendarObjectFromDAVData($id,$uri,$data){
		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*calendar_objects (calendarid,objecttype,startdate,enddate,repeating,summary,calendardata,uri,lastmodified) VALUES(?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time()));

		self::touchCalendar($id);

		return OC_DB::insertid();
	}

	/**
	 * @brief edits an object
	 * @param integer $id id of object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function editCalendarObject($id, $data){
		$oldobject = self::findCalendarObject($id);

		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_objects SET objecttype=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$id));

		self::touchCalendar($id);

		return true;
	}

	/**
	 * @brief edits an object with the data provided by sabredav
	 * @param integer $id calendar id
	 * @param string $uri   the uri of the object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function editCalendarObjectFromDAVData($cid,$uri,$data){
		$oldobject = self::findCalendarObjectWhereDAVDataIs($cid,$uri);

		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_objects SET objecttype=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$oldobject['id']));

		self::touchCalendar($oldobject['calendarid']);

		return true;
	}

	/**
	 * @brief deletes an object
	 * @param integer $id id of object
	 * @return boolean
	 */
	public static function deleteCalendarObject($id){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief deletes an  object with the data provided by sabredav
	 * @param integer $cid calendar id
	 * @param string $uri the uri of the object
	 * @return boolean
	 */
	public static function deleteCalendarObjectFromDAVData($cid,$uri){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri=?' );
		$stmt->execute(array($cid,$uri));

		return true;
	}

	/**
	 * @brief Creates a URI for Calendar
	 * @param string $name name of the calendar
	 * @param array  $existing existing calendar URIs
	 * @return string uri
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
	 * @brief Creates a UID
	 * @return string
	 */
	public static function createUID(){
		return substr(md5(rand().time()),0,10);
	}

	/**
	 * @brief gets the userid from a principal path
	 * @return string
	 */
	public static function extractUserID($principaluri){
		list($prefix,$userid) = Sabre_DAV_URLUtil::splitPath($principaluri);
		return $userid;
	}

	/**
	 * @brief Extracts data from a vObject-Object
	 * @param Sabre_VObject $object
	 * @return array
	 *
	 * [type, start, end, summary, repeating, uid]
	 */
	protected static function extractData($object){
		$return = array('',null,null,'',0,null);

		// Child to use
		$children = 0;
		$use = null;
		foreach($object->children as &$property){
			if($property->name == 'VEVENT'){
				$children++;
				$thisone = true;

				foreach($property->children as &$element){
					if($element->name == 'RECURRENCE-ID'){
						$thisone = false;
					}
				} unset($element);

				if($thisone){
					$use = $property;
				}
			}
			elseif($property->name == 'VTODO' || $property->name == 'VJOURNAL'){
				$return[0] = $use->name;
				foreach($property->children as &$element){
					if($property->name == 'SUMMARY'){
						$return[3] = $property->value;
					}
					elseif($property->name == 'UID'){
						$return[5] = $property->value;
					}
				};

				// Only one VTODO or VJOURNAL per object
				// (only one UID per object but a UID is required by a VTODO =>
				//    one VTODO per object)
				break;
			}
		} unset($property);

		// find the data
		if(!is_null($use)){
			$return[0] = $use->name;
			foreach($use->children as &$property){
				if($property->name == 'DTSTART'){
					$return[1] = self::getUTCforMDB($property->getDateTime());
				}
				elseif($property->name == 'DTEND'){
					$return[2] = self::getUTCforMDB($property->getDateTime());
				}
				elseif($property->name == 'SUMMARY'){
					$return[3] = $property->value;
				}
				elseif($property->name == 'RRULE'){
					$return[4] = 1;
				}
				elseif($property->name == 'UID'){
					$return[5] = $property->value;
				}
			} unset($property);
		}

		// More than one child means reoccuring!
		if($children > 1){
			$return[4] = 1;
		}
		return $return;
	}

	/**
	 * @brief DateTime to UTC string
	 * @param DateTime $datetime The date to convert
	 * @returns date as YYYY-MM-DD hh:mm
	 *
	 * This function creates a date string that can be used by MDB2.
	 * Furthermore it converts the time to UTC.
	 */
	protected static function getUTCforMDB($datetime){
		return date('Y-m-d H:i', $datetime->format('U') - $datetime->getOffset());
	}

	/**
	 * @brief Parses the VObject
	 * @param string VObject as string
	 * @returns Sabre_VObject or null
	 *
	 * This function creates a date string that can be used by MDB2.
	 * Furthermore it converts the time to UTC.
	 */
	public static function parse($data){
		try {
			$card = Sabre_VObject_Reader::read($data);
			return $card;
		} catch (Exception $e) {
			return null;
		}
	}
}
