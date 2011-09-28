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

$calendar_options = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
$category_options = OC_Calendar_Object::getCategoryOptions($l10n);
$repeat_options = OC_Calendar_Object::getRepeatOptions($l10n);
$startday   = substr($_GET['d'], 0, 2);
$startmonth = substr($_GET['d'], 2, 2);
$startyear  = substr($_GET['d'], 4, 4);
$starttime  = $_GET['t'];
$allday = $starttime == 'allday';
if($starttime != 'undefined' && !is_nan($starttime) && !$allday){
	$startminutes = '00';
}elseif($allday){
	$starttime = '0';
	$startminutes = '00';
}else{
	$starttime = date('H');
	if(strlen($starttime) == 2 && $starttime <= 9){
		$starttime = substr($starttime, 1, 1);
	}
	$startminutes = date('i');
}

$endday      = $startday;
$endmonth    = $startmonth;
$endyear     = $startyear;
$endtime     = $starttime;
$endminutes  = $startminutes;
if($endtime == 23) {
	if($startday == date(t, mktime($starttime, $startminutes, 0, $startmonth, $startday, $startyear))){
		$datetimestamp = mktime(0, 0, 0, $startmonth, $startday, $startyear);
		$datetimestamp = $datetimestamp + 86400;
		$endmonth = date("m", $datetimestamp);
		$endday = date("d", $datetimestamp);
		$endyear = date("Y", $datetimestamp);
	}else{
		$endday++;
		if($endday <= 9){
			$endday = "0" . $endday;
		}
	}
	$endtime = 0;
} else {
	$endtime++;
}

$tmpl = new OC_Template('calendar', 'part.newevent');
$tmpl->assign('calendar_options', $calendar_options);
$tmpl->assign('category_options', $category_options);
$tmpl->assign('startdate', $startday . '-' . $startmonth . '-' . $startyear);
$tmpl->assign('starttime', ($starttime <= 9 ? '0' : '') . $starttime . ':' . $startminutes);
$tmpl->assign('enddate', $endday . '-' . $endmonth . '-' . $endyear);
$tmpl->assign('endtime', ($endtime <= 9 ? '0' : '') . $endtime . ':' . $endminutes);
$tmpl->assign('allday', $allday);
$tmpl->printpage();
?>
