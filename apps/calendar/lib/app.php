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
OC_Calendar_App::$tz = OC_Calendar_App::getTimezone();
class OC_Calendar_App{
	const CALENDAR = 'calendar';
	const EVENT = 'event';
	/**
	 * @brief language object for calendar app
	 */
	public static $l10n;
	
	/**
	 * @brief categories of the user
	 */
	protected static $categories = null;

	/**
	 * @brief timezone of the user
	 */
	public static $tz;
	
	/**
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
		return $calendar;
	}
	
	/**
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
	
	/**
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
	
	/**
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
	
	/**
	 * @brief returns the default categories of ownCloud
	 * @return (array) $categories
	 */
	protected static function getDefaultCategories(){
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
	
	/**
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	protected static function getVCategories() {
		if (is_null(self::$categories)) {
			self::$categories = new OC_VCategories('calendar', null, self::getDefaultCategories());
		}
		return self::$categories;
	}
	
	/**
	 * @brief returns the categories of the vcategories object
	 * @return (array) $categories
	 */
	public static function getCategoryOptions(){
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
		} else
		if (isset($calendar->VJOURNAL)) {
			$object = $calendar->VJOURNAL;
		}
		if ($object) {
			self::getVCategories()->loadFromVObject($object, true);
		}
	}
	
	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getRepeatOptions(){
		return OC_Calendar_Object::getRepeatOptions(self::$l10n);
	}
	
	/**
	 * @brief returns the options for the end of an repeating event
	 * @return array - valid inputs for the end of an repeating events
	 */
	public static function getEndOptions(){
		return OC_Calendar_Object::getEndOptions(self::$l10n);
	}
	
	/**
	 * @brief returns the options for an monthly repeating event
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getMonthOptions(){
		return OC_Calendar_Object::getMonthOptions(self::$l10n);
	}
	
	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptions(){
		return OC_Calendar_Object::getWeeklyOptions(self::$l10n);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getYearOptions(){
		return OC_Calendar_Object::getYearOptions(self::$l10n);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific days of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByYearDayOptions(){
		return OC_Calendar_Object::getByYearDayOptions();
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthOptions(){
		return OC_Calendar_Object::getByMonthOptions(self::$l10n);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific week numbers of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByWeekNoOptions(){
		return OC_Calendar_Object::getByWeekNoOptions();
	}
	
	/**
	 * @brief returns the options for an yearly or monthly repeating event which occurs on specific days of the month
	 * @return array - valid inputs for yearly or monthly repeating events
	 */
	public static function getByMonthDayOptions(){
		return OC_Calendar_Object::getByMonthDayOptions();
	}
	
	/**
	 * @brief returns the options for an monthly repeating event which occurs on specific weeks of the month
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getWeekofMonth(){
		return OC_Calendar_Object::getWeekofMonth(self::$l10n);
	}

	/**
	 * @return (string) $timezone as set by user or the default timezone
	 */
	public static function getTimezone() {
		return OCP\Config::getUserValue(OCP\User::getUser(),
						'calendar',
						'timezone',
						date_default_timezone_get());
	}

	/**
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
	
	/**
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
	
	/**
	 * @brief generates the output for an event which will be readable for our js
	 * @param (mixed) $event - event object / array
	 * @param (int) $start - DateTime object of start
	 * @param (int) $end - DateTime object of end
	 * @return (array) $output - readable output
	 */
	public static function generateEventOutput($event, $start, $end){
		if(!isset($event['calendardata']) && !isset($event['vevent'])){
			return false;
		}
		if(!isset($event['calendardata']) && isset($event['vevent'])){
			$event['calendardata'] = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:ownCloud's Internal iCal System\n" . $event['vevent']->serialize() .  "END:VCALENDAR";
		}
		$object = OC_VObject::parse($event['calendardata']);
		$vevent = $object->VEVENT;
		$return = array();
		$id = $event['id'];
		$allday = ($vevent->DTSTART->getDateType() == Sabre_VObject_Element_DateTime::DATE)?true:false;
		$last_modified = @$vevent->__get('LAST-MODIFIED');
		$lastmodified = ($last_modified)?$last_modified->getDateTime()->format('U'):0;
		$staticoutput = array('id'=>(int)$event['id'],
						'title' => ($event['summary']!=NULL || $event['summary'] != '')?$event['summary']: self::$l10n->t('unnamed'),
						'description' => isset($vevent->DESCRIPTION)?$vevent->DESCRIPTION->value:'',
						'lastmodified'=>$lastmodified,
						'allDay'=>$allday);
		if(OC_Calendar_Object::isrepeating($id) && OC_Calendar_Repeat::is_cached_inperiod($event['id'], $start, $end)){
			$cachedinperiod = OC_Calendar_Repeat::get_inperiod($id, $start, $end);
			foreach($cachedinperiod as $cachedevent){
				$dynamicoutput = array();
				if($allday){
					$start_dt = new DateTime($cachedevent['startdate'], new DateTimeZone('UTC'));
					$end_dt = new DateTime($cachedevent['enddate'], new DateTimeZone('UTC'));
					$dynamicoutput['start'] = $start_dt->format('Y-m-d');
					$dynamicoutput['end'] = $end_dt->format('Y-m-d');
				}else{
					$start_dt = new DateTime($cachedevent['startdate'], new DateTimeZone('UTC'));
					$end_dt = new DateTime($cachedevent['enddate'], new DateTimeZone('UTC'));
					$start_dt->setTimezone(new DateTimeZone(self::$tz));
					$end_dt->setTimezone(new DateTimeZone(self::$tz));
					$dynamicoutput['start'] = $start_dt->format('Y-m-d H:i:s');
					$dynamicoutput['end'] = $end_dt->format('Y-m-d H:i:s');
				}
				$return[] = array_merge($staticoutput, $dynamicoutput);
			}
		}else{
			if(OC_Calendar_Object::isrepeating($id) || $event['repeating'] == 1){
				$object->expand($start, $end);
			}
			foreach($object->getComponents() as $singleevent){
				if(!($singleevent instanceof Sabre_VObject_Component_VEvent)){
					continue;
				}
				$dynamicoutput = OC_Calendar_Object::generateStartEndDate($singleevent->DTSTART, OC_Calendar_Object::getDTEndFromVEvent($singleevent), $allday, self::$tz);
				$return[] = array_merge($staticoutput, $dynamicoutput);			
			}
		}
		return $return;
	}
}
