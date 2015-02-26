<?php
/**
 * Copyright (c) 2013 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Add CSS stylesheet
\OC_Util::addStyle('files_encryption', 'settings-personal');

$tmpl = new OCP\Template('files_encryption', 'settings-personal');

$user = \OCP\USER::getUser();
$view = new \OC\Files\View('/');
$util = new \OCA\Files_Encryption\Util($view, $user);
$session = new \OCA\Files_Encryption\Session($view);

$privateKeySet = $session->getPrivateKey() !== false;
// did we tried to initialize the keys for this session?
$initialized = $session->getInitialized();

$recoveryAdminEnabled = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryAdminEnabled');
$recoveryEnabledForUser = $util->recoveryEnabledForUser();

$result = false;

if ($recoveryAdminEnabled || !$privateKeySet) {

	\OCP\Util::addscript('files_encryption', 'settings-personal');

	$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);
	$tmpl->assign('recoveryEnabledForUser', $recoveryEnabledForUser);
	$tmpl->assign('privateKeySet', $privateKeySet);
	$tmpl->assign('initialized', $initialized);

	$result = $tmpl->fetchPage();
}

return $result;

