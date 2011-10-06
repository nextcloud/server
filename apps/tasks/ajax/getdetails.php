<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$id = $_GET['id'];
$task = OC_Calendar_Object::find($id);
$details = Sabre_VObject_Reader::read($task['calendardata'])->VTODO;

$priority_options = OC_Task_VTodo::getPriorityOptions($l10n);
$tmpl = new OC_Template('tasks','part.details');
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details',$details);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'id' => $id, 'page' => $page )));
