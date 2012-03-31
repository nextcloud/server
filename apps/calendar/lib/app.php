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
class OC_Calendar_App{
	/*
	 * @brief: language object for calendar app
	 */
	public static $l10n;

	/*
	 * @brief: returns informations about a calendar
	 * @param: int $id - id of the calendar
	 * @param: bool $security - check access rights or not
	 * @param: bool $shared - check if the user got access via sharing
	 * @return: mixed - bool / array
	 */
	public static function getCalendar($id, $security = true, $shared = false){
		$calendar = OC_Calendar_Object::find($id);
		if($shared === true){
			if(OC_Calendar_Share::check_access(OC_User::getUser(), $id, OC_Calendar_Share::CALENDAR)){
				return $calendar;
			}
		}
		if($security === true){
			if($calendar['userid'] != OC_User::getUser()){
				return false;
			}
		}
		if($calendar === false){
			return false;
		}
		return OC_Calendar_Calendar::find($id);
	}
	
	/*
	 * @brief: returns informations about an event
	 * @param: int $id - id of the event
	 * @param: bool $security - check access rights or not
	 * @param: bool $shared - check if the user got access via sharing
	 * @return: mixed - bool / array
	 */
	public static function getEventObject($id, $security = true, $shared = false){
		$event = OC_Calendar_Object::find($id);
		if($shared === true){
			if(OC_Calendar_Share::check_access(OC_User::getUser(), $id, OC_Calendar_Share::EVENT)){
				return $calendar;
			}
		}
		if($security === true){
			$calendar = self::getCalendar($event['calendarid'], false);
			if($calendar['userid'] != OC_User::getUser()){
				return false;
			}
		}
		if($event === false){
			return false;
		}
		return $event;
	}
	
	/*
	 * @brief: returns the parsed calendar data
	 * @param: int $id - id of the event
	 * @param: bool $security - check access rights or not
	 * @return: mixed - bool / object
	 */
	public static function getVCalendar($id, $security = true){
		$event_object = self::getEventObject($id, $security);
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
	 * 
	 */
	public static function isNotModified($vevent, $lastmodified){
		$last_modified = $vevent->__get('LAST-MODIFIED');
		if($last_modified && $lastmodified != $last_modified->getDateTime()->format('U')){
			OC_JSON::error(array('modified'=>true));
			exit;
		}
	}
	/*
	 * THIS FUNCTION IS DEPRECATED AND WILL BE REMOVED SOON
	 * @brief: returns the valid categories
	 * @return: array - categories
	 */
	public static function getCategoryOptions(){
		return OC_Calendar_Object::getCategoryOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for an repeating event
	 * @return: array - valid inputs for repeating events
	 */
	public static function getRepeatOptions(){
		return OC_Calendar_Object::getRepeatOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for the end of an repeating event
	 * @return: array - valid inputs for the end of an repeating events
	 */
	public static function getEndOptions(){
		return OC_Calendar_Object::getEndOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for an monthly repeating event
	 * @return: array - valid inputs for monthly repeating events
	 */
	public static function getMonthOptions(){
		return OC_Calendar_Object::getMonthOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for an weekly repeating event
	 * @return: array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptions(){
		return OC_Calendar_Object::getWeeklyOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for an yearly repeating event
	 * @return: array - valid inputs for yearly repeating events
	 */
	public static function getYearOptions(){
		return OC_Calendar_Object::getYearOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for an yearly repeating event which occurs on specific days of the year
	 * @return: array - valid inputs for yearly repeating events
	 */
	public static function getByYearDayOptions(){
		return OC_Calendar_Object::getByYearDayOptions();
	}
	
	/*
	 * @brief: returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return: array - valid inputs for yearly repeating events
	 */
	public static function getByMonthOptions(){
		return OC_Calendar_Object::getByMonthOptions(self::$l10n);
	}
	
	/*
	 * @brief: returns the options for an yearly repeating event which occurs on specific week numbers of the year
	 * @return: array - valid inputs for yearly repeating events
	 */
	public static function getByWeekNoOptions(){
		return OC_Calendar_Object::getByWeekNoOptions();
	}
	
	/*
	 * @brief: returns the options for an yearly or monthly repeating event which occurs on specific days of the month
	 * @return: array - valid inputs for yearly or monthly repeating events
	 */
	public static function getByMonthDayOptions(){
		return OC_Calendar_Object::getByMonthDayOptions();
	}
	
	/*
	 * @brief: returns the options for an monthly repeating event which occurs on specific weeks of the month
	 * @return: array - valid inputs for monthly repeating events
	 */
	public static function getWeekofMonth(){
		return OC_Calendar_Object::getWeekofMonth(self::$l10n);
	}
	
	/*
	 * @brief: returns the prepared output for the json calendar data
	 * @param: array $event - array with event informations (self::getEventObject)
	 * @return: array - prepared output
	 */
	public static function prepareForOutput($event){
		if(isset($event['calendardata'])){
			$object = self::getVCalendar($event['calendardata'], false);
			$vevent = $object->VEVENT;
		}else{
			$vevent = $event['vevent'];
		}
		$last_modified = ($vevent->__get('LAST-MODIFIED'))?$last_modified->getDateTime()->format('U'):0;
		$return = array('id'=>(int)$event['id'],
						'title' => htmlspecialchars(($event['summary']!=NULL || $event['summary'] != '')?$event['summary']: self::$l10n->t('unnamed')),
						'description' => isset($vevent->DESCRIPTION)?htmlspecialchars($vevent->DESCRIPTION->value):'',
						'lastmodified'=>(int)$lastmodified);
		return $return;
	}
}
