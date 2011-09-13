<?php

// Init owncloud
require_once('../../../lib/base.php');

$l10n = new OC_L10N('tasks');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in!'))));
	exit();
}

$id = $_POST['id'];
$task = OC_Calendar_Object::find( $id );
if( $task === false ){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('Can not find Task!'))));
	exit();
}

$calendar = OC_Calendar_Calendar::findCalendar( $task['calendarid'] );
if( $calendar === false || $calendar['userid'] != OC_USER::getUser()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('This is not your task!'))));
	exit();
}

$errors = OC_Task_VTodo::validateRequest($_POST, $l10n);
if (!empty($errors)) {
	echo json_encode( array( 'status' => 'error', 'data' => array( 'errors' => $errors )));
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

echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id, 'page' => $page )));
