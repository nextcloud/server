<?php
/**
 * Copyright (c) 2010 Frank Karlitschek karlitschek@kde.org
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
*/

$RUNTIME_NOAPPS = TRUE; //no apps
require_once('../lib/base.php');

// Someone lost their password:
if (isset($_POST['user'])) {
	if (OC_User::userExists($_POST['user'])) {
		$token = sha1($_POST['user']+uniqId());
		OC_Preferences::setValue($_POST['user'], 'owncloud', 'lostpassword', $token);
		// TODO send email with link+token
		$link = OC_Helper::linkTo('lostpassword', 'resetpassword.php', null, true).'?user='.$_POST['user'].'&token='.$token;
		OC_Template::printGuestPage('lostpassword', 'lostpassword', array('error' => false, 'requested' => true));
	} else {
		OC_Template::printGuestPage('lostpassword', 'lostpassword', array('error' => true, 'requested' => false));
	}
} else {
	OC_Template::printGuestPage('lostpassword', 'lostpassword', array('error' => false, 'requested' => false));
}
