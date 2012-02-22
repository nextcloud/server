<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$id = $_POST['id'];
$vcalendar = OC_Calendar_App::getVCalendar($id);

$errors = OC_Task_App::validateRequest($_POST);
if (!empty($errors)) {
	OC_JSON::error(array('data' => array( 'errors' => $errors )));
	exit();
}

OC_Task_App::updateVCalendarFromRequest($_POST, $vcalendar);
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details', $vcalendar->VTODO);
$tmpl->assign('id', $id);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'id' => $id, 'page' => $page )));
