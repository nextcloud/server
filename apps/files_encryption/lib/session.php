<?php
/**
 * ownCloud
 *
 * @author Sam Tuke
 * @copyright 2012 Sam Tuke samtuke@owncloud.com
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
 * Class for handling encryption related session data
 */

class Session {
	
	/**
	 * @brief if session is started, check if ownCloud key pair is set up, if not create it
	 * 
	 * The ownCloud key pair is used to allow public link sharing even if encryption is enabled
	 */
	public function __construct() {
		$view = new \OC\Files\View('/');
		if (!$view->is_dir('owncloud_private_key')) {
			$view->mkdir('owncloud_private_key');
		}
		
		if (!$view->file_exists("/public-keys/owncloud.public.key") || !$view->file_exists("/owncloud_private_key/owncloud.private.key") ) {
			
			$keypair = Crypt::createKeypair();
			
			\OC_FileProxy::$enabled = false;
			// Save public key
			$view->file_put_contents( '/public-keys/owncloud.public.key', $keypair['publicKey'] );
			// Encrypt private key empthy passphrase
			$encryptedPrivateKey = Crypt::symmetricEncryptFileContent( $keypair['privateKey'], '' );
			// Save private key
			error_log("encrypted private key: " . $encryptedPrivateKey );
			$view->file_put_contents( '/owncloud_private_key/owncloud.private.key', $encryptedPrivateKey );
			
			\OC_FileProxy::$enabled = true;
		}
	}

	/**
	 * @brief Sets user private key to session
	 * @return bool
	 *
	 */
	public function setPrivateKey( $privateKey ) {
	
		$_SESSION['privateKey'] = $privateKey;
		
		return true;
		
	}
	
	/**
	 * @brief Gets user private key from session
	 * @returns string $privateKey The user's plaintext private key
	 *
	 */
	public function getPrivateKey() {
	
		if ( 
			isset( $_SESSION['privateKey'] )
			&& !empty( $_SESSION['privateKey'] )
		) {
		
			return $_SESSION['privateKey'];
		
		} else {
		
			return false;
			
		}
		
	}
	
	/**
	 * @brief Sets user legacy key to session
	 * @return bool
	 *
	 */
	public function setLegacyKey( $legacyKey ) {
	
		if ( $_SESSION['legacyKey'] = $legacyKey ) {
		
			return true;
			
		}
		
	}
	
	/**
	 * @brief Gets user legacy key from session
	 * @returns string $legacyKey The user's plaintext legacy key
	 *
	 */
	public function getLegacyKey() {
	
		if ( 
			isset( $_SESSION['legacyKey'] )
			&& !empty( $_SESSION['legacyKey'] )
		) {
		
			return $_SESSION['legacyKey'];
		
		} else {
		
			return false;
			
		}
		
	}

}