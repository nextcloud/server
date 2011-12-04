<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

if(!OC_USER::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}
OC_JSON::checkAppEnabled('calendar');

if (!isset($_POST['start'])){
	OC_JSON::error();
	die;
}
$start = $_POST['start'];
$end = $_POST['end'];
$allday = $_POST['allday'];

if (!$end){
	$duration = OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'duration', '60');
	$end = $start + ($duration * 60);
}
$start = new DateTime('@'.$start);
$end = new DateTime('@'.$end);
$timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$start->setTimezone(new DateTimeZone($timezone));
$end->setTimezone(new DateTimeZone($timezone));

$calendar_options = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
$category_options = OC_Calendar_Object::getCategoryOptions($l10n);
$repeat_options = OC_Calendar_Object::getRepeatOptions($l10n);

$tmpl = new OC_Template('calendar', 'part.newevent');
$tmpl->assign('calendar_options', $calendar_options);
$tmpl->assign('category_options', $category_options);
$tmpl->assign('startdate', $start->format('d-m-Y'));
$tmpl->assign('starttime', $start->format('H:i'));
$tmpl->assign('enddate', $end->format('d-m-Y'));
$tmpl->assign('endtime', $end->format('H:i'));
$tmpl->assign('allday', $allday);
$tmpl->printpage();
?>
