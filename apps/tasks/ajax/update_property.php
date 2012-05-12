<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tasks');

$id = $_POST['id'];
$property = $_POST['type'];
$vcalendar = OC_Calendar_App::getVCalendar( $id );

$vtodo = $vcalendar->VTODO;
switch($property) {
	case 'summary':
		$summary = $_POST['summary'];
		$vtodo->setString('SUMMARY', $summary);
		break;
	case 'description':
		$description = $_POST['description'];
		$vtodo->setString('DESCRIPTION', $description);
		break;
	case 'location':
		$location = $_POST['location'];
		$vtodo->setString('LOCATION', $location);
		break;
	case 'categories':
		$categories = $_POST['categories'];
		$vtodo->setString('CATEGORIES', $categories);
		break;
	case 'due':
		$due = $_POST['due'];
		$due_date_only = $_POST['date'];
		$type = null;
		if ($due != 'false') {
			try {
				$timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
				$timezone = new DateTimeZone($timezone);
				$due = new DateTime('@'.$due);
				$due->setTimezone($timezone);
				$type = Sabre_VObject_Element_DateTime::LOCALTZ;
				if ($due_date_only) {
					$type = Sabre_VObject_Element_DateTime::DATE;
				}
			} catch (Exception $e) {
				OCP\JSON::error(array('data'=>array('message'=>OC_Task_App::$l10n->t('Invalid date/time'))));
				exit();
			}
		}
		$vtodo->setDateTime('DUE', $due, $type);
		break;
	case 'complete':
		$checked = $_POST['checked'];
		OC_Task_App::setComplete($vtodo, $checked ? '100' : '0', null);
		break;
	default:
		OCP\JSON::error(array('data'=>array('message'=>'Unknown type')));
		exit();
}
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$user_timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task_info = OC_Task_App::arrayForJSON($id, $vtodo, $user_timezone);
OCP\JSON::success(array('data' => $task_info));
