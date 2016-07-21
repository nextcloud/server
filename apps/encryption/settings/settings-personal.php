<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

$session = new \OCA\Encryption\Session(\OC::$server->getSession());
$userSession = \OC::$server->getUserSession();

$template = new OCP\Template('encryption', 'settings-personal');
$crypt = new \OCA\Encryption\Crypto\Crypt(
	\OC::$server->getLogger(),
	$userSession,
	\OC::$server->getConfig(),
	\OC::$server->getL10N('encryption'));

$util = new \OCA\Encryption\Util(
	new \OC\Files\View(),
	$crypt,
	\OC::$server->getLogger(),
	$userSession,
	\OC::$server->getConfig(),
	\OC::$server->getUserManager());

$keyManager = new \OCA\Encryption\KeyManager(
	\OC::$server->getEncryptionKeyStorage(),
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
$recoveryEnabledForUser = $util->isRecoveryEnabledForUser($user);

$result = false;

if ($recoveryAdminEnabled || !$privateKeySet) {
	$template->assign('recoveryEnabled', $recoveryAdminEnabled);
	$template->assign('recoveryEnabledForUser', $recoveryEnabledForUser);
	$template->assign('privateKeySet', $privateKeySet);
	$template->assign('initialized', $initialized);

	$result = $template->fetchPage();
}

return $result;

