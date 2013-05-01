<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

\OC_Util::checkAdminUser();

$tmpl = new OCP\Template( 'files_encryption', 'settings-admin' );

$blackList = explode( ',', \OCP\Config::getAppValue( 'files_encryption', 'type_blacklist', '' ) );

// Check if an adminRecovery account is enabled for recovering files after lost pwd
$view = new OC_FilesystemView( '' );

$recoveryAdminEnabled = OC_Appconfig::getValue( 'files_encryption', 'recoveryAdminEnabled' );
$recoveryAdminUid = OC_Appconfig::getValue( 'files_encryption', 'recoveryAdminUid' );

$tmpl->assign( 'blacklist', $blackList );
$tmpl->assign( 'encryption_mode', \OC_Appconfig::getValue( 'files_encryption', 'mode', 'none' ) );
$tmpl->assign( 'recoveryEnabled', $recoveryAdminEnabled );

\OCP\Util::addscript( 'files_encryption', 'settings-admin' );
\OCP\Util::addscript( 'core', 'multiselect' );

return $tmpl->fetchPage();
