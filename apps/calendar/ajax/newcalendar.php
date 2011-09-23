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
$calendar = array(
	'id' => 'new',
	'displayname' => 'Test',
	'description' => 'Test calendar',
	'calendarcolor' => 'black',
);
$tmpl = new OC_Template('calendar', 'part.editcalendar');
$tmpl->assign('new', true);
$tmpl->assign('calendar', $calendar);
$tmpl->printPage();
?>
