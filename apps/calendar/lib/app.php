<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2012 Georg Ehrke <georg@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 * 
 * This class manages our app actions
 */
OC_Calendar_App::$l10n = new OC_L10N('calendar');
OC_Calendar_App::$tz = OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
class OC_Calendar_App{
	const CALENDAR = 'calendar';
	const EVENT = 'event';
	/*
	 * @brief language object for calendar app
	 */
	public static $l10n;
	
	/*
	 * @brief categories of the user
	 */
	protected static $categories = null;

	/*
	 * @brief timezone of the user
	 */
	public static $tz;
	
	/*
	 * @brief returns informations about a calendar
	 * @param int $id - id of the calendar
	 * @param bool $security - check access rights or not
	 * @param bool $shared - check if the user got access via sharing
	 * @return mixed - bool / array
	 */
	public static function getCalendar($id, $security = true, $shared = false){
		if(! is_numeric($id)){
			return false;
		}
		$calendar = OC_Calendar_Calendar::find($id);
		if($shared === true){
			if(OC_Calendar_Share::check_access(OCP\USER::getUser(), $id, OC_Calendar_Share::CALENDAR)){
				return $calendar;
			}
		}
		if($security === true){
			if($calendar['userid'] != OCP\USER::getUser()){
				return false;
			}
		}
		if($calendar === false){
			return false;
		}
		return OC_Calendar_Calendar::find($id);
	}
	
	/*
	 * @brief returns informations about an event
	 * @param int $id - id of the event
	 * @param bool $security - check access rights or not
	 * @param bool $shared - check if the user got access via sharing
	 * @return mixed - bool / array
	 */
	public static function getEventObject($id, $security = true, $shared = false){
		$event = OC_Calendar_Object::find($id);
		if($shared === true){
			if(OC_Calendar_Share::check_access(OCP\USER::getUser(), $id, OC_Calendar_Share::EVENT)){
				return $event;
			}
		}
		if($security === true){
			$calendar = self::getCalendar($event['calendarid'], false);
			if($calendar['userid'] != OCP\USER::getUser()){
				return false;
			}
		}
		if($event === false){
			return false;
		}
		return $event;
	}
	
	/*
	 * @brief returns the parsed calendar data
	 * @param int $id - id of the event
	 * @param bool $security - check access rights or not
	 * @return mixed - bool / object
	 */
	public static function getVCalendar($id, $security = true, $shared = false){
		$event_object = self::getEventObject($id, $security, $shared);
		if($event_object === false){
			return false;
		}
		$vobject = OC_VObject::parse($event_object['calendardata']);
		if(is_null($vobject)){
			return false;
		}
		return $vobject;
	}
	
	/*
	 * @brief checks if an event was edited and dies if it was
	 * @param (object) $vevent - vevent object of the event
	 * @param (int) $lastmodified - time of last modification as unix timestamp
	 * @return (bool)
	 */
	public static function isNotModified($vevent, $lastmodified){
		$last_modified = $vevent->__get('LAST-MODIFIED');
		if($last_modified && $lastmodified != $last_modified->getDateTime()->format('U')){
			OCP\JSON::error(array('modified'=>true));
			exit;
		}
		return true;
	}
	
	/*
	 * @brief returns the default categories of ownCloud
	 * @return (array) $categories
	 */
	protected static function getDefaultCategories()
	{
		return array(
			self::$l10n->t('Birthday'),
			self::$l10n->t('Business'),
			self::$l10n->t('Call'),
			self::$l10n->t('Clients'),
			self::$l10n->t('Deliverer'),
			self::$l10n->t('Holidays'),
			self::$l10n->t('Ideas'),
			self::$l10n->t('Journey'),
			self::$l10n->t('Jubilee'),
			self::$l10n->t('Meeting'),
			self::$l10n->t('Other'),
			self::$l10n->t('Personal'),
			self::$l10n->t('Projects'),
			self::$l10n->t('Questions'),
			self::$l10n->t('Work'),
		);
	}
	
	/*
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	protected static function getVCategories() {
		if (is_null(self::$categories)) {
			self::$categories = new OC_VCategories('calendar', null, self::getDefaultCategories());
		}
		return self::$categories;
	}
	
	/*
	 * @brief returns the categories of the vcategories object
	 * @return (array) $categories
	 */
	public static function getCategoryOptions()
	{
		$categories = self::getVCategories()->categories();
		return $categories;
	}

	/**
	 * scan events for categories.
	 * @param $events VEVENTs to scan. null to check all events for the current user.
	 */
	public static function scanCategories($events = null) {
		if (is_null($events)) {
			$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
			if(count($calendars) > 0) {
				$events = array();
				foreach($calendars as $calendar) {
					$calendar_events = OC_Calendar_Object::all($calendar['id']);
					$events = $events + $calendar_events;
				}
			}
		}
		if(is_array($events) && count($events) > 0) {
			$vcategories = self::getVCategories();
			$vcategories->delete($vcategories->categories());
			foreach($events as $event) {
				$vobject = OC_VObject::parse($event['calendardata']);
				if(!is_null($vobject)) {
					self::loadCategoriesFromVCalendar($vobject);
				}
			}
		}
	}

