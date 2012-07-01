<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
/*
 * This class does import and converts all times to the users current timezone
 */
class OC_Calendar_Import{
	/*
	 * @brief var saves if the percentage should be saved with OC_Cache
	 */
	private $cacheprogress;
	
	/*
	 * @brief Sabre_VObject_Component_VCalendar object - for documentation see http://code.google.com/p/sabredav/wiki/Sabre_VObject_Component_VCalendar
	 */
	private $calobject;
	
	/*
	 * @brief var counts the number of imported elements
	 */
	private $count;
	
	/*
	 * @brief var to check if errors happend while initialization
	 */
	private $error;
	
	/*
	 * @brief var saves the ical string that was submitted with the __construct function
	 */
	private $ical;
	
	/*
	 * @brief calendar id for import
	 */
	private $id;
	
	/*
	 * @brief var saves the percentage of the import's progress
	 */
	private $progress;
	
	/*
	 * @brief var saves the timezone the events shell converted to
	 */
	private $tz;
	
	/*
	 * @brief var saves the userid
	 */
	private $userid;

	/*
	 * public methods 
	 */
	
	/*
	 * @brief does general initialization for import object
	 * @param string $calendar content of ical file
	 * @param string $tz timezone of the user
	 * @return boolean
	 */
	public function __construct($ical){
		$this->error = null;
		$this->ical = $ical;
		$this->count = 0;
		try{
			$this->calobject = OC_VObject::parse($this->ical);
		}catch(Exception $e){
			//MISSING: write some log
			$this->error = true;
			return false;
		}
		return true;
	}
	
	/*
	 * @brief imports a calendar
	 * @return boolean
	 */
	public function import(){
		if(!$this->isValid()){
			return false;
		}
		foreach($this->calobject->getComponents() as $object){
			if(!($object instanceof Sabre_VObject_Component_VEvent) && !($object instanceof Sabre_VObject_Component_VJournal) && !($object instanceof Sabre_VObject_Component_VTodo)){
				continue;
			}
			$dtend = OC_Calendar_Object::getDTEndFromVEvent($object);
			$object->DTSTART->getDateTime()->setTimezone(new DateTimeZone($this->tz));
			$object->DTEND->setDateTime($dtend->getDateTime(), $object->DTSTART->getDateType());
			$object->DTEND->getDateTime()->setTimezone(new DateTimeZone($this->tz));
			$vcalendar = $this->createVCalendar($object->serialize());
			$insertid = OC_Calendar_Object::add($this->id, $vcalendar);
			if($this->isDuplicate($insertid)){
				OC_Calendar_Object::delete($insertid);
			}else{
				$this->count++;	
			}
		}
		return true;
	}
	
	/*
	 * @brief sets the timezone
	 * @return boolean
	 */
	public function setTimeZone($tz){
		$this->tz = $tz;
		return true;
	}
	
	/*
	 * @brief checks if something went wrong while initialization
	 * @return boolean
	 */
	public function isValid(){
		if(is_null($this->error)){
			return true;
		}
		return false;
	}
	
	/*
	 * @brief returns the percentage of progress
	 * @return integer
	 */
	public function getProgress(){
		return $this->progress;
	}
	
	/*
	 * @brief enables the cache for the percentage of progress
	 * @return boolean
	 */
	public function enableProgressCache(){
		$this->cacheprogress = true;
		return true;
	}
	
	/*
	 * @brief disables the cache for the percentage of progress
	 * @return boolean
	 */
	public function disableProgressCache(){
		$this->cacheprogress = false;
		return false;
	}
	
	/*
	 * @brief generates a new calendar name
	 * @return string
	 */
	public function createCalendarName(){	
		$calendars = OC_Calendar_Calendar::allCalendars($this->userid);
		$calendarname = $guessedcalendarname = !is_null($this->guessCalendarName())?($this->guessCalendarName()):(OC_Calendar_App::$l10n->t('New Calendar'));
		$i = 1;
		while(!OC_Calendar_Calendar::isCalendarNameavailable($calendarname, $this->userid)){
			$calendarname = $guessedcalendarname . ' (' . $i . ')';
			$i++;
		}
		return $calendarname;
	}
	
