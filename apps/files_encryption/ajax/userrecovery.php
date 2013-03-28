setValue( $app, $key, $value )

<?php
/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * @brief Script to handle admin settings for encrypted key recovery
 */

use OCA\Encryption;

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled( 'files_encryption' );
\OCP\JSON::callCheck();

if ( 
	isset( $_POST['userEnableRecovery'] ) 
) {

	// Ensure preference is an integer
	$recoveryEnabled = intval( $_POST['userEnableRecovery'] );

	$userId = \OCP\USER::getUser();
	$view = new \OC_FilesystemView( '/' );
	$util = new Util( $view, $userId );
	
	// Save recovery preference to DB
	$result = $util->setRecovery( $recoveryEnabled );
	
	if ( $result ) {
	
		\OCP\JSON::success();
		
	} else {
	
		\OCP\JSON::error();
		
	}
	
}