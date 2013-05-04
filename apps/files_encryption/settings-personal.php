<?php
/**
 * Copyright (c) 2013 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$tmpl = new OCP\Template( 'files_encryption', 'settings-personal');

$blackList = explode( ',', \OCP\Config::getAppValue( 'files_encryption', 'type_blacklist', '' ) );

// Add human readable message in case nothing is blacklisted
if ( 
	1 == count( $blackList )
	&& $blackList[0] == ''
) {
	
	// FIXME: Make this string translatable
	$blackList[0] = "(None - all filetypes will be encrypted)";

}

$user = \OCP\USER::getUser();
$view = new \OC_FilesystemView( '/' );
$util = new \OCA\Encryption\Util( $view, $user );

$recoveryAdminEnabled = OC_Appconfig::getValue( 'files_encryption', 'recoveryAdminEnabled' );
$recoveryEnabledForUser = $util->recoveryEnabledForUser();

\OCP\Util::addscript( 'files_encryption', 'settings-personal' );

$tmpl->assign( 'recoveryEnabled', $recoveryAdminEnabled );
$tmpl->assign( 'recoveryEnabledForUser', $recoveryEnabledForUser );
$tmpl->assign( 'blacklist', $blackList );

return $tmpl->fetchPage();

return null;
