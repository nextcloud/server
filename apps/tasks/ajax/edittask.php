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

$summary = $_POST['summary'];

$vtodo = Sabre_VObject_Reader::read($task['calendardata'])->VTODO[0];
$uid = $vtodo->UID[0]->value;

$vcalendar = new Sabre_VObject_Component('VCALENDAR');
$vcalendar->add(new Sabre_VObject_Property('PRODID', 'ownCloud Calendar'));
$vcalendar->add(new Sabre_VObject_Property('VERSION', '2.0'));
$vtodo = new Sabre_VObject_Component('VTODO');
$vtodo->add(new Sabre_VObject_Property('SUMMARY',$summary));
$vtodo->add(new Sabre_VObject_Property('UID', $uid));
$vcalendar->add($vtodo);
OC_Calendar_Object::edit($id, $vcalendar->serialize());

$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('details',$vtodo);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id, 'page' => $page )));
