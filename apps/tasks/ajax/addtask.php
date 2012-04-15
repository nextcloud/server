<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$cid = $_POST['id'];
$calendar = OC_Calendar_App::getCalendar( $cid );

$errors = OC_Task_App::validateRequest($_POST);
if (!empty($errors)) {
	OC_JSON::error(array('data' => array( 'errors' => $errors )));
	exit();
}

$vcalendar = OC_Task_App::createVCalendarFromRequest($_POST);
$id = OC_Calendar_Object::add($cid, $vcalendar->serialize());

$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details',$vcalendar->VTODO);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task = OC_Task_App::arrayForJSON($id, $vcalendar->VTODO, $user_timezone);

OC_JSON::success(array('data' => array( 'id' => $id, 'page' => $page, 'task' => $task )));
