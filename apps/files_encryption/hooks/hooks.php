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

	# TODO: use passphrase for encrypting private key that is separate to the login password

	/**
	 * @brief Startup encryption backend upon user login
	 * @note This method should never be called for users using client side encryption
	 */
	public static function login( $params ) {
	
// 		if ( Crypt::mode( $params['uid'] ) == 'server' ) {
			
			# TODO: use lots of dependency injection here
		
			$view = new \OC_FilesystemView( '/' );

			$util = new Util( $view, $params['uid'] );
			
			if ( ! $util->ready() ) {
				
				\OC_Log::write( 'Encryption library', 'User account "' . $params['uid'] . '" is not ready for encryption; configuration started' , \OC_Log::DEBUG );
				
				return $util->setupServerSide( $params['password'] );

			}
		
			\OC_FileProxy::$enabled = false;
			
			$encryptedKey = Keymanager::getPrivateKey( $view, $params['uid'] );
			
			\OC_FileProxy::$enabled = true;
			
			# TODO: dont manually encrypt the private keyfile - use the config options of openssl_pkey_export instead for better mobile compatibility
			
			$privateKey = Crypt::symmetricDecryptFileContent( $encryptedKey, $params['password'] );
			
			$session = new Session();
			
			$session->setPrivateKey( $privateKey, $params['uid'] );
			
			$view1 = new \OC_FilesystemView( '/' . $params['uid'] );
			
			// Set legacy encryption key if it exists, to support 
			// depreciated encryption system
			if ( 
			$view1->file_exists( 'encryption.key' )
			&& $legacyKey = $view1->file_get_contents( 'encryption.key' ) 
			) {
				
				$_SESSION['legacyenckey'] = Crypt::legacyDecrypt( $legacyKey, $params['password'] );
			
			}
// 		}

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
			
			// Get existing decrypted private key
			$privateKey = $_SESSION['privateKey'];
			
			// Encrypt private key with new user pwd as passphrase
			$encryptedPrivateKey = Crypt::symmetricEncryptFileContent( $privateKey, $params['password'] );
			
			// Save private key
			Keymanager::setPrivateKey( $encryptedPrivateKey );
			
			# NOTE: Session does not need to be updated as the 
			# private key has not changed, only the passphrase 
			# used to decrypt it has changed
			
		}
	
	}
	
	/**
	 * @brief update the encryption key of the file uploaded by the client
	 */
	public static function updateKeyfile( $params ) {
	
		if ( Crypt::mode() == 'client' ) {
			
			if ( isset( $params['properties']['key'] ) ) {
				
				Keymanager::setFileKey( $params['path'], $params['properties']['key'] );
			
			} else {
				
				\OC_Log::write( 
					'Encryption library', "Client side encryption is enabled but the client doesn't provide a encryption key for the file!"
					, \OC_Log::ERROR 
				);
				
				error_log( "Client side encryption is enabled but the client doesn't provide an encryption key for the file!" );
				
			}
			
		}
		
	}
	
}

?>