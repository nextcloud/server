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
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}

$id = $_POST['id'];
$data = OC_Calendar_Object::find($id);
if (!$data)
{
	OC_JSON::error();
	exit;
}
$calendar = OC_Calendar_Calendar::findCalendar($data['calendarid']);
if($calendar['userid'] != OC_User::getUser()){
	OC_JSON::error();
	exit;
}
$result = OC_Calendar_Object::delete($id);
OC_JSON::success();
?> 
