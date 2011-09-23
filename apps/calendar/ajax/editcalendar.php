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
$calendar = OC_Calendar_Calendar::findCalendar($_GET['calendarid']);
$tmpl = new OC_Template("calendar", "part.editcalendar");
$tmpl->assign('new', false);
$tmpl->assign('calendar', $calendar);
$tmpl->printPage();
?>
