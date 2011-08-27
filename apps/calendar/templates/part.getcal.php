<?php
$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
$events = OC_Calendar_Calendar::allCalendarObjects($calendars[0]['id']);
$select_year = $_GET["year"];
$return_events = array();
foreach($events as $event)
{
	if ($select_year != substr($event['startdate'], 0, 4))
		continue;
	list($date, $time) = explode(' ', $event['startdate']);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $min) = explode(':', $time);
	$hour = (int)$hour;

	// hack
	if (strstr($event['calendardata'], 'DTSTART;VALUE=DATE:')) {
		$hour = 'allday';
	}
	$return_event = array();
	foreach(array('id', 'calendarid', 'objecttype', 'startdate', 'enddate', 'repeating') as $prop)
	{
		$return_event[$prop] = $event[$prop];
	}
	$return_event['description'] = $event['summary'];
	$month--; // return is 0 based
	if (isset($return_events[$year][$month][$day][$hour]))
	{
		$return_events[$year][$month][$day][$hour][] = $return_event;
	}
	else
	{
		$return_events[$year][$month][$day][$hour] = array(1 => $return_event);
	}
}
$return_events[2011][7][7]['allday'][1]['description'] = 'allday event';
$return_events[2011][7][13][10][1]['description'] = '10:00 event';
echo json_encode($return_events);
?>
