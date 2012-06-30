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
	 * @brief var to check if errors happend while initialization
	 */
	private $error;
	
	/*
	 * @brief var saves the ical string that was submitted with the __construct function
	 */
	private $ical;
	
	/*
	 * @brief var saves the percentage of the import's progress
	 */
	private $progress;
	
	/*
	 * @brief var saves the timezone the events shell converted to
	 */
	private $tz;

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
	 * @param string $force force import even though calendar is not valid
	 * @return boolean
	 */
	public function import($force = false){
		if(!$this->isValid() && !$force){
			return false;
		}
		foreach($this->calobject->getComponents() as $object){
			if(!($object instanceof Sabre_VObject_Component_VEvent) && !($object instanceof Sabre_VObject_Component_VJournal) && !($object instanceof Sabre_VObject_Component_VTodo)){
				continue;
			}
			
		}
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
	 * @brief 
	 * @return 
	 */
	private function (){

	}
	
	/*
	 * @brief 
	 * @return 
	 */
	private function (){

	}

	/*
	 * public methods for prerendering of X-... Attributes
	 */
	
	/*
	 * @brief guesses the calendar color
	 * @return mixed - string or boolean
	 */
	public function guessCalendarColor(){
		if(!is_null($this->calobject->__get('X-APPLE-CALENDAR-COLOR'))){
			return $this->calobject->__get('X-APPLE-CALENDAR-COLOR');
		}
		return false;
	}
	
	/*
	 * @brief guesses the calendar description
	 * @return mixed - string or boolean
	 */
	public function guessCalendarDescription(){
		if(!is_null($this->calobject->__get('X-WR-CALDESC'))){
			return $this->calobject->__get('X-WR-CALDESC');
		}
		return false;
	}
	
	/*
	 * @brief guesses the calendar name
	 * @return mixed - string or boolean
	 */
	public function guessCalendarName(){
		if(!is_null($this->calobject->__get('X-WR-CALNAME'))){
			return $this->calobject->__get('X-WR-CALNAME');
		}
		return false;
	}
}
