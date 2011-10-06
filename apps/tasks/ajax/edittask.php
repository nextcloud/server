<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$id = $_POST['id'];
$task = OC_Calendar_Object::find( $id );
if( $task === false ){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('Can not find Task!'))));
	exit();
}

$calendar = OC_Calendar_Calendar::findCalendar( $task['calendarid'] );
if( $calendar === false || $calendar['userid'] != OC_USER::getUser()){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('This is not your task!'))));
	exit();
}

$errors = OC_Task_VTodo::validateRequest($_POST, $l10n);
if (!empty($errors)) {
	OC_JSON::error(array('data' => array( 'errors' => $errors )));
	exit();
}

$vcalendar = Sabre_VObject_Reader::read($task['calendardata']);
OC_Task_VTodo::updateVCalendarFromRequest($_POST, $vcalendar);
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$priority_options = OC_Task_VTodo::getPriorityOptions($l10n);
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details', $vcalendar->VTODO);
$tmpl->assign('id', $id);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'id' => $id, 'page' => $page )));
