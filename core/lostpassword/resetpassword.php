<?php
/**
 * Copyright (c) 2010 Frank Karlitschek karlitschek@kde.org
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
*/

$RUNTIME_NOAPPS = TRUE; //no apps
require_once('../../lib/base.php');

// Someone wants to reset their password:
if(isset($_GET['token']) && isset($_GET['user']) && OC_Preferences::getValue($_GET['user'], 'owncloud', 'lostpassword') === $_GET['token']) {
	if (isset($_POST['password'])) {
		if (OC_User::setPassword($_GET['user'], $_POST['password'])) {
			OC_Preferences::deleteKey($_GET['user'], 'owncloud', 'lostpassword');
			OC_Template::printGuestPage('core/lostpassword', 'resetpassword', array('success' => true));
		} else {
			OC_Template::printGuestPage('core/lostpassword', 'resetpassword', array('success' => false));
		}
	} else {
		OC_Template::printGuestPage('core/lostpassword', 'resetpassword', array('success' => false));
	}
} else {
	// Someone lost their password
	OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => false, 'requested' => false));
}
