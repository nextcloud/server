<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$id = $_POST['id'];
$property = $_POST['type'];
$vcalendar = OC_Calendar_App::getVCalendar( $id );

$vtodo = $vcalendar->VTODO;
switch($property) {
	case 'summary':
		$summary = $_POST['summary'];
		$vtodo->setString('SUMMARY', $summary);
		break;
	case 'complete':
		$checked = $_POST['checked'];
		OC_Task_App::setComplete($vtodo, $checked ? '100' : '0', null);
		break;
	default:
		OC_JSON::error(array('data'=>array('message'=>'Unknown type')));
		exit();
}
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task_info = OC_Task_App::arrayForJSON($id, $vtodo, $user_timezone);
OC_JSON::success(array('data' => $task_info));
