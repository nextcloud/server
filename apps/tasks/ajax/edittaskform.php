<?php

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tasks');

$id = $_GET['id'];
$details = OC_Calendar_App::getVCalendar($id)->VTODO;
$categories = $details->getAsString('CATEGORIES');

$category_options = OC_Calendar_App::getCategoryOptions();
$percent_options = range(0, 100, 10);
$priority_options = OC_Task_App::getPriorityOptions();

$tmpl = new OCP\Template('tasks','part.edittaskform');
$tmpl->assign('category_options', $category_options);
$tmpl->assign('percent_options', $percent_options);
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('id',$id);
$tmpl->assign('details',$details);
$tmpl->assign('categories', $categories);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'page' => $page )));