	/**
	 * check VEvent for new categories.
	 * @see OC_VCategories::loadFromVObject
	 */
	public static function loadCategoriesFromVCalendar(OC_VObject $calendar) {
		$object = null;
		if (isset($calendar->VEVENT)) {
			$object = $calendar->VEVENT;
		} else
		if (isset($calendar->VTODO)) {
			$object = $calendar->VTODO;
		}
		if ($object) {
			self::getVCategories()->loadFromVObject($object, true);
		}
	}

	public static function getRepeatOptions(){
		return OC_Calendar_Object::getRepeatOptions(self::$l10n);
	}
	
	/*
	 * @brief returns the options for the end of an repeating event
	 * @return array - valid inputs for the end of an repeating events
	 */
	public static function getEndOptions(){
		return OC_Calendar_Object::getEndOptions(self::$l10n);
	}
	
	/*
	 * @brief returns the options for an monthly repeating event
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getMonthOptions(){
		return OC_Calendar_Object::getMonthOptions(self::$l10n);
	}
	
	/*
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptions(){
		return OC_Calendar_Object::getWeeklyOptions(self::$l10n);
	}
	
	/*
	 * @brief returns the options for an yearly repeating event
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getYearOptions(){
		return OC_Calendar_Object::getYearOptions(self::$l10n);
	}
	
	/*
	 * @brief returns the options for an yearly repeating event which occurs on specific days of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByYearDayOptions(){
		return OC_Calendar_Object::getByYearDayOptions();
	}
	
	/*
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthOptions(){
		return OC_Calendar_Object::getByMonthOptions(self::$l10n);
	}
	
	/*
	 * @brief returns the options for an yearly repeating event which occurs on specific week numbers of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByWeekNoOptions(){
		return OC_Calendar_Object::getByWeekNoOptions();
	}
	
	/*
	 * @brief returns the options for an yearly or monthly repeating event which occurs on specific days of the month
	 * @return array - valid inputs for yearly or monthly repeating events
	 */
	public static function getByMonthDayOptions(){
		return OC_Calendar_Object::getByMonthDayOptions();
	}
	
	/*
	 * @brief returns the options for an monthly repeating event which occurs on specific weeks of the month
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getWeekofMonth(){
		return OC_Calendar_Object::getWeekofMonth(self::$l10n);
	}
	
	/*
	 * @brief checks the access for a calendar / an event
	 * @param (int) $id - id of the calendar / event
	 * @param (string) $type - type of the id (calendar/event)
	 * @return (string) $access - level of access
	 */
	public static function getaccess($id, $type){
		if($type == self::CALENDAR){
			$calendar = self::getCalendar($id, false, false);
			if($calendar['userid'] == OCP\USER::getUser()){
				return 'owner';
			}
			$isshared = OC_Calendar_Share::check_access(OCP\USER::getUser(), $id, OC_Calendar_Share::CALENDAR);
			if($isshared){
				$writeaccess = OC_Calendar_Share::is_editing_allowed(OCP\USER::getUser(), $id, OC_Calendar_Share::CALENDAR);
				if($writeaccess){
					return 'rw';
				}else{
					return 'r';
				}
			}else{
				return false;
			}
		}elseif($type == self::EVENT){
			if(OC_Calendar_Object::getowner($id) == OCP\USER::getUser()){
				return 'owner';
			}
			$isshared = OC_Calendar_Share::check_access(OCP\USER::getUser(), $id, OC_Calendar_Share::EVENT);
			if($isshared){
				$writeaccess = OC_Calendar_Share::is_editing_allowed(OCP\USER::getUser(), $id, OC_Calendar_Share::EVENT);
				if($writeaccess){
					return 'rw';
				}else{
					return 'r';
				}
			}else{
				return false;
			}
		}
	}
	
