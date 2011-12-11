<?php
class OC_Search_Provider_Calendar extends OC_Search_Provider{
	function search($query){
		$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
		if(count($calendars)==0 || !OC_App::isEnabled('calendar')){
			//return false;
		}
		$results=array();
		$searchquery=array();
		if(substr_count($query, ' ') > 0){
			$searchquery = explode(' ', $query);
		}else{
			$searchquery[] = $query;
		}
		foreach($calendars as $calendar){
			$objects = OC_Calendar_Object::all($calendar['id']);
			foreach($objects as $object){
				if(substr_count(strtolower($object['summary']), strtolower($query)) > 0){//$name,$text,$link,$type
					$results[]=new OC_Search_Result($object['summary'],'','#','Cal.');
				}
			}
		}
		return $results;
	}
}
new OC_Search_Provider_Calendar();