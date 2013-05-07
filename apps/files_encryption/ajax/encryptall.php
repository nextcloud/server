<?php
/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * @brief Script to handle manual trigger of \OCA\Encryption\Util{}->encryptAll()
 */

use OCA\Encryption;

\OCP\JSON::checkAppEnabled( 'files_encryption' );
\OCP\JSON::callCheck();

$return = false;

if ( 
	isset( $_POST['encryptAll'] )
	&& ! empty( $_POST['userPassword'] )
) {

	$view = new \OC_FilesystemView( '' );
	$userId = \OCP\User::getUser();
	$util = new \OCA\Encryption\Util( $view, $userId );
	$session = new \OCA\Encryption\Session( $view );
	$publicKey = \OCA\Encryption\Keymanager::getPublicKey( $view, $userId );
	$path = '/' . $userId . '/' . 'files';
	
	$util->encryptAll( $publicKey, $path, $session->getLegacyKey(), $_POST['userPassword'] );
	
	$return = true;

} else {

	$return = false;
	
}

// Return success or failure
( $return ) ? \OCP\JSON::success() : \OCP\JSON::error();