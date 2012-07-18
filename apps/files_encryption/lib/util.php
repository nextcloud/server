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
 * Class for utilities relating to encrypted file storage system
 */

class Util {

	private $view; // OC_FilesystemView object for filesystem operations
	private $pwd; // User Password
	private $client; // Client side encryption mode flag

        /**
         * @brief get a list of all available versions of a file in descending chronological order
         * @param $filename file to find versions of, relative to the user files dir
         * @param $count number of versions to return
         * @returns array
         */
	public function __construct( \OC_FilesystemView $view, $client = false ) {
	
		$this->view = $view;
		$this->client = $client;
		
	}
	
	public function ready() {
		
		if( 
		!$this->view->file_exists( '/' . 'keyfiles' )
		or !$this->view->file_exists( '/' . 'keypair' )
		or !$this->view->file_exists( '/' . 'keypair' . '/'. 'encryption.public.key' )
		or !$this->view->file_exists( '/' . 'keypair' . '/'. 'encryption.private.key' ) 
		) {
		
			return false;
			
		} else {
		
			return true;
			
		}
	
	}
	
	public function setup( $passphrase = null ) {
	
		$publicKeyFileName = 'encryption.public.key';
		$privateKeyFileName = 'encryption.private.key';
	
		// Log changes to user's filesystem
		$this->appInfo = \OC_APP::getAppInfo( 'files_encryption' );
		
		\OC_Log::write( $this->appInfo['name'], 'File encryption for user will be set up' , \OC_Log::INFO );
		
		// Create mirrored keyfile directory
		if( !$this->view->file_exists( '/' . 'keyfiles' ) ) {
		
			$this->view->mkdir( '/'. 'keyfiles' );
		
		}
		
		// Create keypair directory
		if( !$this->view->file_exists( '/'. 'keypair' ) ) {
		
			$this->view->mkdir( '/'. 'keypair' );
		
		}
		
		// Create user keypair
		if ( 
		!$this->view->file_exists( '/'. 'keypair'. '/' . $publicKeyFileName ) 
		or !$this->view->file_exists( '/'. 'keypair'. '/' . $privateKeyFileName ) 
		) {
		
			// Generate keypair
			$keypair = Crypt::createKeypair();
			
			// Save public key
			$this->view->file_put_contents( '/'. 'keypair'. '/' . $publicKeyFileName, $keypair['publicKey'] );
			
			if ( $this->client == false ) {
				
				# TODO: Use proper IV in encryption
				
				// Encrypt private key with user pwd as passphrase
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent( $keypair['privateKey'], $passphrase );
				
				// $iv = openssl_random_pseudo_bytes(16);
				$this->view->file_put_contents( '/'. 'keypair'. '/' . $privateKeyFileName, $encryptedPrivateKey );
				
			} else {
			
				# TODO PHASE2: add public key to keyserver for client-side
				# TODO PHASE2: encrypt private key using password / new client side specified key, instead of existing user pwd
			
			}
			
		}
	
	}

}
