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
$recoveryAdminEnabled = OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled', '0');

$tmpl->assign('recoveryEnabled', $recoveryAdminEnabled);

\OCP\Util::addscript('files_encryption', 'settings-admin');
\OCP\Util::addscript('core', 'multiselect');

return $tmpl->fetchPage();
