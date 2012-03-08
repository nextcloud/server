<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
$category_options = OC_Calendar_Object::getCategoryOptions($l10n);
$percent_options = range(0, 100, 10);
$priority_options = OC_Task_App::getPriorityOptions();
$tmpl = new OC_Template('tasks','part.addtaskform');
$tmpl->assign('calendars',$calendars);
$tmpl->assign('category_options', $category_options);
$tmpl->assign('percent_options', $percent_options);
$tmpl->assign('priority_options', $priority_options);
$tmpl->assign('details', new OC_VObject('VTODO'));
$tmpl->assign('categories', array());
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));
