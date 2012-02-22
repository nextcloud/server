<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$id = $_GET['id'];
$details = OC_Calendar_App::getVCalendar($id)->VTODO;
$categories = array();
if (isset($details->CATEGORIES)){
	$categories = explode(',', $details->CATEGORIES->value);
	$categories = array_map('trim', $categories);
}

$category_options = OC_Calendar_Object::getCategoryOptions($l10n);
$percent_options = range(0, 100, 10);
$priority_options = OC_Task_App::getPriorityOptions();

$tmpl = new OC_Template('tasks','part.edittaskform');
$tmpl->assign('category_options', $category_options);
$tmpl->assign('percent_options', $percent_options);
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('id',$id);
$tmpl->assign('details',$details);
$tmpl->assign('categories', $categories);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));
