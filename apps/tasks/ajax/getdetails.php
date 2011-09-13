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
$task = OC_Calendar_Object::find($id);
$details = Sabre_VObject_Reader::read($task['calendardata'])->VTODO;

$priority_options = OC_Task_VTodo::getPriorityOptions($l10n);
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details',$details);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id, 'page' => $page )));
