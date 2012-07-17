<?php

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tasks');
OCP\JSON::callCheck();

$l10n = new OC_L10N('tasks');

$id = $_POST['id'];
$vcalendar = OC_Calendar_App::getVCalendar($id);

$errors = OC_Task_App::validateRequest($_POST);
if (!empty($errors)) {
	OCP\JSON::error(array('data' => array( 'errors' => $errors )));
	exit();
}

OC_Task_App::updateVCalendarFromRequest($_POST, $vcalendar);
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OCP\Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details', $vcalendar->VTODO);
$tmpl->assign('id', $id);
$page = $tmpl->fetchPage();

$user_timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task = OC_Task_App::arrayForJSON($id, $vcalendar->VTODO, $user_timezone);

OCP\JSON::success(array('data' => array( 'id' => $id, 'page' => $page, 'task' => $task )));