	/*
	 * @brief generates a new calendar color
	 * @return string
	 */
	public function createCalendarColor(){
		if(is_null($this->guessCalendarColor())){
			return '#9fc6e7';
		}
		return $this->guessCalendarColor();
	}
	
	/*
	 * @brief sets the id for the calendar
	 * @param integer $id of the calendar
	 * @return boolean
	 */
	public function setCalendarID($id){
		$this->id = $id;
		return true;
	}
	
	/*
	 * @brief sets the userid to import the calendar
	 * @param string $id of the user
	 * @return boolean
	 */
	public function setUserID($userid){
		$this->userid = $userid;
		return true;
	}
	
	/*
	 * @brief returns the private 
	 * @param string $id of the user
	 * @return boolean
	 */
	public function getCount(){
		return $this->count;
	}

	/*
	 * private methods 
	 */
	
	/*
	 * @brief generates an unique ID 
	 * @return string 
	 */
	private function createUID(){
		return substr(md5(rand().time()),0,10);
	}
	
	/*
	 * @brief checks is the UID is already in use for another event
	 * @param string $uid uid to check
	 * @return boolean
	 */
	private function isUIDAvailable($uid){
		
	}
	
	/*
	 * @brief generates a proper VCalendar string
	 * @param string $vobject
	 * @return string 
	 */
	private function createVCalendar($vobject){
		if(is_object($vobject)){
			$vobject = @$vobject->serialize();
		}
		$vcalendar = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:ownCloud Calendar " . OCP\App::getAppVersion('calendar') . "\n";
		$vcalendar .= $vobject;
		$vcalendar .= "END:VCALENDAR";
		return $vcalendar;
	}
	
	/*
	 * @brief checks if an event already exists in the user's calendars
	 * @param integer $insertid id of the new object
	 * @return boolean
	 */
	private function isDuplicate($insertid){
		$newobject = OC_Calendar_Object::find($insertid);
		$stmt = OCP\DB::prepare('SELECT COUNT(*) as count FROM *PREFIX*calendar_objects WHERE objecttype=? AND startdate=? AND enddate=? AND repeating=? AND summary=? AND calendardata=?');
		$result = $stmt->execute(array($newobject['objecttype'],$newobject['startdate'],$newobject['enddate'],$newobject['repeating'],$newobject['summary'],$newobject['calendardata']));
		$result = $result->fetchRow();
		if($result['count'] >= 2){
			return true;
		}
		return false;
	}
	
	/*
	 * @brief 
	 * @return 
	 */
	//private function (){

	//}

	/*
	 * public methods for (pre)rendering of X-... Attributes
	 */
	
	/*
	 * @brief guesses the calendar color
	 * @return mixed - string or boolean
	 */
	public function guessCalendarColor(){
		if(!is_null($this->calobject->__get('X-APPLE-CALENDAR-COLOR'))){
			return $this->calobject->__get('X-APPLE-CALENDAR-COLOR');
		}
		return null;
	}
	
	/*
	 * @brief guesses the calendar description
	 * @return mixed - string or boolean
	 */
	public function guessCalendarDescription(){
		if(!is_null($this->calobject->__get('X-WR-CALDESC'))){
			return $this->calobject->__get('X-WR-CALDESC');
		}
		return null;
	}
	
	/*
	 * @brief guesses the calendar name
	 * @return mixed - string or boolean
	 */
	public function guessCalendarName(){
		if(!is_null($this->calobject->__get('X-WR-CALNAME'))){
			return $this->calobject->__get('X-WR-CALNAME');
		}
		return null;
	}
}
