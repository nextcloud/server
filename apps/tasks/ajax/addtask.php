<?php

// Init owncloud
require_once('../../../lib/base.php');

$l10n = new OC_L10N('tasks');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in!'))));
	exit();
}

$cid = $_POST['id'];
$calendar = OC_Calendar_Calendar::findCalendar( $cid );
if( $calendar === false || $calendar['userid'] != OC_USER::getUser()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('This is not your calendar!'))));
	exit();
}

$errors = OC_Task_VTodo::validateRequest($_POST, $l10n);
if (!empty($errors)) {
	echo json_encode( array( 'status' => 'error', 'data' => array( 'errors' => $errors )));
	exit();
}

$vcalendar = OC_Task_VTodo::createVCalendarFromRequest($_POST);
$id = OC_Calendar_Object::add($cid, $vcalendar->serialize());

$priority_options = OC_Task_VTodo::getPriorityOptions($l10n);
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details',$vcalendar->VTODO);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id, 'page' => $page )));
