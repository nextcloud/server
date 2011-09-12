<?php

// Init owncloud
require_once('../../../lib/base.php');

$l10n = new OC_L10N('tasks');

// Check if we are a user
if( !OC_User::isLoggedIn()){
        echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in!'))));
        exit();
}

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
$tmpl = new OC_Template('tasks','part.addtaskform');
$tmpl->assign('calendars',$calendars);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'page' => $page )));
