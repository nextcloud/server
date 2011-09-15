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

/**
 * This class manages our calendars
 */
class OC_Calendar_Object{
	/**
	 * @brief Returns all objects of a calendar
	 * @param integer $id
	 * @return array
	 *
	 * The objects are associative arrays. You'll find the original vObject in
	 * ['calendardata']
	 */
	public static function all($id){
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
	public static function find($id){
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
	public static function findWhereDAVDataIs($cid,$uri){
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
	public static function add($id,$data){
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

		OC_Calendar_Calendar::touchCalendar($id);

		return OC_DB::insertid();
	}

	/**
	 * @brief Adds an object with the data provided by sabredav
	 * @param integer $id Calendar id
	 * @param string $uri   the uri the card will have
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addFromDAVData($id,$uri,$data){
		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'INSERT INTO *PREFIX*calendar_objects (calendarid,objecttype,startdate,enddate,repeating,summary,calendardata,uri,lastmodified) VALUES(?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time()));

		OC_Calendar_Calendar::touchCalendar($id);

		return OC_DB::insertid();
	}

	/**
	 * @brief edits an object
	 * @param integer $id id of object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function edit($id, $data){
		$oldobject = self::find($id);

		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_objects SET objecttype=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$id));

		OC_Calendar_Calendar::touchCalendar($id);

		return true;
	}

	/**
	 * @brief edits an object with the data provided by sabredav
	 * @param integer $id calendar id
	 * @param string $uri   the uri of the object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function editFromDAVData($cid,$uri,$data){
		$oldobject = self::findWhereDAVDataIs($cid,$uri);

		$object = self::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_objects SET objecttype=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$result = $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$oldobject['id']));

		OC_Calendar_Calendar::touchCalendar($oldobject['calendarid']);

		return true;
	}

	/**
	 * @brief deletes an object
	 * @param integer $id id of object
	 * @return boolean
	 */
	public static function delete($id){
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
	public static function deleteFromDAVData($cid,$uri){
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri=?' );
		$stmt->execute(array($cid,$uri));

		return true;
	}

	/**
	 * @brief Creates a UID
	 * @return string
	 */
	protected static function createUID(){
		return substr(md5(rand().time()),0,10);
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
				$return[0] = $property->name;
				foreach($property->children as &$element){
					if($element->name == 'SUMMARY'){
						$return[3] = $element->value;
					}
					elseif($element->name == 'UID'){
						$return[5] = $element->value;
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
	 */
	public static function parse($data){
		try {
			$calendar = Sabre_VObject_Reader::read($data);
			return $calendar;
		} catch (Exception $e) {
			return null;
		}
	}
}
