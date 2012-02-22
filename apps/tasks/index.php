<?php
/*************************************************
 * ownCloud - Tasks Plugin                        *
 *                                                *
 * (c) Copyright 2011 Bart Visscher               *
 * This file is licensed under the Affero General *
 * Public License version 3 or later.             *
 * See the COPYING-README file.                   *
 *************************************************/

require_once ('../../lib/base.php');
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
if( count($calendars) == 0 ) {
	header('Location: ' . OC_Helper::linkTo('calendar', 'index.php'));
	exit;
}

OC_UTIL::addScript('tasks', 'tasks');
OC_UTIL::addStyle('tasks', 'style');
OC_APP::setActiveNavigationEntry('tasks_index');

$l10n = new OC_L10N('tasks');
$priority_options = OC_Task_App::getPriorityOptions();
$output = new OC_Template('tasks', 'tasks', 'user');
$output->assign('priority_options', $priority_options);
$output -> printPage();
