<?php

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tasks');
OCP\JSON::callCheck();

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
$category_options = OC_Calendar_App::getCategoryOptions();
$percent_options = range(0, 100, 10);
$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OCP\Template('tasks','part.addtaskform');
$tmpl->assign('calendars',$calendars);
$tmpl->assign('category_options', $category_options);
$tmpl->assign('percent_options', $percent_options);
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details', new OC_VObject('VTODO'));
$tmpl->assign('categories', '');
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'page' => $page )));
