<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
