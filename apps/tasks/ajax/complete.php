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
$checked = $_POST['checked'];
$vcalendar = OC_Calendar_App::getVCalendar( $id );

$vtodo = $vcalendar->VTODO;
OC_Task_App::setComplete($vtodo, $checked ? '100' : '0', null);
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task_info = OC_Task_App::arrayForJSON($id, $vtodo, $user_timezone);
OC_JSON::success(array('data' => $task_info));
