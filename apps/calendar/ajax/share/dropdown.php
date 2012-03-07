<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../../lib/base.php');
$user = OC_USER::getUser();
$calid = $_GET['calid'];
$calendar = OC_Calendar_Calendar::find($calid);
if($calendar['userid'] != $user){
	OC_JSON::error();
	exit;
}
$tmpl = new OC_Template('calendar', 'share.dropdown');
$tmpl->assign('calid', $calid);
$tmpl->printPage();