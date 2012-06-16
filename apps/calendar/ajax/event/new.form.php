<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 

if(!OCP\User::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}
OCP\JSON::checkAppEnabled('calendar');

if (!isset($_POST['start'])){
	OCP\JSON::error();
	die;
}
$start = $_POST['start'];
$end = $_POST['end'];
$allday = $_POST['allday'];

if (!$end){
	$duration = OCP\Config::getUserValue( OCP\USER::getUser(), 'calendar', 'duration', '60');
	$end = $start + ($duration * 60);
}
$start = new DateTime('@'.$start);
$end = new DateTime('@'.$end);
$timezone = OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$start->setTimezone(new DateTimeZone($timezone));
$end->setTimezone(new DateTimeZone($timezone));

$calendar_options = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
$repeat_options = OC_Calendar_App::getRepeatOptions();
$repeat_end_options = OC_Calendar_App::getEndOptions();
$repeat_month_options = OC_Calendar_App::getMonthOptions();
$repeat_year_options = OC_Calendar_App::getYearOptions();
$repeat_weekly_options = OC_Calendar_App::getWeeklyOptions();
$repeat_weekofmonth_options = OC_Calendar_App::getWeekofMonth();
$repeat_byyearday_options = OC_Calendar_App::getByYearDayOptions();
$repeat_bymonth_options = OC_Calendar_App::getByMonthOptions();
$repeat_byweekno_options = OC_Calendar_App::getByWeekNoOptions();
$repeat_bymonthday_options = OC_Calendar_App::getByMonthDayOptions();

$tmpl = new OCP\Template('calendar', 'part.newevent');
$tmpl->assign('access', 'owner');
$tmpl->assign('calendar_options', $calendar_options);
$tmpl->assign('repeat_options', $repeat_options);
$tmpl->assign('repeat_month_options', $repeat_month_options);
$tmpl->assign('repeat_weekly_options', $repeat_weekly_options);
$tmpl->assign('repeat_end_options', $repeat_end_options);
$tmpl->assign('repeat_year_options', $repeat_year_options);
$tmpl->assign('repeat_byyearday_options', $repeat_byyearday_options);
$tmpl->assign('repeat_bymonth_options', $repeat_bymonth_options);
$tmpl->assign('repeat_byweekno_options', $repeat_byweekno_options);
$tmpl->assign('repeat_bymonthday_options', $repeat_bymonthday_options);
$tmpl->assign('repeat_weekofmonth_options', $repeat_weekofmonth_options);

$tmpl->assign('eventid', 'new');
$tmpl->assign('startdate', $start->format('d-m-Y'));
$tmpl->assign('starttime', $start->format('H:i'));
$tmpl->assign('enddate', $end->format('d-m-Y'));
$tmpl->assign('endtime', $end->format('H:i'));
$tmpl->assign('allday', $allday);
$tmpl->assign('repeat', 'doesnotrepeat');
$tmpl->assign('repeat_month', 'monthday');
$tmpl->assign('repeat_weekdays', array());
$tmpl->assign('repeat_interval', 1);
$tmpl->assign('repeat_end', 'never');
$tmpl->assign('repeat_count', '10');
$tmpl->assign('repeat_weekofmonth', 'auto');
$tmpl->assign('repeat_date', '');
$tmpl->assign('repeat_year', 'bydate');
$tmpl->printpage();