<?php
/**
 * Copyright (c) 2015 Clark Tomlinson <clark@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Add CSS stylesheet
\OC_Util::addStyle('encryption', 'settings-personal');

$session = new \OCA\Encryption\Session(\OC::$server->getSession());

$tmpl = new OCP\Template('encryption', 'settings-personal');
$crypt = new \OCA\Encryption\Crypto\Crypt(
	\OC::$server->getLogger(),
	\OC::$server->getUserSession(),
	\OC::$server->getConfig());

$util = new \OCA\Encryption\Util(
	new \OC\Files\View(),
	$crypt,
	\OC::$server->getLogger(),
	\OC::$server->getUserSession(),
	\OC::$server->getConfig());

$keymanager = new \OCA\Encryption\KeyManager(
	\OC::$server->getEncryptionKeyStorage(\OCA\Encryption\Crypto\Encryption::ID),
	$crypt,
	\OC::$server->getConfig(),
	\OC::$server->getUserSession(),
	$session,
	\OC::$server->getLogger(), $util);

$user = \OCP\User::getUser();

$view = new \OC\Files\View('/');



$privateKeySet = $session->isPrivateKeySet();
// did we tried to initialize the keys for this session?
$initialized = $session->getStatus();

$recoveryAdminEnabled = \OC::$server->getConfig()->getAppValue('encryption', 'recoveryAdminEnabled');
$recoveryEnabledForUser = $util->isRecoveryEnabledForUser();

$result = false;

if ($recoveryAdminEnabled || !$privateKeySet) {
	$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);
	$tmpl->assign('recoveryEnabledForUser', $recoveryEnabledForUser);
	$tmpl->assign('privateKeySet', $privateKeySet);
	$tmpl->assign('initialized', $initialized);

	$result = $tmpl->fetchPage();
}

return $result;

