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

# Bugs
# ----
# Sharing a file to a user without encryption set up will not provide them with access but won't notify the sharer
# Timeouts on first login due to encryption of very large files


# Missing features
# ----------------
# Re-use existing keyfiles so they don't need version control (part implemented, stream{} and util{} remain)
# Make sure user knows if large files weren't encrypted
# Trashbin support


// Old Todo:
//  - Crypt/decrypt button in the userinterface
//  - Setting if crypto should be on by default
//  - Add a setting "Don´t encrypt files larger than xx because of performance 
//    reasons"

namespace OCA\Encryption;

/**
 * @brief Class for utilities relating to encrypted file storage system
 * @param OC_FilesystemView $view expected to have OC '/' as root path
 * @param string $userId ID of the logged in user
 * @param int $client indicating status of client side encryption. Currently
 * unused, likely to become obsolete shortly
 */

class Util {
	
	
	// Web UI:
	
	//// DONE: files created via web ui are encrypted
	//// DONE: file created & encrypted via web ui are readable in web ui
	//// DONE: file created & encrypted via web ui are readable via webdav
	
	
	// WebDAV:
	
	//// DONE: new data filled files added via webdav get encrypted
	//// DONE: new data filled files added via webdav are readable via webdav
	//// DONE: reading unencrypted files when encryption is enabled works via 
	////       webdav
	//// DONE: files created & encrypted via web ui are readable via webdav
	
	
	// Legacy support:
	
	//// DONE: add method to check if file is encrypted using new system
	//// DONE: add method to check if file is encrypted using old system
	//// DONE: add method to fetch legacy key
	//// DONE: add method to decrypt legacy encrypted data
	
	
	// Admin UI:
	
	//// DONE: changing user password also changes encryption passphrase
	
	//// TODO: add support for optional recovery in case of lost passphrase / keys
	//// TODO: add admin optional required long passphrase for users
	//// TODO: add UI buttons for encrypt / decrypt everything
	//// TODO: implement flag system to allow user to specify encryption by folder, subfolder, etc.
	
	
	// Sharing:
	
	//// TODO: add support for encrypting to multiple public keys
	//// TODO: add support for decrypting to multiple private keys
	
	
	// Integration testing:
	
	//// TODO: test new encryption with versioning
	//// TODO: test new encryption with sharing
	//// TODO: test new encryption with proxies
	
	
	private $view; // OC_FilesystemView object for filesystem operations
	private $userId; // ID of the currently logged-in user
	private $pwd; // User Password
	private $client; // Client side encryption mode flag
	private $publicKeyDir; // Dir containing all public user keys
	private $encryptionDir; // Dir containing user's files_encryption
	private $keyfilesPath; // Dir containing user's keyfiles
	private $shareKeysPath; // Dir containing env keys for shared files
	private $publicKeyPath; // Path to user's public key
	private $privateKeyPath; // Path to user's private key

	public function __construct( \OC_FilesystemView $view, $userId, $client = false ) {
	
		$this->view = $view;
		$this->userId = $userId;
		$this->client = $client;
		$this->userDir =  '/' . $this->userId;
		$this->userFilesDir =  '/' . $this->userId . '/' . 'files';
		$this->publicKeyDir =  '/' . 'public-keys';
		$this->encryptionDir =  '/' . $this->userId . '/' . 'files_encryption';
		$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
		$this->shareKeysPath = $this->encryptionDir . '/' . 'share-keys';
		$this->publicKeyPath = $this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
		$this->privateKeyPath = $this->encryptionDir . '/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key
		
	}
	
