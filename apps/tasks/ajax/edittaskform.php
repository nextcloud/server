<?php

// Init owncloud
require_once('../../../lib/base.php');

$l10n = new OC_L10N('tasks');

// Check if we are a user
if( !OC_User::isLoggedIn()){
        echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in!'))));
        exit();
}

$id = $_GET['id'];
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

$details = Sabre_VObject_Reader::read($task['calendardata'])->VTODO;
$tmpl = new OC_Template('tasks','part.edittaskform');
$tmpl->assign('task',$task);
$tmpl->assign('details',$details);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'page' => $page )));
