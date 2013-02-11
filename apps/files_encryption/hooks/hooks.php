<?php

/**
 * ownCloud
 *
 * @author Sam Tuke
 * @copyright 2012 Sam Tuke samtuke@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Encryption;

/**
 * Class for hook specific logic
 */

class Hooks {

	// TODO: use passphrase for encrypting private key that is separate to 
	// the login password

	/**
	 * @brief Startup encryption backend upon user login
	 * @note This method should never be called for users using client side encryption
	 */
	public static function login( $params ) {
	
		// Manually initialise Filesystem{} singleton with correct 
		// fake root path, in order to avoid fatal webdav errors
		\OC\Files\Filesystem::init( $params['uid'] . '/' . 'files' . '/' );
	
		$view = new \OC_FilesystemView( '/' );

		$util = new Util( $view, $params['uid'] );
		
		// Check files_encryption infrastructure is ready for action
		if ( ! $util->ready() ) {
			
			\OC_Log::write( 'Encryption library', 'User account "' . $params['uid'] . '" is not ready for encryption; configuration started', \OC_Log::DEBUG );
			
			return $util->setupServerSide( $params['password'] );

		}
	
		\OC_FileProxy::$enabled = false;
		
		$encryptedKey = Keymanager::getPrivateKey( $view, $params['uid'] );
		
		\OC_FileProxy::$enabled = true;
		
		$privateKey = Crypt::symmetricDecryptFileContent( $encryptedKey, $params['password'] );
		
		$session = new Session();
		
		$session->setPrivateKey( $privateKey, $params['uid'] );
		
		$view1 = new \OC_FilesystemView( '/' . $params['uid'] );
		
		// Set legacy encryption key if it exists, to support 
		// depreciated encryption system
		if ( 
			$view1->file_exists( 'encryption.key' )
			&& $encLegacyKey = $view1->file_get_contents( 'encryption.key' ) 
		) {
		
			$plainLegacyKey = Crypt::legacyDecrypt( $encLegacyKey, $params['password'] );
			
			$session->setLegacyKey( $plainLegacyKey );
		
		}
		
		\OC_FileProxy::$enabled = false;
		
		$publicKey = Keymanager::getPublicKey( $view, $params['uid'] );
		
		\OC_FileProxy::$enabled = false;
		
		// Encrypt existing user files:
		// This serves to upgrade old versions of the encryption
		// app (see appinfo/spec.txt)
		if ( 
			$util->encryptAll( $publicKey,  '/' . $params['uid'] . '/' . 'files', $session->getLegacyKey(), $params['password'] )
		) {
			
			\OC_Log::write( 
				'Encryption library', 'Encryption of existing files belonging to "' . $params['uid'] . '" started at login'
				, \OC_Log::INFO 
			);
		
		}

		return true;

	}
	
	/**
	 * @brief Change a user's encryption passphrase
	 * @param array $params keys: uid, password
	 */
	public static function setPassphrase( $params ) {
		
		// Only attempt to change passphrase if server-side encryption
		// is in use (client-side encryption does not have access to 
		// the necessary keys)
		if ( Crypt::mode() == 'server' ) {
			
			$session = new Session();
			
			// Get existing decrypted private key
			$privateKey = $session->getPrivateKey();
			
			// Encrypt private key with new user pwd as passphrase
			$encryptedPrivateKey = Crypt::symmetricEncryptFileContent( $privateKey, $params['password'] );
			
			// Save private key
			Keymanager::setPrivateKey( $encryptedPrivateKey );
			
			// NOTE: Session does not need to be updated as the 
			// private key has not changed, only the passphrase 
			// used to decrypt it has changed
			
		}
	
	}
	
	/**
	 * @brief update the encryption key of the file uploaded by the client
	 */
	public static function updateKeyfile( $params ) {
	
		if ( Crypt::mode() == 'client' ) {
			
			if ( isset( $params['properties']['key'] ) ) {
				
				$view = new \OC_FilesystemView( '/' );
				$userId = \OCP\User::getUser();
				
				Keymanager::setFileKey( $view, $params['path'], $userId, $params['properties']['key'] );
				
			} else {
				
				\OC_Log::write( 
					'Encryption library', "Client side encryption is enabled but the client doesn't provide a encryption key for the file!"
					, \OC_Log::ERROR 
				);
				
				error_log( "Client side encryption is enabled but the client doesn't provide an encryption key for the file!" );
				
			}
			
		}
		
	}
	
	/**
	 * @brief 
	 */
	public static function postShared( $params ) {
		
		// NOTE: $params is an array with these keys:
		// itemSource -> int, filecache file ID
		// shareWith -> string, uid of user being shared to
		// fileTarget -> path of file being shared
		// uidOwner -> owner of the original file being shared
		
		$view = new \OC_FilesystemView( '/' );
		$userId = \OCP\User::getUser();
		$util = new Util( $view, $userId );
		$session = new Session();
		
		$shares = \OCP\Share::getUsersSharingFile( $params['fileTarget'], 1 );
		
		$userIds = array();
		
		foreach ( $shares as $share ) {
		
			$util = new Util( $view, $share['userId'] );
			
			// Check that the user is encryption capable
			if ( $util->ready() ) {
		
				// Construct array of just UIDs for Keymanager{}
				$userIds[] = $share['userId'];
			
			} else {
			
				// Log warning; we can't do necessary setup here
				// because we don't have the user passphrase
				// TODO: Provide user feedback indicating that
				// sharing failed
				\OC_Log::write( 'Encryption library', 'File cannot be shared: user "'.$share['userId'].'" is not setup for encryption', \OC_Log::WARN );
				
			}
		
		}
		
		$userPubKeys = Keymanager::getPublicKeys( $view, $userIds );
		
		\OC_FileProxy::$enabled = false;
		
		// get the keyfile
		$encKeyfile = Keymanager::getFileKey( $view, $userId, $params['fileTarget'] );
		
		$privateKey = $session->getPrivateKey();
		
		// decrypt the keyfile
		$plainKeyfile = Crypt::keyDecrypt( $encKeyfile, $privateKey );
		
		// re-enc keyfile to sharekeys
		$shareKeys = Crypt::multiKeyEncrypt( $plainKeyfile, $userPubKeys );
		
		// save sharekeys
		if ( ! Keymanager::setShareKeys( $view, $params['fileTarget'], $shareKeys['keys'] ) ) {
		
			trigger_error( "SET Share keys failed" );
			
		}
		
		// Delete existing keyfile
		// Do this last to ensure file is recoverable in case of error
// 		Keymanager::deleteFileKey( $view, $userId, $params['fileTarget'] );
		
		\OC_FileProxy::$enabled = true;
		
		return true;
		
	}
	
	/**
	 * @brief 
	 */
	public static function preUnshare( $params ) {
		
		// Delete existing catfile
		
		// Generate new catfile and env keys
		
		// Save env keys to user folders
	}
	
	/**
	 * @brief 
	 */
	public static function preUnshareAll( $params ) {
		
		trigger_error( "preUnshareAll" );
		
	}
	
}
