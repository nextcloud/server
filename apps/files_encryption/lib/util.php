<?php
/**
 * ownCloud
 *
 * @author Sam Tuke, Frank Karlitschek
 * @copyright 2012 Sam Tuke samtuke@owncloud.com, 
 * Frank Karlitschek frank@owncloud.org
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

// Todo:
//  - Crypt/decrypt button in the userinterface
//  - Setting if crypto should be on by default
//  - Add a setting "Don´t encrypt files larger than xx because of performance reasons"
//  - Transparent decrypt/encrypt in filesystem.php. Autodetect if a file is encrypted (.encrypted extension)
//  - Don't use a password directly as encryption key. but a key which is stored on the server and encrypted with the user password. -> password change faster
//  - IMPORTANT! Check if the block lenght of the encrypted data stays the same

namespace OCA_Encryption;

/**
 * @brief Class for utilities relating to encrypted file storage system
 * @param $view OC_FilesystemView object, expected to have OC '/' as root path
 * @param $client flag indicating status of client side encryption. Currently
 * unused, likely to become obsolete shortly
 */

class Util {

	# DONE: add method to check if file is encrypted using new system
	# DONE: add method to check if file is encrypted using old system
	# DONE: add method to fetch legacy key
	# DONE: add method to decrypt legacy encrypted data
	
	# TODO: add method to encrypt all user files using new system
	# TODO: add method to decrypt all user files using new system
	# TODO: add method to encrypt all user files using old system
	# TODO: add method to decrypt all user files using old system
	
	# TODO: fix / test the crypt stream proxy class
	# TODO: add support for optional recovery user in case of lost passphrase / keys
	# TODO: add admin optional required long passphrase for users
	# TODO: implement flag system to allow user to specify encryption by folder, subfolder, etc.
	# TODO: add UI buttons for encrypt / decrypt everything?
	
	# TODO: test new encryption with webdav
	# TODO: test new encryption with versioning
	# TODO: test new encryption with sharing
	# TODO: test new encryption with proxies

	private $view; // OC_FilesystemView object for filesystem operations
	private $pwd; // User Password
	private $client; // Client side encryption mode flag

	public function __construct( \OC_FilesystemView $view, $userId, $client = false ) {
	
		$this->view = $view;
		$this->userId = $userId;
		$this->client = $client;
		$this->publicKeyDir =  '/' . 'public-keys';
		$this->encryptionDir =  '/' . $this->userId . '/' . 'files_encryption';
		$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
		$this->publicKeyPath = $this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
		$this->privateKeyPath = $this->encryptionDir . '/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key
		
	}
	
	public function ready() {
		
		if( 
		!$this->view->file_exists( $this->keyfilesPath )
		or !$this->view->file_exists( $this->publicKeyPath )
		or !$this->view->file_exists( $this->privateKeyPath ) 
		) {
		
			return false;
			
		} else {
		
			return true;
			
		}
	
	}
	
        /**
         * @brief Sets up user folders and keys for serverside encryption
         * @param $passphrase passphrase to encrypt server-stored private key with
         */
	public function setupServerSide( $passphrase = null ) {
	
		// Log changes to user's filesystem
		$this->appInfo = \OC_APP::getAppInfo( 'files_encryption' );
		
		\OC_Log::write( $this->appInfo['name'], 'File encryption for user "' . $this->userId . '" will be set up' , \OC_Log::INFO );
		
		// Create shared public key directory
		if( !$this->view->file_exists( $this->publicKeyDir ) ) {
		
			$this->view->mkdir( $this->publicKeyDir );
		
		}
		
		// Create encryption app directory
		if( !$this->view->file_exists( $this->encryptionDir ) ) {
		
			$this->view->mkdir( $this->encryptionDir );
		
		}
		
		// Create mirrored keyfile directory
		if( !$this->view->file_exists( $this->keyfilesPath ) ) {
		
			$this->view->mkdir( $this->keyfilesPath );
		
		}
		
		// Create user keypair
		if ( 
		!$this->view->file_exists( $this->publicKeyPath ) 
		or !$this->view->file_exists( $this->privateKeyPath ) 
		) {
		
			// Generate keypair
			$keypair = Crypt::createKeypair();
		
			\OC_FileProxy::$enabled = false;
			
			// Save public key
			$this->view->file_put_contents( $this->publicKeyPath, $keypair['publicKey'] );
			
			// Encrypt private key with user pwd as passphrase
			$encryptedPrivateKey = Crypt::symmetricEncryptFileContent( $keypair['privateKey'], $passphrase );
			
			// Save private key
			$this->view->file_put_contents( $this->privateKeyPath, $encryptedPrivateKey );
			
			\OC_FileProxy::$enabled = true;
			
		}
	
	}
	
	/**
	 * @brief Get the blowfish encryption handeler for a key
	 * @param $key string (optional)
	 * @return Crypt_Blowfish blowfish object
	 *
	 * if the key is left out, the default handeler will be used
	 */
	public function getBlowfish( $key = '' ) {
	
		if ( $key ) {
		
			return new \Crypt_Blowfish( $key );
		
		} else {
		
			return false;
			
		}
		
	}
	
	/**
	 * @brief Fetch the legacy encryption key from user files
	 * @param string $login used to locate the legacy key
	 * @param string $passphrase used to decrypt the legacy key
	 * @return true / false
	 *
	 * if the key is left out, the default handeler will be used
	 */
	public function getLegacyKey( $passphrase ) {

		//OC_FileProxy::$enabled = false;
		
		if ( 
		$passphrase 
		and $key = $this->view->file_get_contents( '/encryption.key' ) 
		) {
		
			//OC_FileProxy::$enabled = true;
		
			if ( $this->legacyKey = $this->legacyDecrypt( $key, $passphrase ) ) {
			
				return true;
				
			} else {
			
				return false;
				
			}
			
		} else {
		
			return false;
			
		}
		
	}
	
	/**
	 * @brief encrypts content using legacy blowfish system
	 * @param $content the cleartext message you want to encrypt
	 * @param $key the encryption key (optional)
	 * @returns encrypted content
	 *
	 * This function encrypts an content
	 */
	public function legacyEncrypt( $content, $passphrase = '' ) {
	
		$bf = $this->getBlowfish( $passphrase );
		
		return $bf->encrypt( $content );
		
	}
	
	/**
	* @brief decryption of an content
	* @param $content the cleartext message you want to decrypt
	* @param $key the encryption key (optional)
	* @returns cleartext content
	*
	* This function decrypts an content
	*/
	public function legacyDecrypt( $content, $passphrase = '' ) {
	
		$bf = $this->getBlowfish( $passphrase );
		
		$data = $bf->decrypt( $content );
		
		return $data;
		
	}
	
	/**
	* @brief Re-encryptes a legacy blowfish encrypted file using AES with integrated IV
	* @param $legacyContent the legacy encrypted content to re-encrypt
	* @returns cleartext content
	*
	* This function decrypts an content
	*/
	public function legacyRecrypt( $legacyContent ) {
		
		# TODO: write me
	
	}

}
