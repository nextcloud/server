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
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ?' );
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
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ?'
		.' AND ((startdate >= ? AND startdate <= ? AND repeating = 0)'
		.' OR (enddate >= ? AND enddate <= ? AND repeating = 0)'
		.' OR (startdate <= ? AND repeating = 1))' );
		$start = self::getUTCforMDB($start);
		$end = self::getUTCforMDB($end);
		$result = $stmt->execute(array($id,
					$start, $end,
					$start, $end,
					$end));

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
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE id = ?' );
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
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri = ?' );
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
		$object = OC_VObject::parse($data);
		OC_Calendar_App::loadCategoriesFromVCalendar($object);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		if(is_null($uid)){
			$object->setUID();
			$data = $object->serialize();
		}

		$uri = 'owncloud-'.md5($data.rand().time()).'.ics';

		$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*calendar_objects (calendarid,objecttype,startdate,enddate,repeating,summary,calendardata,uri,lastmodified) VALUES(?,?,?,?,?,?,?,?,?)' );
		$stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time()));
		$object_id = OCP\DB::insertid('*PREFIX*calendar_objects');

		OC_Calendar_Calendar::touchCalendar($id);

		return $object_id;
	}

	/**
	 * @brief Adds an object with the data provided by sabredav
	 * @param integer $id Calendar id
	 * @param string $uri   the uri the card will have
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addFromDAVData($id,$uri,$data){
		$object = OC_VObject::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*calendar_objects (calendarid,objecttype,startdate,enddate,repeating,summary,calendardata,uri,lastmodified) VALUES(?,?,?,?,?,?,?,?,?)' );
		$stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time()));
		$object_id = OCP\DB::insertid('*PREFIX*calendar_objects');

		OC_Calendar_Calendar::touchCalendar($id);

		return $object_id;
	}

	/**
	 * @brief edits an object
	 * @param integer $id id of object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function edit($id, $data){
		$oldobject = self::find($id);

		$object = OC_VObject::parse($data);
		OC_Calendar_App::loadCategoriesFromVCalendar($object);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_objects SET objecttype=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$id));

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

		$object = OC_VObject::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid) = self::extractData($object);

		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_objects SET objecttype=?,startdate=?,enddate=?,repeating=?,summary=?,calendardata=?, lastmodified = ? WHERE id = ?' );
		$stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$oldobject['id']));

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
		$stmt = OCP\DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE id = ?' );
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
		$stmt = OCP\DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE calendarid = ? AND uri=?' );
		$stmt->execute(array($cid,$uri));
		OC_Calendar_Calendar::touchCalendar($cid);

		return true;
	}

	public static function moveToCalendar($id, $calendarid){
		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_objects SET calendarid=? WHERE id = ?' );
		$stmt->execute(array($calendarid,$id));

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
		foreach($object->children as $property){
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
		}

		// find the data
		if(!is_null($use)){
			$return[0] = $use->name;
			foreach($use->children as $property){
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
			}
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

	public static function getDTEndFromVEvent($vevent)
	{
		if ($vevent->DTEND) {
			$dtend = $vevent->DTEND;
		}else{
			$dtend = clone $vevent->DTSTART;
			// clone creates a shallow copy, also clone DateTime
			$dtend->setDateTime(clone $dtend->getDateTime(), $dtend->getDateType());
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

	public static function getRepeatOptions($l10n)
	{
		return array(
			'doesnotrepeat' => $l10n->t('Does not repeat'),
			'daily'         => $l10n->t('Daily'),
			'weekly'        => $l10n->t('Weekly'),
			'weekday'       => $l10n->t('Every Weekday'),
			'biweekly'      => $l10n->t('Bi-Weekly'),
			'monthly'       => $l10n->t('Monthly'),
			'yearly'        => $l10n->t('Yearly')
		);
	}

	public static function getEndOptions($l10n)
	{
		return array(
			'never' => $l10n->t('never'),
			'count' => $l10n->t('by occurrences'),
			'date'  => $l10n->t('by date')
		);
	}

	public static function getMonthOptions($l10n)
	{
		return array(
			'monthday' => $l10n->t('by monthday'),
			'weekday'  => $l10n->t('by weekday')
		);
	}

	public static function getWeeklyOptions($l10n)
	{
		return array(
			'MO' => $l10n->t('Monday'),
			'TU' => $l10n->t('Tuesday'),
			'WE' => $l10n->t('Wednesday'),
			'TH' => $l10n->t('Thursday'),
			'FR' => $l10n->t('Friday'),
			'SA' => $l10n->t('Saturday'),
			'SU' => $l10n->t('Sunday')
		);
	}

	public static function getWeekofMonth($l10n)
	{
		return array(
			'auto' => $l10n->t('events week of month'),
			'1' => $l10n->t('first'),
			'2' => $l10n->t('second'),
			'3' => $l10n->t('third'),
			'4' => $l10n->t('fourth'),
			'5' => $l10n->t('fifth'),
			'-1' => $l10n->t('last')
		);
	}

	public static function getByYearDayOptions(){
		$return = array();
		foreach(range(1,366) as $num){
			$return[(string) $num] = (string) $num;
		}
		return $return;
	}

	public static function getByMonthDayOptions(){
		$return = array();
		foreach(range(1,31) as $num){
			$return[(string) $num] = (string) $num;
		}
		return $return;
	}

	public static function getByMonthOptions($l10n){
		return array(
			'1'  => $l10n->t('January'),
			'2'  => $l10n->t('February'),
			'3'  => $l10n->t('March'),
			'4'  => $l10n->t('April'),
			'5'  => $l10n->t('May'),
			'6'  => $l10n->t('June'),
			'7'  => $l10n->t('July'),
			'8'  => $l10n->t('August'),
			'9'  => $l10n->t('September'),
			'10' => $l10n->t('October'),
			'11' => $l10n->t('November'),
			'12' => $l10n->t('December')
		);
	}

	public static function getYearOptions($l10n){
		return array(
			'bydate' => $l10n->t('by events date'),
			'byyearday' => $l10n->t('by yearday(s)'),
			'byweekno'  => $l10n->t('by weeknumber(s)'),
			'bydaymonth'  => $l10n->t('by day and month')
		);
	}

	public static function getByWeekNoOptions(){
		return range(1, 52);
	}

	public static function validateRequest($request)
	{
		$errnum = 0;
		$errarr = array('title'=>'false', 'cal'=>'false', 'from'=>'false', 'fromtime'=>'false', 'to'=>'false', 'totime'=>'false', 'endbeforestart'=>'false');
		if($request['title'] == ''){
			$errarr['title'] = 'true';
			$errnum++;
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
		if($request['repeat'] != 'doesnotrepeat'){
			if(is_nan($request['interval']) && $request['interval'] != ''){
				$errarr['interval'] = 'true';
				$errnum++;
			}
			if(array_key_exists('repeat', $request) && !array_key_exists($request['repeat'], self::getRepeatOptions(OC_Calendar_App::$l10n))){
				$errarr['repeat'] = 'true';
				$errnum++;
			}
			if(array_key_exists('advanced_month_select', $request) && !array_key_exists($request['advanced_month_select'], self::getMonthOptions(OC_Calendar_App::$l10n))){
				$errarr['advanced_month_select'] = 'true';
				$errnum++;
			}
			if(array_key_exists('advanced_year_select', $request) && !array_key_exists($request['advanced_year_select'], self::getYearOptions(OC_Calendar_App::$l10n))){
				$errarr['advanced_year_select'] = 'true';
				$errnum++;
			}
			if(array_key_exists('weekofmonthoptions', $request) && !array_key_exists($request['weekofmonthoptions'], self::getWeekofMonth(OC_Calendar_App::$l10n))){
				$errarr['weekofmonthoptions'] = 'true';
				$errnum++;
			}
			if($request['end'] != 'never'){
				if(!array_key_exists($request['end'], self::getEndOptions(OC_Calendar_App::$l10n))){
					$errarr['end'] = 'true';
					$errnum++;
				}
				if($request['end'] == 'count' && is_nan($request['byoccurrences'])){
					$errarr['byoccurrences'] = 'true';
					$errnum++;
				}
				if($request['end'] == 'date'){
					list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
					if(!checkdate($bydate_month, $bydate_day, $bydate_year)){
						$errarr['bydate'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('weeklyoptions', $request)){
				foreach($request['weeklyoptions'] as $option){
					if(!in_array($option, self::getWeeklyOptions(OC_Calendar_App::$l10n))){
						$errarr['weeklyoptions'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('byyearday', $request)){
				foreach($request['byyearday'] as $option){
					if(!array_key_exists($option, self::getByYearDayOptions())){
						$errarr['byyearday'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('weekofmonthoptions', $request)){
				if(is_nan((double)$request['weekofmonthoptions'])){
					$errarr['weekofmonthoptions'] = 'true';
					$errnum++;
				}
			}
			if(array_key_exists('bymonth', $request)){
				foreach($request['bymonth'] as $option){
					if(!in_array($option, self::getByMonthOptions(OC_Calendar_App::$l10n))){
						$errarr['bymonth'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('byweekno', $request)){
				foreach($request['byweekno'] as $option){
					if(!array_key_exists($option, self::getByWeekNoOptions())){
						$errarr['byweekno'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('bymonthday', $request)){
				foreach($request['bymonthday'] as $option){
					if(!array_key_exists($option, self::getByMonthDayOptions())){
						$errarr['bymonthday'] = 'true';
						$errnum++;
					}
				}
			}
		}
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
		$vcalendar = new OC_VObject('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$vevent = new OC_VObject('VEVENT');
		$vcalendar->add($vevent);

		$vevent->setDateTime('CREATED', 'now', Sabre_VObject_Property_DateTime::UTC);

		$vevent->setUID();
		return self::updateVCalendarFromRequest($request, $vcalendar);
	}

	public static function updateVCalendarFromRequest($request, $vcalendar)
	{
		$title = $request["title"];
		$location = $request["location"];
		$categories = $request["categories"];
		$allday = isset($request["allday"]);
		$from = $request["from"];
		$to  = $request["to"];
		if (!$allday){
			$fromtime = $request['fromtime'];
			$totime = $request['totime'];
		}
		$vevent = $vcalendar->VEVENT;
		$description = $request["description"];
		$repeat = $request["repeat"];
		if($repeat != 'doesnotrepeat'){
			$rrule = '';
			$interval = $request['interval'];
			$end = $request['end'];
			$byoccurrences = $request['byoccurrences'];
			switch($repeat){
				case 'daily':
					$rrule .= 'FREQ=DAILY';
					break;
				case 'weekly':
					$rrule .= 'FREQ=WEEKLY';
					if(array_key_exists('weeklyoptions', $request)){
						$byday = '';
						$daystrings = array_flip(self::getWeeklyOptions(OC_Calendar_App::$l10n));
						foreach($request['weeklyoptions'] as $days){
							if($byday == ''){
								$byday .= $daystrings[$days];
							}else{
								$byday .= ',' .$daystrings[$days];
							}
						}
						$rrule .= ';BYDAY=' . $byday;
					}
					break;
				case 'weekday':
					$rrule .= 'FREQ=WEEKLY';
					$rrule .= ';BYDAY=MO,TU,WE,TH,FR';
					break;
				case 'biweekly':
					$rrule .= 'FREQ=WEEKLY';
					$interval = $interval * 2;
					break;
				case 'monthly':
					$rrule .= 'FREQ=MONTHLY';
					if($request['advanced_month_select'] == 'monthday'){
						break;
					}elseif($request['advanced_month_select'] == 'weekday'){
						if($request['weekofmonthoptions'] == 'auto'){
							list($_day, $_month, $_year) = explode('-', $from);
							$weekofmonth = floor($_day/7);
						}else{
							$weekofmonth = $request['weekofmonthoptions'];
						}
						$days = array_flip(self::getWeeklyOptions(OC_Calendar_App::$l10n));
						$byday = '';
						foreach($request['weeklyoptions'] as $day){
							if($byday == ''){
								$byday .= $weekofmonth . $days[$day];
							}else{
								$byday .= ',' . $weekofmonth . $days[$day];
							}
						}
						if($byday == ''){
							$byday = 'MO,TU,WE,TH,FR,SA,SU';
						}
						$rrule .= ';BYDAY=' . $byday;
					}
					break;
				case 'yearly':
					$rrule .= 'FREQ=YEARLY';
					if($request['advanced_year_select'] == 'bydate'){
						
					}elseif($request['advanced_year_select'] == 'byyearday'){
						list($_day, $_month, $_year) = explode('-', $from);
						$byyearday = date('z', mktime(0,0,0, $_month, $_day, $_year)) + 1;
						if(array_key_exists('byyearday', $request)){
							foreach($request['byyearday'] as $yearday){
								$byyearday .= ',' . $yearday;
							}
						}
						$rrule .= ';BYYEARDAY=' . $byyearday;
					}elseif($request['advanced_year_select'] == 'byweekno'){
						list($_day, $_month, $_year) = explode('-', $from);
						$rrule .= ';BYDAY=' . strtoupper(substr(date('l', mktime(0,0,0, $_month, $_day, $_year)), 0, 2));
						$byweekno = '';
						foreach($request['byweekno'] as $weekno){
							if($byweekno == ''){
								$byweekno = $weekno;
							}else{
								$byweekno .= ',' . $weekno;
							}
						}
						$rrule .= ';BYWEEKNO=' . $byweekno;
					}elseif($request['advanced_year_select'] == 'bydaymonth'){
						if(array_key_exists('weeklyoptions', $request)){
							$days = array_flip(self::getWeeklyOptions(OC_Calendar_App::$l10n));
							$byday = '';
							foreach($request['weeklyoptions'] as $day){
								if($byday == ''){
								      $byday .= $days[$day];
								}else{
								      $byday .= ',' . $days[$day];
								}
							}
							$rrule .= ';BYDAY=' . $byday;
						}
						if(array_key_exists('bymonth', $request)){
							$monthes = array_flip(self::getByMonthOptions(OC_Calendar_App::$l10n));
							$bymonth = '';
							foreach($request['bymonth'] as $month){
								if($bymonth == ''){
								      $bymonth .= $monthes[$month];
								}else{
								      $bymonth .= ',' . $monthes[$month];
								}
							}
							$rrule .= ';BYMONTH=' . $bymonth;
							
						}
						if(array_key_exists('bymonthday', $request)){
							$bymonthday = '';
							foreach($request['bymonthday'] as $monthday){
								if($bymonthday == ''){
								      $bymonthday .= $monthday;
								}else{
								      $bymonthday .= ',' . $monthday;
								}
							}
							$rrule .= ';BYMONTHDAY=' . $bymonthday;
							
						}
					}
					break;
				default:
					break;
			}
			if($interval != ''){
				$rrule .= ';INTERVAL=' . $interval;
			}
			if($end == 'count'){
				$rrule .= ';COUNT=' . $byoccurrences;
			}
			if($end == 'date'){
				list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
				$rrule .= ';UNTIL=' . $bydate_year . $bydate_month . $bydate_day;
			}
			$vevent->setString('RRULE', $rrule);
			$repeat = "true";
		}else{
			$repeat = "false";
		}


		$vevent->setDateTime('LAST-MODIFIED', 'now', Sabre_VObject_Property_DateTime::UTC);
		$vevent->setDateTime('DTSTAMP', 'now', Sabre_VObject_Property_DateTime::UTC);
		$vevent->setString('SUMMARY', $title);

		if($allday){
			$start = new DateTime($from);
			$end = new DateTime($to.' +1 day');
			$vevent->setDateTime('DTSTART', $start, Sabre_VObject_Property_DateTime::DATE);
			$vevent->setDateTime('DTEND', $end, Sabre_VObject_Property_DateTime::DATE);
		}else{
			$timezone = OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
			$timezone = new DateTimeZone($timezone);
			$start = new DateTime($from.' '.$fromtime, $timezone);
			$end = new DateTime($to.' '.$totime, $timezone);
			$vevent->setDateTime('DTSTART', $start, Sabre_VObject_Property_DateTime::LOCALTZ);
			$vevent->setDateTime('DTEND', $end, Sabre_VObject_Property_DateTime::LOCALTZ);
		}
		unset($vevent->DURATION);

		$vevent->setString('LOCATION', $location);
		$vevent->setString('DESCRIPTION', $description);
		$vevent->setString('CATEGORIES', $categories);

		/*if($repeat == "true"){
			$vevent->RRULE = $repeat;
		}*/

		return $vcalendar;
	}

	public static function getowner($id){
		$event = self::find($id);
		$cal = OC_Calendar_Calendar::find($event['calendarid']);
		return $cal['userid'];
	}
	
	public static function getCalendarid($id){
		$event = self::find($id);
		return $event['calendarid'];
	}
}
