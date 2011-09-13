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
$categories = array();
if (isset($details->CATEGORIES)){
	$categories = explode(',', $details->CATEGORIES->value);
	$categories = array_map('trim', $categories);
}

$category_options = OC_Calendar_Object::getCategoryOptions($l10n);
$percent_options = range(0, 100, 10);
$priority_options = OC_Task_VTodo::getPriorityOptions($l10n);

$tmpl = new OC_Template('tasks','part.edittaskform');
$tmpl->assign('category_options', $category_options);
$tmpl->assign('percent_options', $percent_options);
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('task',$task);
$tmpl->assign('details',$details);
$tmpl->assign('categories', $categories);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'page' => $page )));
