<?php
/**
 * Copyright (c) 2011 Jakob Sack <mail@jakobsack.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class manages our calendar objects
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
	 * @brief Returns all objects of a calendar between $start and $end
	 * @param integer $id
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return array
	 *
	 * The objects are associative arrays. You'll find the original vObject
	 * in ['calendardata']
	 */
	public static function allInPeriod($id, $start, $end){
		$stmt = OC_DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ?'
		.' AND ((startdate >= ? AND startdate <= ? AND repeating = 0)'
		.' OR (startdate <= ? AND enddate >= ? AND repeating = 1))' );
		$start = self::getUTCforMDB($start);
		$end = self::getUTCforMDB($end);
		$result = $stmt->execute(array($id,
					$start, $end,
					$end, $start));

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

		OC_Calendar_Calendar::touchCalendar($oldobject['calendarid']);

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
		$oldobject = self::find($id);
		$stmt = OC_DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE id = ?' );
		$stmt->execute(array($id));
		OC_Calendar_Calendar::touchCalendar($oldobject['calendarid']);

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

	public static function moveToCalendar($id, $calendarid){
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_objects SET calendarid=? WHERE id = ?' );
		$result = $stmt->execute(array($calendarid,$id));

		OC_Calendar_Calendar::touchCalendar($id);

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

	public static function getDTEndFromVEvent($vevent)
	{
		if ($vevent->DTEND) {
			$dtend = $vevent->DTEND;
		}else{
			$dtend = clone $vevent->DTSTART;
			if ($vevent->DURATION){
				$duration = strval($vevent->DURATION);
				$invert = 0;
				if ($duration[0] == '-'){
					$duration = substr($duration, 1);
					$invert = 1;
				}
				if ($duration[0] == '+'){
					$duration = substr($duration, 1);
				}
				$interval = new DateInterval($duration);
				$interval->invert = $invert;
				$dtend->getDateTime()->add($interval);
			}
		}
		return $dtend;
	}

	public static function getCategoryOptions($l10n)
	{
		return array(
			$l10n->t('Birthday'),
			$l10n->t('Business'),
			$l10n->t('Call'),
			$l10n->t('Clients'),
			$l10n->t('Deliverer'),
			$l10n->t('Holidays'),
			$l10n->t('Ideas'),
			$l10n->t('Journey'),
			$l10n->t('Jubilee'),
			$l10n->t('Meeting'),
			$l10n->t('Other'),
			$l10n->t('Personal'),
			$l10n->t('Projects'),
			$l10n->t('Questions'),
			$l10n->t('Work'),
		);
	}

	public static function getRepeatOptions($l10n)
	{
		return array(
			'doesnotrepeat' => $l10n->t('Does not repeat'),
			'daily'         => $l10n->t('Daily'),
			'weekly'        => $l10n->t('Weekly'),
			'weekday'       => $l10n->t('Every Weekday'),
			'biweekly'      => $l10n->t('Bi-Weekly'),
			'monthly'       => $l10n->t('Monthly'),
			'yearly'        => $l10n->t('Yearly'),
		);
	}
	public static function validateRequest($request)
	{
		$errnum = 0;
		$errarr = array('title'=>'false', 'cal'=>'false', 'from'=>'false', 'fromtime'=>'false', 'to'=>'false', 'totime'=>'false', 'endbeforestart'=>'false');
		if($request['title'] == ''){
			$errarr['title'] = 'true';
			$errnum++;
		}
		$calendar = OC_Calendar_Calendar::findCalendar($request['calendar']);
		if($calendar['userid'] != OC_User::getUser()){
			$errarr['cal'] = 'true';
			$errnum++;
		}

		if(isset($request['categories']) && !is_array($request['categories'])){
			$errors['categories'] = $l10n->t('Not an array');
		}

		$fromday = substr($request['from'], 0, 2);
		$frommonth = substr($request['from'], 3, 2);
		$fromyear = substr($request['from'], 6, 4);
		if(!checkdate($frommonth, $fromday, $fromyear)){
			$errarr['from'] = 'true';
			$errnum++;
		}
		$allday = isset($request['allday']);
		if(!$allday && self::checkTime(urldecode($request['fromtime']))) {
			$errarr['fromtime'] = 'true';
			$errnum++;
		}

		$today = substr($request['to'], 0, 2);
		$tomonth = substr($request['to'], 3, 2);
		$toyear = substr($request['to'], 6, 4);
		if(!checkdate($tomonth, $today, $toyear)){
			$errarr['to'] = 'true';
			$errnum++;
		}
		;
		if(!$allday && self::checkTime(urldecode($request['totime']))) {
			$errarr['totime'] = 'true';
			$errnum++;
		}
		if($today < $fromday && $frommonth == $tomonth && $fromyear == $toyear){
			$errarr['endbeforestart'] = 'true';
			$errnum++;
		}
		if($today == $fromday && $frommonth > $tomonth && $fromyear == $toyear){
			$errarr['endbeforestart'] = 'true';
			$errnum++;
		}
		if($today == $fromday && $frommonth == $tomonth && $fromyear > $toyear){
			$errarr['endbeforestart'] = 'true';
			$errnum++;
		}
		if(!$allday && $fromday == $today && $frommonth == $tomonth && $fromyear == $toyear){
			list($tohours, $tominutes) = explode(':', $request['totime']);
			list($fromhours, $fromminutes) = explode(':', $request['fromtime']);
			if($tohours < $fromhours){
				$errarr['endbeforestart'] = 'true';
				$errnum++;
			}
			if($tohours == $fromhours && $tominutes < $fromminutes){
				$errarr['endbeforestart'] = 'true';
				$errnum++;
			}
		}
		if ($errnum)
		{
			return $errarr;
		}
		return false;
	}

	protected static function checkTime($time)
	{
		list($hours, $minutes) = explode(':', $time);
		return empty($time)
			|| $hours < 0 || $hours > 24
			|| $minutes < 0 || $minutes > 60;
	}

	public static function createVCalendarFromRequest($request)
	{
		$vcalendar = new Sabre_VObject_Component('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$now = new DateTime();

		$vevent = new Sabre_VObject_Component('VEVENT');
		$vcalendar->add($vevent);

		$created = new Sabre_VObject_Element_DateTime('CREATED');
		$created->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
		$vevent->add($created);

		$uid = self::createUID();
		$vevent->add('UID',$uid);

		return self::updateVCalendarFromRequest($request, $vcalendar);
	}

	public static function updateVCalendarFromRequest($request, $vcalendar)
	{
		$title = $request["title"];
		$location = $request["location"];
		$categories = isset($request["categories"]) ? $request["categories"] : null;
		$allday = isset($request["allday"]);
		$from = $request["from"];
		$to  = $request["to"];
		if (!$allday){
			$fromtime = $request['fromtime'];
			$totime = $request['totime'];
		}
		$description = $request["description"];
		//$repeat = $request["repeat"];
		/*switch($request["repeatfreq"]){
			case "DAILY":
				$repeatfreq = "DAILY";
			case "WEEKLY":
				$repeatfreq = "WEEKLY";
			case "WEEKDAY":
				$repeatfreq = "DAILY;BYDAY=MO,TU,WE,TH,FR"; //load weeksdayss from userconfig when weekdays are choosable
			case "":
				$repeatfreq = "";
			case "":
				$repeatfreq = "";
			case "":
				$repeatfreq = "";
			default:
				$repeat = "false";
		}*/
		$repeat = "false";

		$now = new DateTime();
		$vevent = $vcalendar->VEVENT[0];

		$last_modified = new Sabre_VObject_Element_DateTime('LAST-MODIFIED');
		$last_modified->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
		$vevent->__set('LAST-MODIFIED', $last_modified);

		$dtstamp = new Sabre_VObject_Element_DateTime('DTSTAMP');
		$dtstamp->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
		$vevent->DTSTAMP = $dtstamp;

		$vevent->SUMMARY = $title;

		$dtstart = new Sabre_VObject_Element_DateTime('DTSTART');
		$dtend = new Sabre_VObject_Element_DateTime('DTEND');
		if($allday){
			$start = new DateTime($from);
			$end = new DateTime($to.' +1 day');
			$dtstart->setDateTime($start, Sabre_VObject_Element_DateTime::DATE);
			$dtend->setDateTime($end, Sabre_VObject_Element_DateTime::DATE);
		}else{
			$timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
			$timezone = new DateTimeZone($timezone);
			$start = new DateTime($from.' '.$fromtime, $timezone);
			$end = new DateTime($to.' '.$totime, $timezone);
			$dtstart->setDateTime($start, Sabre_VObject_Element_DateTime::LOCALTZ);
			$dtend->setDateTime($end, Sabre_VObject_Element_DateTime::LOCALTZ);
		}
		$vevent->DTSTART = $dtstart;
		$vevent->DTEND = $dtend;
		unset($vevent->DURATION);

		if($location != ""){
			$vevent->LOCATION = $location;
		}else{
			unset($vevent->LOCATION);
		}

		if($description != ""){
			$vevent->DESCRIPTION = $description;
		}else{
			unset($vevent->DESCRIPTION);
		}

		if(!empty($categories)){
			$vevent->CATEGORIES = join(',', $categories);
		}else{
			unset($vevent->CATEGORIES);
		}

		/*if($repeat == "true"){
			$vevent->RRULE = $repeat;
		}*/

		return $vcalendar;
	}
}
