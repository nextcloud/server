<?php
/**
 * Copyright (c) 2015 Clark Tomlinson <clark@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCA\Encryption\KeyManager;

\OC_Util::checkAdminUser();

$tmpl = new OCP\Template('encryption', 'settings-admin');

// Check if an adminRecovery account is enabled for recovering files after lost pwd
$recoveryAdminEnabled = \OC::$server->getConfig()->getAppValue('encryption', 'recoveryAdminEnabled', '0');

$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);
$tmpl->assign('initStatus', KeyManager::$session->get('initStatus'));

\OCP\Util::addscript('files_encryption', 'settings-admin');
\OCP\Util::addscript('core', 'multiselect');

return $tmpl->fetchPage();
