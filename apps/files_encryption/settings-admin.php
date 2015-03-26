<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sam Tuke <mail@samtuke.com>
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

\OC_Util::checkAdminUser();

$tmpl = new OCP\Template('files_encryption', 'settings-admin');

// Check if an adminRecovery account is enabled for recovering files after lost pwd
$recoveryAdminEnabled = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryAdminEnabled', '0');
$session = new \OCA\Files_Encryption\Session(new \OC\Files\View('/'));
$initStatus = $session->getInitialized();

$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);
$tmpl->assign('initStatus', $initStatus);

\OCP\Util::addscript('files_encryption', 'settings-admin');
\OCP\Util::addscript('core', 'multiselect');

return $tmpl->fetchPage();
