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
		if (!empty($email) and isset($_POST['sectoken']) and isset($_SESSION['sectoken']) and ($_POST['sectoken']==$_SESSION['sectoken']) ) {
			$link = OC_Helper::linkToAbsolute('core/lostpassword', 'resetpassword.php').'?user='.urlencode($_POST['user']).'&token='.$token;
			$tmpl = new OC_Template('core/lostpassword', 'email');
			$tmpl->assign('link', $link);
			$msg = $tmpl->fetchPage();
			$l = OC_L10N::get('core');
			$from = 'lostpassword-noreply@' . OC_Helper::serverHost();
			OC_MAIL::send($email,$_POST['user'],$l->t('ownCloud password reset'),$msg,$from,'ownCloud');
			echo('sent');

		}
		$sectoken=rand(1000000,9999999);
		$_SESSION['sectoken']=$sectoken;
		OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => false, 'requested' => true, 'sectoken' => $sectoken));
	} else {
		$sectoken=rand(1000000,9999999);
		$_SESSION['sectoken']=$sectoken;
		OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => true, 'requested' => false, 'sectoken' => $sectoken));
	}
} else {
	$sectoken=rand(1000000,9999999);
	$_SESSION['sectoken']=$sectoken;
	OC_Template::printGuestPage('core/lostpassword', 'lostpassword', array('error' => false, 'requested' => false, 'sectoken' => $sectoken));
}
