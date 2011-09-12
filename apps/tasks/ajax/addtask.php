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

$summary = $_POST['summary'];

$vcalendar = new Sabre_VObject_Component('VCALENDAR');
$vcalendar->add(new Sabre_VObject_Property('PRODID', 'ownCloud Calendar'));
$vcalendar->add(new Sabre_VObject_Property('VERSION', '2.0'));
$vtodo = new Sabre_VObject_Component('VTODO');
$vtodo->add(new Sabre_VObject_Property('SUMMARY',$summary));
$vtodo->add(new Sabre_VObject_Property('UID',OC_Calendar_Calendar::createUID()));
$vcalendar->add($vtodo);
$id = OC_Calendar_Object::add($cid, $vcalendar->serialize());

$details = OC_Contacts_Addressbook::structureContact($vtodo);
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('details',$details);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id, 'page' => $page )));
