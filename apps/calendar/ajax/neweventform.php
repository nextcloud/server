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

$calendar_options = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
$category_options = OC_Calendar_Object::getCategoryOptions($l10n);
$repeat_options = OC_Calendar_Object::getRepeatOptions($l10n);
$startday   = substr($_GET['d'], 0, 2);
$startmonth = substr($_GET['d'], 2, 2);
$startyear  = substr($_GET['d'], 4, 4);
$allday = $_GET['t'] == 'allday';
if(!$allday){
	$starthour    = substr($_GET['t'], 0, 2);
	$startminutes = substr($_GET['t'], 2, 2);
}else{
	$starthour = '00';
	$startminutes = '00';
}

$datetimestamp = mktime($starthour, $startminutes, 0, $startmonth, $startday, $startyear);
$duration = OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'duration', "60");
$datetimestamp = $datetimestamp + ($duration * 60);
$endmonth = date("m", $datetimestamp);
$endday = date("d", $datetimestamp);
$endyear = date("Y", $datetimestamp);
$endtime = date("G", $datetimestamp);
$endminutes = date("i", $datetimestamp);



$tmpl = new OC_Template('calendar', 'part.newevent');
$tmpl->assign('calendar_options', $calendar_options);
$tmpl->assign('category_options', $category_options);
$tmpl->assign('startdate', $startday . '-' . $startmonth . '-' . $startyear);
$tmpl->assign('starttime', $starthour . ':' . $startminutes);
$tmpl->assign('enddate', $endday . '-' . $endmonth . '-' . $endyear);
$tmpl->assign('endtime', ($endtime <= 9 ? '0' : '') . $endtime . ':' . $endminutes);
$tmpl->assign('allday', $allday);
$tmpl->printpage();
?>
