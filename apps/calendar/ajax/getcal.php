<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../../lib/base.php");
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('calendar');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
$events = array();
$return = array('calendars'=>array());
foreach($calendars as $calendar) {
	$tmp = OC_Calendar_Object::all($calendar['id']);
	$events = array_merge($events, $tmp);
	$return['calendars'][$calendar['id']] = array(
		'displayname' => $calendar['displayname'],
		'color'       => '#'.$calendar['calendarcolor']
	);
}

$select_year = $_GET["year"];
$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
foreach($events as $event)
{
	if($select_year != substr($event['startdate'], 0, 4) && $event["repeating"] == false)
		continue;
	if($select_year == substr($event['startdate'], 0, 4) && $event["repeating"] == false){
		$object = Sabre_VObject_Reader::read($event['calendardata']);
		$vevent = $object->VEVENT;
		$dtstart = $vevent->DTSTART;
		$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
		$start_dt = $dtstart->getDateTime();
		$start_dt->setTimezone(new DateTimeZone($user_timezone));
		$end_dt = $dtend->getDateTime();
		$end_dt->setTimezone(new DateTimeZone($user_timezone));
		$year  = $start_dt->format('Y');
		$month = $start_dt->format('n') - 1; // return is 0 based
		$day   = $start_dt->format('j');
		$hour  = $start_dt->format('G');
		if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
			$hour = 'allday';
		}

		$return_event = array();
		foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop)
		{
			$return_event[$prop] = $event[$prop];
		}
		$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
		$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
		$return_event['description'] = $event['summary'];
		if ($hour == 'allday')
		{
			$return_event['allday'] = true;
		}
		if (isset($return[$year][$month][$day][$hour]))
		{
			$return[$year][$month][$day][$hour][] = $return_event;
		}
		else
		{
			$return[$year][$month][$day][$hour] = array(1 => $return_event);
		}
	}
	if($event["repeating"] == 1){
		$object = Sabre_VObject_Reader::read($event['calendardata']);
		$vevent = $object->VEVENT;
		//echo substr_count($event["calendardata"], "EXDATE");
		$numofelements = substr_count($vevent->RRULE, ";");
		$properties = array("FREQ"=>"false", "UNTIL"=>"false", "COUNT"=>"false", "INTERVAL"=>"false", "BYDAY"=>"false", "BYMONTHDAY"=>"false", "BYWEEKNO"=>"false", "BYMONTH"=>"false", "BYYEARDAY"=>"false", "BYSETPOS"=>"false");
		$exruleproperties = array("FREQ"=>"false", "UNTIL"=>"false", "COUNT"=>"false", "INTERVAL"=>"false", "BYDAY"=>"false", "BYMONTHDAY"=>"false", "BYWEEKNO"=>"false", "BYMONTH"=>"false", "BYYEARDAY"=>"false", "BYSETPOS"=>"false");
		$byday = array("MO"=>"false", "TU"=>"false", "WE"=>"false", "TH"=>"false", "FR"=>"false", "SA"=>"false", "SU"=>"false");
		if($numofelements !=  0){
			$rrule = explode(";", $vevent->RRULE);
			for($i = 0;$i <= $numofelements;$i++){
				$rule = explode("=", $rrule[$i]);
				$property = $rule[0];
				$value = $rule[1];
				$properties[$property] = $value;
			}
			if($properties["BYDAY"] != "false"){
				$numofdays = substr_count($properties["BYDAY"], ",");
				if($numofdays == 0){
					if(strlen($properties["BYDAY"]) != 2){
						$lenght = strlen($properties["BYDAY"]);
						switch($lenght){
							case "3":
								$properties["BYSETPOS"] = substr($properties["BYDAY"],0,1);
								$properties["BYDAY"] = substr($properties["BYDAY"],1,2);
								break;
							case "4":
								$properties["BYSETPOS"] = substr($properties["BYDAY"],0,2);
								$properties["BYDAY"] = substr($properties["BYDAY"],2,2);
								break;
							case "5":
								$properties["BYSETPOS"] = substr($properties["BYDAY"],0,3);
								$properties["BYDAY"] = substr($properties["BYDAY"],3,2);
								break;
							case "6":
								$properties["BYSETPOS"] = substr($properties["BYDAY"],0,4);
								$properties["BYDAY"] = substr($properties["BYDAY"],4,2);
								break;
						}
					}
					$byday[$properties["BYDAY"]] = true;
					
				}else{
					$days = explode(",", $properties["BYDAY"]);
					for($i = 0;$i <= $numofdays;$i++){
						$day = $days[$i];
						$byday[$day] = true;
					}
				}
			}
		}else{
			$rule = explode("=", $vevent->RRULE);
			$properties[$rule[0]] = $rule[1];
		}
		if($properties["INTERVAL"] == "false"){
				$properties["INTERVAL"] = 1;
		}
		$count = 0; //counts all loops
		$countedoutputs = 0; //counts only the outputs
		$countchecker = true;
		$dtstart = $vevent->DTSTART;
		$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
		$start_dt = $dtstart->getDateTime();
		$start_dt->setTimezone(new DateTimeZone($user_timezone));
		$end_dt = $dtend->getDateTime();
		$end_dt->setTimezone(new DateTimeZone($user_timezone));
		$firststart_year  = $start_dt->format('Y');
		$firststart_month = $start_dt->format('n');
		$firststart_day   = $start_dt->format('j');
		$hour  = $start_dt->format('G');
		$interval = 0;
		if($properties["UNTIL"] != "false"){
			$until = $properties["UNTIL"];
			$until_year = substr($until, 0, 4);
			$until_month = substr($until, 4, 2);
			$until_day = substr($until, 6, 2);
		}
		//print_r($properties);
		//print_r($byday);
		if($properties["FREQ"] == "DAILY"){
			if($properties["BYDAY"] == "false"){
				$byday = array("MO"=>"1", "TU"=>"1", "WE"=>"1", "TH"=>"1", "FR"=>"1", "SA"=>"1", "SU"=>"1");
			}
			while(date("Y", mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year) + ($count * 1 * 86400 * $interval)) <= $select_year && $countchecker == true){
				if($byday[strtoupper(substr(date("D", mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year) + ($count * 1 * 86400 * $interval)), 0, 2))] == "1"){
					$newunixtime = mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year) + ($count * 1 * 86400 * $interval);
					$year  = date("Y", $newunixtime);
					$month = date("n", $newunixtime) - 1; // return is 0 based
					$day   = date("j", $newunixtime);
					if($properties["UNTIL"] != "false"){
						if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
							break;
						}
					}
					if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
						$hour = 'allday';
					}
					$return_event = array();
					foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
						$return_event[$prop] = $event[$prop];
					}
					$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
					$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
					$return_event['description'] = $event['summary'];
					$interval = $properties["INTERVAL"];
					$countedoutputs++;
					if($properties["COUNT"] != "false"){
						if($countedoutputs == $properties["COUNT"]){
							$countchecker = false;
						}
					}
					if ($hour == 'allday'){
						$return_event['allday'] = true;
					}
					if (isset($return[$year][$month][$day][$hour])){
						$return[$year][$month][$day][$hour][] = $return_event;
					}else{
						$return[$year][$month][$day][$hour] = array(1 => $return_event);
					}
				}
				$count++;
			}
		}
		if($properties["FREQ"] == "WEEKLY"){
			if($properties["BYDAY"] == "false"){
				$byday[strtoupper(substr(date("D", mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year)), 0, 2))] = "1";
			}
			while(date("Y", mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year) + ($count * 1 * 86400 * $interval)) <= $select_year && $countchecker == true){
				if($byday[strtoupper(substr(date("D", mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year) + ($count * 1 * 86400 * $interval)), 0, 2))] == "1"){
					$newunixtime = mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year) + ($count * 1 * 86400 * $interval);
					$year  = date("Y", $newunixtime);
					$month = date("n", $newunixtime) - 1; // return is 0 based
					$day   = date("j", $newunixtime);
					if($properties["UNTIL"] != "false"){
						if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
							break;
						}
					}
					if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
						$hour = 'allday';
					}
					$return_event = array();
					foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
						$return_event[$prop] = $event[$prop];
					}
					$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
					$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
					$return_event['description'] = $event['summary'];
					$interval = $properties["INTERVAL"];
					$countedoutputs++;
					if($properties["COUNT"] != "false"){
						if($countedoutputs == $properties["COUNT"]){
							$countchecker = false;
						}
					}
					if ($hour == 'allday'){
						$return_event['allday'] = true;
					}
					if (isset($return[$year][$month][$day][$hour])){
						$return[$year][$month][$day][$hour][] = $return_event;
					}else{
						$return[$year][$month][$day][$hour] = array(1 => $return_event);
					}
				}
				$count++;
			}
		}
		if($properties["FREQ"] == "MONTHLY"){
			if(substr_count($properties["BYMONTHDAY"], ",") != 0){
				$numofBYMONTHDAY = substr_count($properties["BYMONTHDAY"], ",");
				if($numofBYMONTHDAY == 0){
					$BYMONTHDAY = array();
					$BYMONTHDAY[0] = $properties["BYMONTHDAY"];
				}else{
					$BYMONTHDAY = explode(",", $properties["BYMONTHDAY"]);
				}
				while(date("Y", mktime(0,0,0, $firststart_month + ($count * $interval), $properties["BYMONTHDAY"], $firststart_year)) <= $select_year && $countchecker == true){
					for($i = 0;$i <= $numofBYMONTHDAY;$i++){
						$newunixtime = mktime(0,0,0, $firststart_month + ($count * $interval), $BYMONTHDAY[$i], $firststart_year);
						$year  = date("Y", $newunixtime);
						$month = date("n", $newunixtime) - 1; // return is 0 based
						$day   = date("j", $newunixtime);
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
					}
					$count++;
				}
			}
			//if($properties["BYMONTHDAY"] != "false"){
				if($properties["BYSETPOS"] == "false"){
					while(date("Y", mktime(0,0,0, $firststart_month + ($count * $interval), $properties["BYMONTHDAY"], $firststart_year)) <= $select_year && $countchecker == true){
						$newunixtime = mktime(0,0,0, $firststart_month + ($count * $interval), $properties["BYMONTHDAY"], $firststart_year);
						$year  = date("Y", $newunixtime);
						$month = date("n", $newunixtime) - 1; // return is 0 based
						$day   = date("j", $newunixtime);
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
						$count++;
					}
				}else{
					if(!is_nan($properties["BYSETPOS"]) && $properties["BYSETPOS"] >= 1){
						while(date("Y", mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year)) <= $select_year && $countchecker == true){
							$lastdayofmonth = date("t", mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year));
							$matches = 0;
							$matchedday = "";
							for($i = 1;$i <= $lastdayofmonth;$i++){
								$thisday = date("j", mktime(0,0,0, $firststart_month + ($count * $interval), $i, $firststart_year));
								$thisdayname = strtoupper(substr(date("D", mktime(0,0,0, $firststart_month + ($count * $interval), $i, $firststart_year)),0,2));
								//echo $thisdayname . " " . $thisday . "\n"; 
								if($byday[$thisdayname] == 1){
									$matches++;
								}
								if($matches == $properties["BYSETPOS"]){
									$matchedday = $thisday;
									$i = 32;
								}
							}
							$newunixtime = mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year);
							$year  = date("Y", $newunixtime);
							$month = date("n", $newunixtime) - 1; // return is 0 based
							$day   = $matchedday;
							if($properties["UNTIL"] != "false"){
								if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
									break;
								}
							}
							if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
								$hour = 'allday';
							}
							$return_event = array();
							foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
								$return_event[$prop] = $event[$prop];
							}
							$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
							$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
							$return_event['description'] = $event['summary'];
							$interval = $properties["INTERVAL"];
							$countedoutputs++;
							if($properties["COUNT"] != "false"){
								if($countedoutputs == $properties["COUNT"]){
									$countchecker = false;
								}
							}
							if ($hour == 'allday'){
								$return_event['allday'] = true;
							}
							if (isset($return[$year][$month][$day][$hour])){
								$return[$year][$month][$day][$hour][] = $return_event;
							}else{
								$return[$year][$month][$day][$hour] = array(1 => $return_event);
							}
							$count++;
						}
					}elseif(!is_nan($properties["BYSETPOS"]) && $properties["BYSETPOS"] <= -1){
						while(date("Y", mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year)) <= $select_year && $countchecker == true){
							$lastdayofmonth = date("t", mktime(0,0,0, $firststart_month + ($count * $interval), 1, $firststart_year));
							$matches = 0;
							$matchedday = "";
							for($i = $lastdayofmonth;$i >= 1;$i--){
								$thisday = date("j", mktime(0,0,0, $firststart_month + ($count * $interval), $i, $firststart_year));
								$thisdayname = strtoupper(substr(date("D", mktime(0,0,0, $firststart_month + ($count * $interval), $i, $firststart_year)),0,2));
								//echo $thisdayname . " " . $thisday . "\n"; 
								if($byday[$thisdayname] == 1){
									$matches++;
								}
								if($matches == $properties["BYSETPOS"]){
									$matchedday = $thisday;
									$i = 0;
								}
							}
							$newunixtime = mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year);
							$year  = date("Y", $newunixtime);
							$month = date("n", $newunixtime) - 1; // return is 0 based
							$day   = $matchedday;
							if($properties["UNTIL"] != "false"){
								if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
									break;
								}
							}
							if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
								$hour = 'allday';
							}
							$return_event = array();
							foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
								$return_event[$prop] = $event[$prop];
							}
							$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
							$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
							$return_event['description'] = $event['summary'];
							$interval = $properties["INTERVAL"];
							$countedoutputs++;
							if($properties["COUNT"] != "false"){
								if($countedoutputs == $properties["COUNT"]){
									$countchecker = false;
								}
							}
							if ($hour == 'allday'){
								$return_event['allday'] = true;
							}
							if (isset($return[$year][$month][$day][$hour])){
								$return[$year][$month][$day][$hour][] = $return_event;
							}else{
								$return[$year][$month][$day][$hour] = array(1 => $return_event);
							}
							$count++;
						}
					//}
				}
			}
			if(strlen($properties["BYDAY"]) == 2){
				while(date("Y", mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year)) <= $select_year && $countchecker == true){
					if($byday[strtoupper(substr(date("D", mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year)), 0, 2))] == "1"){
						$newunixtime = mktime(0,0,0, $firststart_month + ($count * $interval), $firststart_day, $firststart_year);
						$year  = date("Y", $newunixtime);
						$month = date("n", $newunixtime) - 1; // return is 0 based
						$day   = date("j", $newunixtime);
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
					}
					$count++;
				}
			}else{
				while(date("Y", mktime(0,0,0, 0, 0, $firststart_year + ($count * $interval))) <= $select_year && $countchecker == true){
					$newunixtime = mktime(0,0,0, $properties["BYMONTH"], $properties["BYMONTHDAY"], $firststart_year + ($count * $interval));
					$year  = date("Y", $newunixtime);
					$month = $month - 1; // return is 0 based
					$day   = $dateofweekone;
					if($properties["UNTIL"] != "false"){
						if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
							break;
						}
					}
					if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
						$hour = 'allday';
					}
					$return_event = array();
					foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
						$return_event[$prop] = $event[$prop];
					}
					$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
					$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
					$return_event['description'] = $event['summary'];
					$interval = $properties["INTERVAL"];
					$countedoutputs++;
					if($properties["COUNT"] != "false"){
						if($countedoutputs == $properties["COUNT"]){
							$countchecker = false;
						}
					}
					if ($hour == 'allday'){
						$return_event['allday'] = true;
					}
					if (isset($return[$year][$month][$day][$hour])){
						$return[$year][$month][$day][$hour][] = $return_event;
					}else{
						$return[$year][$month][$day][$hour] = array(1 => $return_event);
					}
					$count++;
				}
			}
		}
		if($properties["FREQ"] == "YEARLY"){
			if($properties["BYMONTH"] != "false"){
				if($properties["BYMONTHDAY"] == false){
					$properties["BYMONTHDAY"] = date("j", mktime(0,0,0, $firststart_month, $firststart_day, $firststart_year));
				}
				if($properties["BYDAY"] == "false"){
					while(date("Y", mktime(0,0,0, $properties["BYMONTH"], $properties["BYMONTHDAY"], $firststart_year + ($count * $interval))) <= $select_year && $countchecker == true){
						$newunixtime = mktime(0,0,0, $properties["BYMONTH"], $properties["BYMONTHDAY"], $firststart_year + ($count * $interval));
						$year  = date("Y", $newunixtime);
						$month = date("n", $newunixtime) - 1; // return is 0 based
						$day   = date("j", $newunixtime);
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
						$count++;
					}
				}
				if(strlen($properties["BYDAY"]) == 2){
					while(date("Y", mktime(0,0,0, $properties["BYMONTH"], $properties["BYMONTHDAY"], $firststart_year + ($count * $interval))) <= $select_year && $countchecker == true){
						$newunixtime = mktime(0,0,0, $properties["BYMONTH"], $properties["BYMONTHDAY"], $firststart_year + ($count * $interval));
						$year  = date("Y", $newunixtime);
						$month = date("n", $newunixtime) - 1; // return is 0 based
						$day   = date("j", $newunixtime);
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
						$count++;
					}
				}else{
					$number = substr($properties["BYDAY"],0,1);
					$weekday = substr($properties["BYDAY"],1,2);
					$month = $properties["BYMONTH"];
					$dateofweekone = "";
					for($i = 0; $i <= 7;$i++){
						if(strtoupper(substr(date("D", mktime(0,0,0, $properties["BYMONTH"], $i, $select_year)), 0, 2)) == $weekday){
							$dateofweekone = date("j", mktime(0,0,0, $properties["BYMONTH"], $i, $select_year));
							$i = 8;
						}
					}
					if($number != 1){
						$dateofweekone = $dateofweekone + (7 * ($number - 1));
					}
					while(date("Y", mktime(0,0,0, 0, 0, $firststart_year + ($count * $interval))) <= $select_year && $countchecker == true){
						$newunixtime = mktime(0,0,0, $properties["BYMONTH"], $properties["BYMONTHDAY"], $firststart_year + ($count * $interval));
						$year  = date("Y", $newunixtime);
						$month = $month - 1; // return is 0 based
						$day   = $dateofweekone;
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
						$count++;
					}
				}
			}elseif($properties["BYYEARDAY"] != false){
				$numofyeardays = substr_count($properties["BYYEARDAY"], ",");
				if($numofyeardays == 0){
					$yeardays = array();
					$yeardays[0] = $properties["BYYEARDAY"];
				}else{
					$yeardays = explode(",", $properties["BYYEARDAY"]);
				}
				while(date("Y", mktime(0,0,0, 0, 0, $firststart_year + ($count * $interval)) + ($yeardays[$numofyeardays]-1) * 86400) <= $select_year && $countchecker == true){
					for($i = 0;$i <= $numofyeardays;$i++){
						$newunixtime = mktime(0,0,0, 1, 1, $firststart_year + ($count * $interval)) + ($yeardays[$i] -1) * 86400;
						$year  = date("Y", $newunixtime);
						$month = date("n", $newunixtime) - 1; // return is 0 based
						$day   = date("j", $newunixtime);
						if($properties["UNTIL"] != "false"){
							if($year >= $until_year && $month + 1 >= $until_month && $day > $until_day){
								break;
							}
						}
						if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
							$hour = 'allday';
						}
						$return_event = array();
						foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop){
							$return_event[$prop] = $event[$prop];
						}
						$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
						$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
						$return_event['description'] = $event['summary'];
						$interval = $properties["INTERVAL"];
						$countedoutputs++;
						if($properties["COUNT"] != "false"){
							if($countedoutputs == $properties["COUNT"]){
								$countchecker = false;
							}
						}
						if ($hour == 'allday'){
							$return_event['allday'] = true;
						}
						if (isset($return[$year][$month][$day][$hour])){
							$return[$year][$month][$day][$hour][] = $return_event;
						}else{
							$return[$year][$month][$day][$hour] = array(1 => $return_event);
						}
					}
					$count++;
				}
			}
		}
	}
}
OC_JSON::encodedPrint($return);
