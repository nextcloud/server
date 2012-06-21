<?php
class OC_Search_Provider_Calendar extends OC_Search_Provider{
	function search($query){
		$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser(), 1);
		if(count($calendars)==0 || !OCP\App::isEnabled('calendar')){
			//return false;
		}
		$results=array();
		$searchquery=array();
		if(substr_count($query, ' ') > 0){
			$searchquery = explode(' ', $query);
		}else{
			$searchquery[] = $query;
		}
		$user_timezone = OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
		$l = new OC_l10n('calendar');
		foreach($calendars as $calendar){
			$objects = OC_Calendar_Object::all($calendar['id']);
			foreach($objects as $object){
				if($object['objecttype']!='VEVENT') {
					continue;
				}
				if(substr_count(strtolower($object['summary']), strtolower($query)) > 0){
					$calendardata = OC_VObject::parse($object['calendardata']);
					$vevent = $calendardata->VEVENT;
					$dtstart = $vevent->DTSTART;
					$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
					$start_dt = $dtstart->getDateTime();
					$start_dt->setTimezone(new DateTimeZone($user_timezone));
					$end_dt = $dtend->getDateTime();
					$end_dt->setTimezone(new DateTimeZone($user_timezone));
					if ($dtstart->getDateType() == Sabre_VObject_Property_DateTime::DATE){
						$end_dt->modify('-1 sec');
						if($start_dt->format('d.m.Y') != $end_dt->format('d.m.Y')){
							$info = $l->t('Date') . ': ' . $start_dt->format('d.m.Y') . ' - ' . $end_dt->format('d.m.Y');
						}else{
							$info = $l->t('Date') . ': ' . $start_dt->format('d.m.Y');
						}
					}else{
						$info = $l->t('Date') . ': ' . $start_dt->format('d.m.y H:i') . ' - ' . $end_dt->format('d.m.y H:i');
					}
					$link = OCP\Util::linkTo('calendar', 'index.php').'?showevent='.urlencode($object['id']);
					$results[]=new OC_Search_Result($object['summary'],$info, $link,(string)$l->t('Cal.'));//$name,$text,$link,$type
				}
			}
		}
		return $results;
	}
}
