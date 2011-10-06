<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$cid = $_POST['id'];
$calendar = OC_Calendar_Calendar::findCalendar( $cid );
if( $calendar === false || $calendar['userid'] != OC_USER::getUser()){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('This is not your calendar!'))));
	exit();
}

$errors = OC_Task_VTodo::validateRequest($_POST, $l10n);
if (!empty($errors)) {
	OC_JSON::error(array('data' => array( 'errors' => $errors )));
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

OC_JSON::success(array('data' => array( 'id' => $id, 'page' => $page )));
