<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');
$currentview = $_GET['v'];
switch($currentview){
	case 'agendaWeek':
	case 'month';
	case 'list':
		break;
	default:
		OC_JSON::error();
		exit;
}
OC_Preferences::setValue(OC_USER::getUser(), 'calendar', 'currentview', $currentview);
OC_JSON::success();
?>