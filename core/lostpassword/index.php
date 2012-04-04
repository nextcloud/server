<?php
/**
 * Copyright (c) 2010 Frank Karlitschek karlitschek@kde.org
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
*/

$RUNTIME_NOAPPS = TRUE; //no apps
require_once('../../lib/base.php');

// Someone lost their password:
if (isset($_POST['user'])) {
	if (OC_User::userExists($_POST['user'])) {
		$token = sha1($_POST['user'].md5(uniqid(rand(), true)));
		OC_Preferences::setValue($_POST['user'], 'owncloud', 'lostpassword', $token);
		$email = OC_Preferences::getValue($_POST['user'], 'settings', 'email', '');
		if (!empty($email)) {
			$link = OC_Helper::linkTo('core/lostpassword', 'resetpassword.php', null, true).'?user='.$_POST['user'].'&token='.$token;
			$tmpl = new OC_Template('core/lostpassword', 'email');
			$tmpl->assign('link', $link);
			$msg = $tmpl->fetchPage();
			$l = new OC_L10N('core');
			mail($email, $l->t('Owncloud password reset'), $msg);
		}
		OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => false, 'requested' => true));
	} else {
		OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => true, 'requested' => false));
	}
} else {
	OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => false, 'requested' => false));
}
