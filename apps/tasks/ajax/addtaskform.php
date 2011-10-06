<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$l10n = new OC_L10N('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
$tmpl = new OC_Template('tasks','part.addtaskform');
$tmpl->assign('calendars',$calendars);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));
