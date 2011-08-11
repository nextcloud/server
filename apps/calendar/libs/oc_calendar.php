<?php
/**
/ ownCloud - Calendar Plugin
/
/ (c) Copyright 2011 Georg Ehrke
/ author: Georg Ehrke
/ email: ownclouddev at georgswebsite dot de
/ homepage: ownclouddev.georgswebsite.de
/ License: GPL
/ <http://www.gnu.org/licenses/>.
*/
class OC_CALENDAR_ICAL{
	public $defaultview;
	public $calendar;
	public $calendar_name;
	public $calendar_status;
	public $ics_properties;
	public $VCALENDAR;
	public $VTIMEZONE;
	public $DAYLIGHT;
	
	function __construct(){
		include("cfg/" . OC_USER::getUser() . ".cfg.php");
		include("iCalcreator.php");
		$this->defaultview = $defaultview;
		$this->calendar = $calendar;
		$this->calendar_name = $calendar_name;
		$this->calendarstatus = $calendar_status;
	}
	
	public function load_ics($path){
		if(!file_exists("../../data/" . OC_USER::getUser() . $path)){
			return false;
		}else{
			$calfile = file("../../data/" . OC_USER::getUser() . $path);
			$return = array();
			$eventnum = 0;
			foreach($calfile as $line){
				
				
				
			}
		}
	}
	
	public function converttojs($cal){
		
		
		
		
	}
	
	public function addevent($event, $calendar){
		
	}
	
	public function removeevent($event, $calendar){
		
	}
	
	public function changeevent($event, $calendar){
		
	}
	
	public function moveevent($event, $calendar){
		
	}
	
	public function choosecalendar_dialog(){
		
	 }
}
