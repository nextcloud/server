<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

\OC_Util::checkAdminUser();

$tmpl = new OCP\Template('encryption', 'settings-admin');

$crypt = new \OCA\Encryption\Crypto\Crypt(
	\OC::$server->getLogger(),
	\OC::$server->getUserSession(),
	\OC::$server->getConfig(),
	\OC::$server->getL10N('encryption'));

$util = new \OCA\Encryption\Util(
	new \OC\Files\View(),
	$crypt,
	\OC::$server->getLogger(),
	\OC::$server->getUserSession(),
	\OC::$server->getConfig(),
	\OC::$server->getUserManager());

// Check if an adminRecovery account is enabled for recovering files after lost pwd
$recoveryAdminEnabled = \OC::$server->getConfig()->getAppValue('encryption', 'recoveryAdminEnabled', '0');
$session = new \OCA\Encryption\Session(\OC::$server->getSession());

$encryptHomeStorage = $util->shouldEncryptHomeStorage();

$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);
$tmpl->assign('initStatus', $session->getStatus());
$tmpl->assign('encryptHomeStorage', $encryptHomeStorage);
$tmpl->assign('masterKeyEnabled', $util->isMasterKeyEnabled());

return $tmpl->fetchPage();