	public function ready() {
		
		if( 
		!$this->view->file_exists( $this->encryptionDir )
		or !$this->view->file_exists( $this->keyfilesPath )
		or !$this->view->file_exists( $this->shareKeysPath )
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
		
		// Set directories to check / create
		$setUpDirs = array( 
			$this->userDir
			, $this->userFilesDir
			, $this->publicKeyDir
			, $this->encryptionDir
			, $this->keyfilesPath
			, $this->shareKeysPath
		);
		
		// Check / create all necessary dirs
		foreach ( $setUpDirs as $dirPath ) {
		
			if( !$this->view->file_exists( $dirPath ) ) {
			
				$this->view->mkdir( $dirPath );
			
			}
		
		}
		
		// Create user keypair
		if ( 
			! $this->view->file_exists( $this->publicKeyPath ) 
			or ! $this->view->file_exists( $this->privateKeyPath ) 
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
	
	public function recoveryEnabled(  ) {
	
		$sql = 'SELECT * FROM `*PREFIX*myusers` WHERE id = ?';
		$args = array(1);

		$query = \OCP\DB::prepare($sql);
		$result = $query->execute($args);

		while($row = $result->fetchRow()) {
			$userName = $row['username'];
		}	
	
	}
	
	/**
	 * @brief Find all files and their encryption status within a directory
	 * @param string $directory The path of the parent directory to search
	 * @return mixed false if 0 found, array on success. Keys: name, path
	 
	 * @note $directory needs to be a path relative to OC data dir. e.g.
	 *       /admin/files NOT /backup OR /home/www/oc/data/admin/files
	 */
	public function findEncFiles( $directory ) {
		
		// Disable proxy - we don't want files to be decrypted before
		// we handle them
		\OC_FileProxy::$enabled = false;
		
		$found = array( 'plain' => array(), 'encrypted' => array(), 'legacy' => array() );
		
		if ( 
			$this->view->is_dir( $directory ) 
			&& $handle = $this->view->opendir( $directory ) 
		) {
		
			while ( false !== ( $file = readdir( $handle ) ) ) {
				
				if (
				$file != "." 
				&& $file != ".."
				) {
					
					$filePath = $directory . '/' . $this->view->getRelativePath( '/' . $file );
					$relPath = $this->stripUserFilesPath( $filePath );
					
					// If the path is a directory, search 
					// its contents
					if ( $this->view->is_dir( $filePath ) ) { 
						
						$this->findEncFiles( $filePath );
					
					// If the path is a file, determine 
					// its encryption status
					} elseif ( $this->view->is_file( $filePath ) ) {
						
						// Disable proxies again, some-
						// where they got re-enabled :/
						\OC_FileProxy::$enabled = false;
						
						$data = $this->view->file_get_contents( $filePath );
						
						// If the file is encrypted
						// NOTE: If the userId is 
						// empty or not set, file will 
						// detected as plain
						// NOTE: This is inefficient;
						// scanning every file like this
						// will eat server resources :(
						if ( 
							Keymanager::getFileKey( $this->view, $this->userId, $file ) 
							&& Crypt::isCatfileContent( $data )
						) {
						
							$found['encrypted'][] = array( 'name' => $file, 'path' => $filePath );
						
						// If the file uses old 
						// encryption system
						} elseif (  Crypt::isLegacyEncryptedContent( $this->tail( $filePath, 3 ), $relPath ) ) {
							
							$found['legacy'][] = array( 'name' => $file, 'path' => $filePath );
							
						// If the file is not encrypted
						} else {
						
							$found['plain'][] = array( 'name' => $file, 'path' => $filePath );
						
						}
					
					}
					
				}
				
			}
			
			\OC_FileProxy::$enabled = true;
			
			if ( empty( $found ) ) {
			
				return false;
			
			} else {
				
				return $found;
			
			}
		
		}
		
		\OC_FileProxy::$enabled = true;
		
		return false;

	}
	
        /**
         * @brief Fetch the last lines of a file efficiently
         * @note Safe to use on large files; does not read entire file to memory
         * @note Derivative of http://tekkie.flashbit.net/php/tail-functionality-in-php
         */
	public function tail( $filename, $numLines ) {
		
		\OC_FileProxy::$enabled = false;
		
		$text = '';
		$pos = -1;
		$handle = $this->view->fopen( $filename, 'r' );

		while ( $numLines > 0 ) {
		
			--$pos;

			if( fseek( $handle, $pos, SEEK_END ) !== 0 ) {
			
				rewind( $handle );
				$numLines = 0;
				
			} elseif ( fgetc( $handle ) === "\n" ) {
			
				--$numLines;
				
			}

			$block_size = ( -$pos ) % 8192;
			if ( $block_size === 0 || $numLines === 0 ) {
			
				$text = fread( $handle, ( $block_size === 0 ? 8192 : $block_size ) ) . $text;
				
			}
		}

		fclose( $handle );
		
		\OC_FileProxy::$enabled = true;
		
		return $text;
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
		
		return Crypt::isCatfileContent( $data );
	
	}
	
	/**
	 * @brief Format a path to be relative to the /user/files/ directory
	 */
	public function stripUserFilesPath( $path ) {
	
		$trimmed = ltrim( $path, '/' );
		$split = explode( '/', $trimmed );
		$sliced = array_slice( $split, 2 );
		$relPath = implode( '/', $sliced );
		
		return $relPath;
	
	}
	
	/**
	 * @brief Format a shared path to be relative to the /user/files/ directory
	 * @note Expects a path like /uid/files/Shared/filepath
	 */
	public function stripSharedFilePath( $path ) {
	
		$trimmed = ltrim( $path, '/' );
		$split = explode( '/', $trimmed );
		$sliced = array_slice( $split, 3 );
		$relPath = implode( '/', $sliced );
		
		return $relPath;
	
	}
	
	public function isSharedPath( $path ) {
	
		$trimmed = ltrim( $path, '/' );
		$split = explode( '/', $trimmed );
		
		if ( $split[2] == "Shared" ) {
		
			return true;
		
		} else {
		
			return false;
		
		}
	
	}
	
	/**
	 * @brief Encrypt all files in a directory
	 * @param string $publicKey the public key to encrypt files with
	 * @param string $dirPath the directory whose files will be encrypted
	 * @note Encryption is recursive
	 */
	public function encryptAll( $publicKey, $dirPath, $legacyPassphrase = null, $newPassphrase = null ) {
	
		if ( $found = $this->findEncFiles( $dirPath ) ) {
		
			// Disable proxy to prevent file being encrypted twice
			\OC_FileProxy::$enabled = false;
		
			// Encrypt unencrypted files
			foreach ( $found['plain'] as $plainFile ) {
			
				// Open plain file handle
				
				
				// Open enc file handle
				
				
				// Read plain file in chunks
				
				
				$relPath = $this->stripUserFilesPath( $plainFile['path'] );
				
				// Open handle with for binary reading
				$plainHandle = $this->view->fopen( $plainFile['path'], 'rb' );
				// Open handle with for binary writing
				$encHandle = fopen( 'crypt://' . 'var/www/oc6/data/' . $plainFile['path'] . '.tmp', 'ab' );
				
				// Overwrite the existing file with the encrypted one
				//$this->view->file_put_contents( $plainFile['path'], $encrypted['data'] );
				$size = stream_copy_to_stream( $plainHandle, $encHandle );
				
				// Fetch the key that has just been set/updated by the stream
				$encKey = Keymanager::getFileKey( $relPath );
				
				// Save keyfile
				Keymanager::setFileKey( $this->view, $relPath, $this->userId, $encKey );
				
				// Add the file to the cache
				\OC\Files\Filesystem::putFileInfo( $plainFile['path'], array( 'encrypted'=>true, 'size' => $size ), '' );
			
			}
			
			// Encrypt legacy encrypted files
			if ( 
				! empty( $legacyPassphrase ) 
				&& ! empty( $newPassphrase ) 
			) {
			
				foreach ( $found['legacy'] as $legacyFile ) {
				
					// Fetch data from file
					$legacyData = $this->view->file_get_contents( $legacyFile['path'] );
				
					// Recrypt data, generate catfile
					$recrypted = Crypt::legacyKeyRecryptKeyfile( $legacyData, $legacyPassphrase, $publicKey, $newPassphrase );
					
					$relPath = $this->stripUserFilesPath( $legacyFile['path'] );
					
					// Save keyfile
					Keymanager::setFileKey( $this->view, $relPath, $this->userId, $recrypted['key'] );
					
					// Overwrite the existing file with the encrypted one
					$this->view->file_put_contents( $legacyFile['path'], $recrypted['data'] );
					
					$size = strlen( $recrypted['data'] );
					
					// Add the file to the cache
					\OC\Files\Filesystem::putFileInfo( $legacyFile['path'], array( 'encrypted'=>true, 'size' => $size ), '' );
				
				}
				
			}
			
			\OC_FileProxy::$enabled = true;
			
			// If files were found, return true
			return true;
		
		} else {
		
			// If no files were found, return false
			return false;
			
		}
		
	}
	
	/**
	 * @brief Return important encryption related paths
	 * @param string $pathName Name of the directory to return the path of
	 * @return string path
	 */
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
	
	/**
	 * @brief get path of a file.
	 * @param $fileId id of the file
	 * @return path of the file
	 */
	public static function fileIdToPath( $fileId ) {
	
		$query = \OC_DB::prepare( 'SELECT `path`'
				.' FROM `*PREFIX*filecache`'
				.' WHERE `fileid` = ?' );
				
		$result = $query->execute( array( $fileId ) );
		
		$row = $result->fetchRow();
		
		return substr( $row['path'], 5 );
	
	}
	
	/**
	 * @brief Filter an array of UIDs to return only ones ready for sharing
	 * @param array $unfilteredUsers users to be checked for sharing readiness
	 * @return array $userIds filtered users
	 */
	public function filterShareReadyUsers( $unfilteredUsers ) {
	
		// This array will collect the filtered IDs
		$userIds = array();
	
		// Loop through users and create array of UIDs that need new keyfiles
		foreach ( $unfilteredUsers as $user ) {
		
			$util = new Util( $this->view, $user );
				
			// Check that the user is encryption capable, or is the
			// public system user 'ownCloud' (for public shares)
			if ( 
				$util->ready() 
				or $user == 'ownCloud' 
			) {
			
				// Construct array of just UIDs for Keymanager{}
				$userIds[] = $user;
				
			} else {
					
				// Log warning; we can't do necessary setup here
				// because we don't have the user passphrase
				// TODO: Provide user feedback indicating that
				// sharing failed
				\OC_Log::write( 'Encryption library', '"'.$user.'" is not setup for encryption', \OC_Log::WARN );
		
			}
		
		}
		
		return $userIds;
		
	}
	
	/**
	 * @brief Expand given path to all sub files & folders
	 * @param Session $session
	 * @param string $path path which needs to be updated
	 * @return array $pathsArray all found file paths
	 * @note Paths of directories excluded, only *file* paths are returned
	 */
	public function getPaths( $path ) {
		
		// Default return value is success
		$result = true;
	
		// Make path include 'files' dir for OC_FSV operations
		$fPath = 'files' . $path;
		
		// If we're handling a single file
		if ( ! $this->view->is_dir( $fPath ) ) {
			
			$pathsArray[] = $path;
			
		// If we're handling a folder (recursively)
		} else {
		
			$subFiles = $this->view->getDirectoryContent( $fPath );
			
			foreach ( $subFiles as $file ) {
			
				$filePath = substr( $file['path'], 5 );
				
				// If this is a nested file
				if ( ! $this->view->is_dir( $fPath ) ) {
					
					// Add the file path to array
					$pathsArray[] = $path;
					
				} else {
				
					// If this is a nested folder
					$dirPaths = $this->getPaths( $filePath );
					
					// Add all subfiles & folders to the array
					$pathsArray = array_merge( $dirPaths, $pathsArray );
					
				}
			}
			
		}
		
		return $pathsArray;

	}
	
	/**
	 * @brief Decrypt a keyfile without knowing how it was encrypted
	 * @param string $filePath
	 * @param string $fileOwner
	 * @param string $privateKey
	 * @note Checks whether file was encrypted with openssl_seal or 
	 *       openssl_encrypt, and decrypts accrdingly
	 */
	public function decryptUnknownKeyfile( $filePath, $fileOwner, $privateKey ) {

		// Get the encrypted keyfile
		// NOTE: the keyfile format depends on how it was encrypted! At
		// this stage we don't know how it was encrypted
		$encKeyfile = Keymanager::getFileKey( $this->view, $this->userId, $filePath );
		
		// We need to decrypt the keyfile
		// Has the file been shared yet?
		if ( 
			$this->userId == $fileOwner
			&& ! Keymanager::getShareKey( $this->view, $this->userId, $filePath ) // NOTE: we can't use isShared() here because it's a post share hook so it always returns true
		) {
		
			// The file has no shareKey, and its keyfile must be 
			// decrypted conventionally
			$plainKeyfile = Crypt::keyDecrypt( $encKeyfile, $privateKey );
			
		
		} else {
			
			// The file has a shareKey and must use it for decryption
			$shareKey = Keymanager::getShareKey( $this->view, $this->userId, $filePath );
		
			$plainKeyfile = Crypt::multiKeyDecrypt( $encKeyfile, $shareKey, $privateKey );
			
		}
		
		return $plainKeyfile;

	}
	
	/**
	 * @brief Encrypt keyfile to multiple users
	 * @param array $users list of users which should be able to access the file
	 * @param string $filePath path of the file to be shared
	 */
	public function setSharedFileKeyfiles( Session $session, array $users, $filePath ) {
	
		// Make sure users are capable of sharing
		$filteredUids = $this->filterShareReadyUsers( $users );
		
		// Get public keys for each user, ready for generating sharekeys
		$userPubKeys = Keymanager::getPublicKeys( $this->view, $filteredUids ); // TODO: check this includes the owner's public key

		\OC_FileProxy::$enabled = false;

		// Get the current users's private key for decrypting existing keyfile
		$privateKey = $session->getPrivateKey();
		
		$fileOwner = \OC\Files\Filesystem::getOwner( $filePath );
		
		// Decrypt keyfile
		$plainKeyfile = $this->decryptUnknownKeyfile( $filePath, $fileOwner, $privateKey );
		
		// Re-enc keyfile to (additional) sharekeys
		$multiEncKey = Crypt::multiKeyEncrypt( $plainKeyfile, $userPubKeys );
		
		// Save the recrypted key to it's owner's keyfiles directory
		// Save new sharekeys to all necessary user directory
		// TODO: Reuse the keyfile, it it exists, instead of making a new one
		if ( 
			! Keymanager::setFileKey( $this->view, $filePath, $fileOwner, $multiEncKey['data'] )
			|| ! Keymanager::setShareKeys( $this->view, $filePath, $multiEncKey['keys'] ) 
		) {

			trigger_error( "SET Share keys failed" );

		}

		// Delete existing keyfile
		// Do this last to ensure file is recoverable in case of error
		// Keymanager::deleteFileKey( $this->view, $this->userId, $params['fileTarget'] );
	
		\OC_FileProxy::$enabled = true;

		return true;
	}

}
