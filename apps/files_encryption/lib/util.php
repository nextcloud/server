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

namespace OCA\Encryption;

/**
 * @brief Class for utilities relating to encrypted file storage system
 * @param $view OC_FilesystemView object, expected to have OC '/' as root path
 * @param $client flag indicating status of client side encryption. Currently
 * unused, likely to become obsolete shortly
 */

class Util {
	
	
	# Web UI:
	
	## DONE: files created via web ui are encrypted
	## DONE: file created & encrypted via web ui are readable in web ui
	## DONE: file created & encrypted via web ui are readable via webdav
	
	
	# WebDAV:
	
	## DONE: new data filled files added via webdav get encrypted
	## DONE: new data filled files added via webdav are readable via webdav
	## DONE: reading unencrypted files when encryption is enabled works via webdav
	## DONE: files created & encrypted via web ui are readable via webdav
	
	
	# Legacy support:
	
	## DONE: add method to check if file is encrypted using new system
	## DONE: add method to check if file is encrypted using old system
	## DONE: add method to fetch legacy key
	## DONE: add method to decrypt legacy encrypted data
	
	## TODO: add method to encrypt all user files using new system
	## TODO: add method to decrypt all user files using new system
	## TODO: add method to encrypt all user files using old system
	## TODO: add method to decrypt all user files using old system
	
	
	# Admin UI:
	
	## DONE: changing user password also changes encryption passphrase
	
	## TODO: add support for optional recovery in case of lost passphrase / keys
	## TODO: add admin optional required long passphrase for users
	## TODO: add UI buttons for encrypt / decrypt everything
	## TODO: implement flag system to allow user to specify encryption by folder, subfolder, etc.
	
	
	# Sharing:
	
	## TODO: add support for encrypting to multiple public keys
	## TODO: add support for decrypting to multiple private keys
	
	
	# Integration testing:
	
	## TODO: test new encryption with webdav
	## TODO: test new encryption with versioning
	## TODO: test new encryption with sharing
	## TODO: test new encryption with proxies
	
	
	private $view; // OC_FilesystemView object for filesystem operations
	private $pwd; // User Password
	private $client; // Client side encryption mode flag
	private $publicKeyDir; // Directory containing all public user keys
	private $encryptionDir; // Directory containing user's files_encryption
	private $keyfilesPath; // Directory containing user's keyfiles
	private $publicKeyPath; // Path to user's public key
	private $privateKeyPath; // Path to user's private key

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
		
		return true;
	
	}
	
	public function findFiles( $directory, $type = 'plain' ) {
	
	# TODO: test finding non plain content
		
		if ( $handle = $this->view->opendir( $directory ) ) {

			while ( false !== ( $file = readdir( $handle ) ) ) {
			
				if (
				$file != "." 
				&& $file != ".."
				) {
				
					$filePath = $directory . '/' . $this->view->getRelativePath( '/' . $file );
					
					var_dump($filePath);
					
					if ( $this->view->is_dir( $filePath ) ) { 
						
						$this->findFiles( $filePath );
						
					} elseif ( $this->view->is_file( $filePath ) ) {
					
						if ( $type == 'plain' ) {
					
							$this->files[] = array( 'name' => $file, 'path' => $filePath );
							
						} elseif ( $type == 'encrypted' ) {
						
							if (  Crypt::isEncryptedContent( $this->view->file_get_contents( $filePath ) ) ) {
							
								$this->files[] = array( 'name' => $file, 'path' => $filePath );
							
							}
						
						} elseif ( $type == 'legacy' ) {
						
							if (  Crypt::isLegacyEncryptedContent( $this->view->file_get_contents( $filePath ) ) ) {
							
								$this->files[] = array( 'name' => $file, 'path' => $filePath );
							
							}
						
						}
					
					}
					
				}
				
			}
			
			if ( !empty( $this->files ) ) {
			
				return $this->files;
			
			} else {
			
				return false;
			
			}
		
		}
		
		return false;

	}
	
        /**
         * @brief Check if a given path identifies an encrypted file
         * @return true / false
         */
	public function isEncryptedPath( $path ) {
	
		// Disable encryption proxy so data retreived is in its 
		// original form
		\OC_FileProxy::$enabled = false;
	
		$data = $this->view->file_get_contents( $path );
		
		\OC_FileProxy::$enabled = true;
		
		return Crypt::isEncryptedContent( $data );
	
	}
	
	public function encryptAll( $directory ) {
	
		$plainFiles = $this->findFiles( $this->view, 'plain' );
		
		if ( $this->encryptFiles( $plainFiles ) ) {
		
			return true;
			
		} else {
		
			return false;
			
		}
		
	}
	
	public function getPath( $pathName ) {
	
		switch ( $pathName ) {
			
			case 'publicKeyDir':
			
				return $this->publicKeyDir;
				
				break;
				
			case 'encryptionDir':
			
				return $this->encryptionDir;
				
				break;
				
			case 'keyfilesPath':
			
				return $this->keyfilesPath;
				
				break;
				
			case 'publicKeyPath':
			
				return $this->publicKeyPath;
				
				break;
				
			case 'privateKeyPath':
			
				return $this->privateKeyPath;
				
				break;
			
		}
		
	}

}
