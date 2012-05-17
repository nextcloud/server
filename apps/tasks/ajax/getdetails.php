<?php

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$id = $_GET['id'];
$task = OC_Calendar_Object::find($id);
$details = OC_VObject::parse($task['calendardata']);
if (!$details){
	OCP\JSON::error();
	exit;
}

$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OCP\Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details',$details->VTODO);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'id' => $id, 'page' => $page )));
