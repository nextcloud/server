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

\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled( 'files_encryption' );
\OCP\JSON::callCheck();

$return = $doSetup = false;

// Enable recoveryAdmin
if ( 
	isset( $_POST['adminEnableRecovery'] ) 
	&& 1 == $_POST['adminEnableRecovery'] 
// 	&& isset( $_POST['recoveryPassword'] ) 
// 	&& ! empty ( $_POST['recoveryPassword'] )
) {

	// TODO: Let the admin set this themselves
	$recoveryAdminUid = 'recoveryAdmin';
	
	// If desired recoveryAdmin UID is already in use
	if ( ! \OC_User::userExists( $recoveryAdminUid ) ) {
		
		// Create new recoveryAdmin user
		\OC_User::createUser( $recoveryAdminUid, $_POST['recoveryPassword'] );
		
		$doSetup = true;
		
	} else {
	
		// Get list of admin users
		$admins = OC_Group::usersInGroup( 'admin' );
		
		// If the existing recoveryAdmin UID is an admin
		if ( in_array( $recoveryAdminUid, $admins ) ) {
			
			// The desired recoveryAdmi UID pre-exists and can be used
			$doSetup = true;
		
		// If the recoveryAdmin UID exists but doesn't have admin rights
		} else {
		
			$return = false;
			
		}
		
	}
	
	// Setup recoveryAdmin user for encryption
	if ( $doSetup ) {
		
		$view = new \OC_FilesystemView( '/' );
		$util = new \OCA\Encryption\Util( $view, $recoveryAdminUid );
		
		// Ensure recoveryAdmin is ready for encryption (has usable keypair etc.)
		$util->setupServerSide( $_POST['recoveryPassword'] );
		
		// Store the UID in the DB
		OC_Appconfig::setValue( 'files_encryption', 'recoveryAdminUid', $recoveryAdminUid );
		
		$return = true;
		
	}
	
	// Set recoveryAdmin as enabled
	OC_Appconfig::setValue( 'files_encryption', 'recoveryAdminEnabled', 1 );

// Disable recoveryAdmin
} elseif ( 
	isset( $_POST['adminEnableRecovery'] ) 
	&& 0 == $_POST['adminEnableRecovery'] 
) {
		
		// Set recoveryAdmin as enabled
		OC_Appconfig::setValue( 'files_encryption', 'recoveryAdminEnabled', 0 );
		
		$return = true;

}

($return) ? OC_JSON::success() : OC_JSON::error();