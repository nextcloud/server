<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
$l10n = new OC_L10N('calendar');
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
$calendarcolor_options = array(
	'ff0000', // "Red"
	'00ff00', // "Green"
	'ffff00', // "Yellow"
	'808000', // "Olive"
	'ffa500', // "Orange"
	'ff7f50', // "Coral"
	'ee82ee', // "Violet"
	'ecc255', // dark yellow
);
OC_JSON::checkAppEnabled('calendar');
$calendar = OC_Calendar_Calendar::findCalendar($_GET['calendarid']);
$tmpl = new OC_Template("calendar", "part.editcalendar");
$tmpl->assign('new', false);
$tmpl->assign('calendarcolor_options', $calendarcolor_options);
$tmpl->assign('calendar', $calendar);
$tmpl->printPage();
?>
