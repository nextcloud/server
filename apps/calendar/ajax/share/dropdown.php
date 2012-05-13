<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
$user = OCP\USER::getUser();
$calid = $_GET['calid'];
$calendar = OC_Calendar_Calendar::find($calid);
if($calendar['userid'] != $user){
	OCP\JSON::error();
	exit;
}
$tmpl = new OCP\Template('calendar', 'share.dropdown');
$tmpl->assign('calid', $calid);
$tmpl->printPage();