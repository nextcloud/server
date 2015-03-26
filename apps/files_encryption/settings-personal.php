<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

