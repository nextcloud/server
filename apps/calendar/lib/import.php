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
	 * @brief var to check if errors happend while initialization
	 */
	private $error;
	
	/*
	 * @brief Sabre_VObject_Component_VCalendar object - for documentation see http://code.google.com/p/sabredav/wiki/Sabre_VObject_Component_VCalendar
	 */
	private $calobject;
	
	/*
	 * @brief var saves the ical string that was submitted with the __construct function
	 */
	private $ical;
	
	/*
	 * @brief var saves the ical string that was submitted with the __construct function
	 */
	private $tz;
	
	/*
	 * @brief var saves the percentage of the import's progress
	 */
	private $progress;

	/*
	 * public methods 
	 */
	
	/*
	 * @brief does general initialization for import object
	 * @param string $calendar 
	 * @return boolean
	 */
	public function __construct($ical, $tz){
		$this->error = null;
		$this->ical = $ical;
		try{
			$this->calobject = OC_VObject::parse($this->ical);
		}catch(Exception $e){
			//MISSING: write some log
			$this->error = true;
			return false;
		}
		$this->tz = $tz;
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
	 * private methods 
	 */
	
	/*
	 * @brief 
	 * @return 
	 */
	private function (){

	}
	

	/*
	 * methods for X-...
	 */
	
	/*
	 * @brief guesses the calendar color
	 * @return mixed - string or boolean
	 */
	private function guessCalendarColor(){
		if(!is_null($this->calobject->__get('X-APPLE-CALENDAR-COLOR'))){
			return $this->calobject->__get('X-APPLE-CALENDAR-COLOR');
		}
		return false;
	}
	
	/*
	 * @brief guesses the calendar description
	 * @return mixed - string or boolean
	 */
	private function guessCalendarDescription(){
		if(!is_null($this->calobject->__get('X-WR-CALDESC'))){
			return $this->calobject->__get('X-WR-CALDESC');
		}
		return false;
	}
	
	/*
	 * @brief guesses the calendar name
	 * @return mixed - string or boolean
	 */
	private function guessCalendarName(){
		if(!is_null($this->calobject->__get('X-WR-CALNAME'))){
			return $this->calobject->__get('X-WR-CALNAME');
		}
		return false;
	}
}
