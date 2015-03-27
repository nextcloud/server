<?php
/**
 * Copyright (c) 2015 Clark Tomlinson <clark@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Add CSS stylesheet
\OC_Util::addStyle('encryption', 'settings-personal');

$tmpl = new OCP\Template('encryption', 'settings-personal');

$user = \OCP\User::getUser();
$view = new \OC\Files\View('/');
$util = new \OCA\Files_Encryption\Util($view, $user);
$session = new \OCA\Files_Encryption\Session($view);

$privateKeySet = $session->getPrivateKey() !== false;
// did we tried to initialize the keys for this session?
$initialized = $session->getInitialized();

$recoveryAdminEnabled = \OC::$server->getConfig()->getAppValue('encryption', 'recoveryAdminEnabled');
$recoveryEnabledForUser = $util->recoveryEnabledForUser();

$result = false;

if ($recoveryAdminEnabled || !$privateKeySet) {

	\OCP\Util::addscript('encryption', 'settings-personal');

	$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);
	$tmpl->assign('recoveryEnabledForUser', $recoveryEnabledForUser);
	$tmpl->assign('privateKeySet', $privateKeySet);
	$tmpl->assign('initialized', $initialized);

	$result = $tmpl->fetchPage();
}

return $result;

