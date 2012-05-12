<?php
/*************************************************
 * ownCloud - Tasks Plugin                        *
 *                                                *
 * (c) Copyright 2011 Bart Visscher               *
 * This file is licensed under the Affero General *
 * Public License version 3 or later.             *
 * See the COPYING-README file.                   *
 *************************************************/

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
if( count($calendars) == 0 ) {
	header('Location: ' . OCP\Util::linkTo('calendar', 'index.php'));
	exit;
}

OCP\Util::addScript('3rdparty/timepicker', 'jquery.ui.timepicker');
OCP\Util::addStyle('3rdparty/timepicker', 'jquery.ui.timepicker');
OCP\Util::addScript('tasks', 'tasks');
OCP\Util::addStyle('tasks', 'style');
OCP\Util::addScript('contacts','jquery.multi-autocomplete');
OCP\Util::addScript('','oc-vcategories');
OCP\App::setActiveNavigationEntry('tasks_index');

$categories = OC_Calendar_App::getCategoryOptions();
$l10n = new OC_L10N('tasks');
$priority_options = OC_Task_App::getPriorityOptions();
$output = new OCP\Template('tasks', 'tasks', 'user');
$output->assign('priority_options', $priority_options);
$output->assign('categories', $categories);
$output -> printPage();
