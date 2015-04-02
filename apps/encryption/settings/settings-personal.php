<?php
/**
 * Copyright (c) 2015 Clark Tomlinson <clark@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$session = new \OCA\Encryption\Session(\OC::$server->getSession());
$userSession = \OC::$server->getUserSession();

$template = new OCP\Template('encryption', 'settings-personal');
$crypt = new \OCA\Encryption\Crypto\Crypt(
	\OC::$server->getLogger(),
	$userSession,
	\OC::$server->getConfig());

$util = new \OCA\Encryption\Util(
	new \OC\Files\View(),
	$crypt,
	\OC::$server->getLogger(),
	$userSession,
	\OC::$server->getConfig());

$keyManager = new \OCA\Encryption\KeyManager(
	\OC::$server->getEncryptionKeyStorage(\OCA\Encryption\Crypto\Encryption::ID),
	$crypt,
	\OC::$server->getConfig(),
	$userSession,
	$session,
	\OC::$server->getLogger(), $util);

$user = $userSession->getUser()->getUID();

$view = new \OC\Files\View('/');



$privateKeySet = $session->isPrivateKeySet();
// did we tried to initialize the keys for this session?
$initialized = $session->getStatus();

$recoveryAdminEnabled = \OC::$server->getConfig()->getAppValue('encryption', 'recoveryAdminEnabled');
$recoveryEnabledForUser = $util->isRecoveryEnabledForUser();

$result = false;

if ($recoveryAdminEnabled || !$privateKeySet) {
	$template->assign('recoveryEnabled', $recoveryAdminEnabled);
	$template->assign('recoveryEnabledForUser', $recoveryEnabledForUser);
	$template->assign('privateKeySet', $privateKeySet);
	$template->assign('initialized', $initialized);

	$result = $template->fetchPage();
}

return $result;

