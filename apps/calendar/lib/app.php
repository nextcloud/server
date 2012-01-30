<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class manages our app actions
 */
OC_Calendar_App::$l10n = new OC_L10N('calendar');
class OC_Calendar_App{
	public static $l10n;

	public static function getCalendar($id){
		$calendar = OC_Calendar_Calendar::find( $id );
		if( $calendar === false || $calendar['userid'] != OC_User::getUser()){
			OC_JSON::error(array('data' => array('message' => self::$l10n->t('Wrong calendar'))));
			exit();
		}
		return $calendar;
	}

	public static function getEventObject($id){
		$event_object = OC_Calendar_Object::find( $id );
		if( $event_object === false ){
			OC_JSON::error();
			exit();
		}

		self::getCalendar( $event_object['calendarid'] );//access check
		return $event_object;
	}

	public static function getVCalendar($id){
		$event_object = self::getEventObject( $id );

		$vcalendar = OC_VObject::parse($event_object['calendardata']);
		// Check if the vcalendar is valid
		if(is_null($vcalendar)){
			OC_JSON::error();
			exit();
		}
		return $vcalendar;
	}

	public static function isNotModified($vevent, $lastmodified)
	{
		$last_modified = $vevent->__get('LAST-MODIFIED');
		if($last_modified && $lastmodified != $last_modified->getDateTime()->format('U')){
			OC_JSON::error(array('modified'=>true));
			exit;
		}
	}

	public static function getCategoryOptions()
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

	public static function getRepeatOptions(){
		return OC_Calendar_Object::getRepeatOptions(self::$l10n);
	}

	public static function getEndOptions(){
		return OC_Calendar_Object::getEndOptions(self::$l10n);
	}

	public static function getMonthOptions(){
		return OC_Calendar_Object::getMonthOptions(self::$l10n);
	}

	public static function getWeeklyOptions(){
		return OC_Calendar_Object::getWeeklyOptions(self::$l10n);
	}

	public static function getYearOptions(){
		return OC_Calendar_Object::getYearOptions(self::$l10n);
	}

	public static function getByYearDayOptions(){
		return OC_Calendar_Object::getByYearDayOptions();
	}

	public static function getByMonthOptions(){
		return OC_Calendar_Object::getByMonthOptions(self::$l10n);
	}
	
	public static function getByWeekNoOptions(){
		return OC_Calendar_Object::getByWeekNoOptions();
	}

	public static function getByMonthDayOptions(){
		return OC_Calendar_Object::getByMonthDayOptions();
	}
	
	public static function getWeekofMonth(){
		return OC_Calendar_Object::getWeekofMonth(self::$l10n);
	}
}
