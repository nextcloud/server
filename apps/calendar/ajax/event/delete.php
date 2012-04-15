<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../../lib/base.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

$id = $_POST['id'];
$access = OC_Calendar_App::getaccess($id, OC_Calendar_App::EVENT);
if($access != 'owner' && $access != 'rw'){
	OC_JSON::error(array('message'=>'permission denied'));
	exit;
}
$result = OC_Calendar_Object::delete($id);
OC_JSON::success();
