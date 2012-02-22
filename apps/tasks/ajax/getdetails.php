<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$id = $_GET['id'];
$task = OC_Calendar_Object::find($id);
$details = OC_VObject::parse($task['calendardata']);
if (!$details){
	OC_JSON::error();
	exit;
}

$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details',$details->VTODO);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'id' => $id, 'page' => $page )));