	/*
	 * @brief analyses the parameter for calendar parameter and returns the objects
	 * @param (string) $calendarid - calendarid
	 * @param (int) $start - unixtimestamp of start
	 * @param (int) $end - unixtimestamp of end
	 * @return (array) $events 
	 */
	public static function getrequestedEvents($calendarid, $start, $end){
		$events = array();
		if($calendarid == 'shared_rw' || $calendarid == 'shared_r'){
			$calendars = OC_Calendar_Share::allSharedwithuser(OCP\USER::getUser(), OC_Calendar_Share::CALENDAR, 1, ($_GET['calendar_id'] == 'shared_rw')?'rw':'r');
			foreach($calendars as $calendar){
				$calendarevents = OC_Calendar_Object::allInPeriod($calendar['calendarid'], $start, $end);
				foreach($calendarevents as $event){
					$event['summary'] .= ' (' . self::$l10n->t('by') .  ' ' . OC_Calendar_Object::getowner($event['id']) . ')';
				}
				$events = array_merge($events, $calendarevents);
			}
			$singleevents = OC_Calendar_Share::allSharedwithuser(OCP\USER::getUser(), OC_Calendar_Share::EVENT, 1, ($_GET['calendar_id'] == 'shared_rw')?'rw':'r');
			foreach($singleevents as $singleevent){
				$event = OC_Calendar_Object::find($singleevent['eventid']);
				if(!array_key_exists('summary', $event)){
					$event['summary'] = self::$l10n->t('unnamed');
				}
				$event['summary'] .= ' (' . self::$l10n->t('by') .  ' ' . OC_Calendar_Object::getowner($event['id']) . ')';
				$events[] =  $event;
			}
		}else{
			if (is_numeric($calendarid)) {
				$calendar = self::getCalendar($calendarid);
				OCP\Response::enableCaching(0);
				OCP\Response::setETagHeader($calendar['ctag']);
				$events = OC_Calendar_Object::allInPeriod($calendarid, $start, $end);
			} else {
				OCP\Util::emitHook('OC_Calendar', 'getEvents', array('calendar_id' => $calendarid, 'events' => &$events));
			}
		}
		return $events;
	}
	
	/*
	 * @brief generates the output for an event which will be readable for our js
	 * @param (mixed) $event - event object / array
	 * @param (int) $start - unixtimestamp of start
	 * @param (int) $end - unixtimestamp of end
	 * @return (array) $output - readable output
	 */
	public static function generateEventOutput($event, $start, $end){
		$output = array();
		
		if(isset($event['calendardata'])){
			$object = OC_VObject::parse($event['calendardata']);
			$vevent = $object->VEVENT;
		}else{
			$vevent = $event['vevent'];
		}
		
		$last_modified = @$vevent->__get('LAST-MODIFIED');
		$lastmodified = ($last_modified)?$last_modified->getDateTime()->format('U'):0;
		
		$output = array('id'=>(int)$event['id'],
						'title' => ($event['summary']!=NULL || $event['summary'] != '')?$event['summary']: self::$l10n->t('unnamed'),
						'description' => isset($vevent->DESCRIPTION)?$vevent->DESCRIPTION->value:'',
						'lastmodified'=>$lastmodified);
		
		$dtstart = $vevent->DTSTART;
		$start_dt = $dtstart->getDateTime();
		$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
		$end_dt = $dtend->getDateTime();
		
		if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE){
			$output['allDay'] = true;
		}else{
			$output['allDay'] = false;
			$start_dt->setTimezone(new DateTimeZone(self::$tz));
			$end_dt->setTimezone(new DateTimeZone(self::$tz));
		}

		// Handle exceptions to recurring events
		$exceptionDateObjects = $vevent->select('EXDATE');
		$exceptionDateMap = Array();
		foreach ($exceptionDateObjects as $exceptionObject) {
			foreach($exceptionObject->getDateTimes() as $datetime) {
				$ts = $datetime->getTimestamp();
				$exceptionDateMap[idate('Y',$ts)][idate('m', $ts)][idate('d', $ts)] = true;
			}
		}

		$return = array();
		if($event['repeating'] == 1){
			$duration = (double) $end_dt->format('U') - (double) $start_dt->format('U');
			$r = new When();
			$r->recur($start_dt)->rrule((string) $vevent->RRULE);
			/*$r = new iCal_Repeat_Generator(array('RECUR'  => $start_dt,
			 *									   'RRULE'  => (string)$vevent->RRULE
			 *									   'RDATE'  => (string)$vevent->RDATE						
			 *									   'EXRULE' => (string)$vevent->EXRULE
			 *									   'EXDATE' => (string)$vevent->EXDATE));*/
			while($result = $r->next()){
				if($result < $start){
					continue;
				}
				if($result > $end){
					break;
				}
				// Check for exceptions to recurring events
				$ts = $result->getTimestamp();
				if (isset($exceptionDateMap[idate('Y',$ts)][idate('m', $ts)][idate('d', $ts)])) {
					continue;
				}
				unset($ts);

				if($output['allDay'] == true){
					$output['start'] = $result->format('Y-m-d');
					$output['end'] = date('Y-m-d', $result->format('U') + --$duration);
				}else{
					$output['start'] = $result->format('Y-m-d H:i:s');
					$output['end'] = date('Y-m-d H:i:s', $result->format('U') + $result->format('Z') + $duration);
				}
				$return[] = $output;
			}
		}else{
			if($output['allDay'] == true){
				$output['start'] = $start_dt->format('Y-m-d');
				$end_dt->modify('-1 sec');
				$output['end'] = $end_dt->format('Y-m-d');
			}else{
				$output['start'] = $start_dt->format('Y-m-d H:i:s');
				$output['end'] = $end_dt->format('Y-m-d H:i:s');
			}
			$return[] = $output;
		}
		return $return;
	}
}
