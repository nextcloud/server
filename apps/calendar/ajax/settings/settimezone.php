<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
 

$l=OC_L10N::get('calendar');

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');

// Get data
if( isset( $_POST['timezone'] ) ){
	$timezone=$_POST['timezone'];
	OCP\Config::setUserValue( OCP\USER::getUser(), 'calendar', 'timezone', $timezone );
	OCP\JSON::success(array('data' => array( 'message' => $l->t('Timezone changed') )));
}else{
	OCP\JSON::error(array('data' => array( 'message' => $l->t('Invalid request') )));
}