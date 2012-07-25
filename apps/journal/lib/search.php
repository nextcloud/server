<?php
class OC_Search_Provider_Journal extends OC_Search_Provider {
	function search($query){
		$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser(), true);
		if(count($calendars)==0 || !OCP\App::isEnabled('calendar')) {
			//return false;
		}
		$results=array();
		$searchquery=array();
		if(substr_count($query, ' ') > 0) {
			$searchquery = explode(' ', $query);
		}else{
			$searchquery[] = $query;
		}
		error_log('search');
		$user_timezone = OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
		$l = new OC_l10n('journal');
		foreach($calendars as $calendar) {
			$objects = OC_Calendar_Object::all($calendar['id']);
			foreach($objects as $object) {
				if($object['objecttype']!='VJOURNAL') {
					continue;
				}
				if(substr_count(strtolower($object['summary']), strtolower($query)) > 0) {
					$calendardata = OC_VObject::parse($object['calendardata']);
					$vjournal = $calendardata->VJOURNAL;
					$dtstart = $vjournal->DTSTART;
					if($dtstart) {
						continue;
					}
					$start_dt = $dtstart->getDateTime();
					$start_dt->setTimezone(new DateTimeZone($user_timezone));
					if ($dtstart->getDateType() == Sabre_VObject_Property_DateTime::DATE) {
						$info = $l->t('Date') . ': ' . $start_dt->format('d.m.Y');
					}else{
						$info = $l->t('Date') . ': ' . $start_dt->format('d.m.y H:i');
					}
					$link = OCP\Util::linkTo('journal', 'index.php').'&id='.urlencode($object['id']);
					$results[]=new OC_Search_Result($object['summary'],$info, $link,(string)$l->t('Journal'));//$name,$text,$link,$type
				}
			}
		}
		return $results;
	}
}
