<?php

/**
* ownCloud
*
* @author Sam Tuke, Robin Appelman
* @copyright 2012 Sam Tuke samtuke@owncloud.com, Robin Appelman 
* icewind1991@gmail.com
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

/**
* @brief Encryption proxy which handles filesystem operations before and after
*        execution and encrypts, and handles keyfiles accordingly. Used for 
*        webui.
*/

namespace OCA\Encryption;

class Proxy extends \OC_FileProxy {

	private static $blackList = null; //mimetypes blacklisted from encryption
	
	private static $enableEncryption = null;
	
	/**
	 * Check if a file requires encryption
	 * @param string $path
	 * @return bool
	 *
	 * Tests if server side encryption is enabled, and file is allowed by blacklists
	 */
	private static function shouldEncrypt( $path ) {
		
		if ( is_null( self::$enableEncryption ) ) {
		
			if ( 
				\OCP\Config::getAppValue( 'files_encryption', 'enable_encryption', 'true' ) == 'true' 
				&& Crypt::mode() == 'server' 
			) {
			
				self::$enableEncryption = true;
			
			} else {
				
				self::$enableEncryption = false;
			
			}
			
		}
		
		if ( !self::$enableEncryption ) {
		
			return false;
			
		}
		
		if ( is_null(self::$blackList ) ) {
		
			self::$blackList = explode(',', \OCP\Config::getAppValue( 'files_encryption', 'type_blacklist', '' ) );
			
		}
		
		if ( Crypt::isCatfileContent( $path ) ) {
		
			return true;
			
		}
		
		$extension = substr( $path, strrpos( $path, '.' ) +1 );
		
		if ( array_search( $extension, self::$blackList ) === false ) {
		
			return true;
			
		}
		
		return false;
	}
	
	public function preFile_put_contents( $path, &$data ) { 
		
		if ( self::shouldEncrypt( $path ) ) {
		
			// Stream put contents should have been converted to fopen
			if ( !is_resource( $data ) ) {
			
				$userId = \OCP\USER::getUser();
				$rootView = new \OC_FilesystemView( '/' );
				$util = new Util( $rootView, $userId );
				$session = new Session( $rootView );
				$privateKey = $session->getPrivateKey();
				$filePath = $util->stripUserFilesPath( $path );
				// Set the filesize for userland, before encrypting
				$size = strlen( $data );
				
				// Disable encryption proxy to prevent recursive calls
				\OC_FileProxy::$enabled = false;
				
				// Check if there is an existing key we can reuse
				if ( $encKeyfile = Keymanager::getFileKey( $rootView, $userId, $filePath ) ) {
					
					// Fetch shareKey
					$shareKey = Keymanager::getShareKey( $rootView, $userId, $filePath );
					
					// Decrypt the keyfile
					$plainKey = Crypt::multiKeyDecrypt( $encKeyfile, $shareKey, $privateKey );
					
// 					trigger_error("\$shareKey = $shareKey");
// 					trigger_error("\$plainKey = $plainKey");
				
				} else {
				
					// Make a new key
					$plainKey = Crypt::generateKey();
				
				}
				
				// Encrypt data
				$encData = Crypt::symmetricEncryptFileContent( $data, $plainKey );
				
				$sharingEnabled = \OCP\Share::isEnabled();
				
				$uniqueUserIds = $util->getSharingUsersArray( $sharingEnabled, $filePath );
				
				// Fetch public keys for all users who will share the file
				$publicKeys = Keymanager::getPublicKeys( $rootView, $uniqueUserIds );
				
				\OC_FileProxy::$enabled = false;
				
				// Encrypt plain keyfile to multiple sharefiles
				$multiEncrypted = Crypt::multiKeyEncrypt( $plainKey, $publicKeys );
				
				// Save sharekeys to user folders
				
				Keymanager::setShareKeys( $rootView, $filePath, $multiEncrypted['keys'] );
				
				// Set encrypted keyfile as common varname
				$encKey = $multiEncrypted['data'];
				
				// Save keyfile for newly encrypted file in parallel directory tree
				Keymanager::setFileKey( $rootView, $filePath, $userId, $encKey );

				// Replace plain content with encrypted content by reference
				$data = $encData;
				
				// Update the file cache with file info
				\OC\Files\Filesystem::putFileInfo( $path, array( 'encrypted'=>true, 'size' => $size ), '' );
				
				// Re-enable proxy - our work is done
				\OC_FileProxy::$enabled = true;
				
			}
		}
		
	}
	
	/**
	 * @param string $path Path of file from which has been read
	 * @param string $data Data that has been read from file
	 */
	public function postFile_get_contents( $path, $data ) {

		// FIXME: $path for shared files is just /uid/files/Shared/filepath
		
		$userId = \OCP\USER::getUser();
		$view = new \OC_FilesystemView( '/' );
		$util = new Util( $view, $userId );
		
		$relPath = $util->stripUserFilesPath( $path );
		
	
		// TODO check for existing key file and reuse it if possible to avoid problems with versioning etc.
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		// If data is a catfile
		if ( 
			Crypt::mode() == 'server' 
			&& Crypt::isCatfileContent( $data ) // TODO: Do we really need this check? Can't we assume it is properly encrypted?
		) {
		
			// TODO: use get owner to find correct location of key files for shared files
			$session = new Session( $view );
			$privateKey = $session->getPrivateKey( $userId );
			
			// Get the encrypted keyfile
			$encKeyfile = Keymanager::getFileKey( $view, $userId, $relPath );
			
			// Attempt to fetch the user's shareKey
			$shareKey = Keymanager::getShareKey( $view, $userId, $relPath );
			
			// Decrypt keyfile with shareKey
			$plainKeyfile = Crypt::multiKeyDecrypt( $encKeyfile, $shareKey, $privateKey );
		
			$plainData = Crypt::symmetricDecryptFileContent( $data, $plainKeyfile );
			
// 			trigger_error("PLAINDATA = ". var_export($plainData, 1));

		} elseif (
		Crypt::mode() == 'server' 
		&& isset( $_SESSION['legacyenckey'] )
		&& Crypt::isEncryptedMeta( $path ) 
		) {
			
			$plainData = Crypt::legacyDecrypt( $data, $session->getLegacyKey() );
			
		}
		
		\OC_FileProxy::$enabled = true;
		
		if ( ! isset( $plainData ) ) {
		
			$plainData = $data;
			
		}
		
		return $plainData;
		
	}
	
	/**
	 * @brief When a file is deleted, remove its keyfile also
	 */
	public function preUnlink( $path ) {
	
		// let the trashbin handle this  
		if ( \OCP\App::isEnabled('files_trashbin') ) {
		     return true;
		}
		
		$path = Keymanager::fixPartialFilePath( $path );
	
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView( '/' );

		$userId = \OCP\USER::getUser();

		$util = new Util( $view, $userId );

		// Format path to be relative to user files dir
		$relPath = $util->stripUserFilesPath( $path );

// 		list( $owner, $ownerPath ) = $util->getUidAndFilename( $relPath );

		$fileOwner = \OC\Files\Filesystem::getOwner( $path );
		$ownerPath = $util->stripUserFilesPath( $path );  // TODO: Don't trust $path, fetch owner path

		$filePath = $fileOwner . '/' . 'files_encryption' . '/' . 'keyfiles' . '/'. $ownerPath;

		// Delete keyfile & shareKey so it isn't orphaned
		if (
			! (
				Keymanager::deleteFileKey( $view, $fileOwner, $ownerPath )
				&& Keymanager::delShareKey( $view, $fileOwner, $ownerPath )
			)
		) {
		
			\OC_Log::write( 'Encryption library', 'Keyfile or shareKey could not be deleted for file "'.$filePath.'"', \OC_Log::ERROR );
				
		}
		
		\OC_FileProxy::$enabled = true;
		
		// If we don't return true then file delete will fail; better
		// to leave orphaned keyfiles than to disallow file deletion
		return true;
	
	}

	/**
	 * @brief When a file is renamed, rename its keyfile also
	 * @return bool Result of rename()
	 * @note This is pre rather than post because using post didn't work
	 */
	public function preRename( $oldPath, $newPath ) {
		
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView( '/' );
		
		$userId = \OCP\USER::getUser();
	
		// Format paths to be relative to user files dir
		$oldTrimmed = ltrim( $oldPath, '/' );
		$oldSplit = explode( '/', $oldTrimmed );
		$oldSliced = array_slice( $oldSplit, 2 );
		$oldRelPath = implode( '/', $oldSliced );
		$oldKeyfilePath = $userId . '/' . 'files_encryption' . '/' . 'keyfiles' . '/' . $oldRelPath . '.key';
		
		$newTrimmed = ltrim( $newPath, '/' );
		$newSplit = explode( '/', $newTrimmed );
		$newSliced = array_slice( $newSplit, 2 );
		$newRelPath = implode( '/', $newSliced );
		$newKeyfilePath = $userId . '/' . 'files_encryption' . '/' . 'keyfiles' . '/' . $newRelPath . '.key';
		
		// Rename keyfile so it isn't orphaned
		$result = $view->rename( $oldKeyfilePath, $newKeyfilePath );
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	
	}
	
	public function postFopen( $path, &$result ){
	
		if ( !$result ) {
		
			return $result;
			
		}
		
		// Reformat path for use with OC_FSV
		$path_split = explode( '/', $path );
		$path_f = implode( '/', array_slice( $path_split, 3 ) );
		
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		$meta = stream_get_meta_data( $result );
		
		$view = new \OC_FilesystemView( '' );
		
		$util = new Util( $view, \OCP\USER::getUser());
		
		// If file is already encrypted, decrypt using crypto protocol
		if ( 
			Crypt::mode() == 'server' 
			&& $util->isEncryptedPath( $path ) 
		) {
			
			// Close the original encrypted file
			fclose( $result );
			
			// Open the file using the crypto stream wrapper 
			// protocol and let it do the decryption work instead
			$result = fopen( 'crypt://' . $path_f, $meta['mode'] );
			
			
		} elseif ( 
			self::shouldEncrypt( $path ) 
			and $meta ['mode'] != 'r' 
			and $meta['mode'] != 'rb' 
		) {
		// If the file is not yet encrypted, but should be 
		// encrypted when it's saved (it's not read only)
		
		// NOTE: this is the case for new files saved via WebDAV
		
			if ( 
			$view->file_exists( $path ) 
			and $view->filesize( $path ) > 0 
			) {
				$x = $view->file_get_contents( $path );
				
				$tmp = tmpfile();
				
// 				// Make a temporary copy of the original file
// 				\OCP\Files::streamCopy( $result, $tmp );
// 				
// 				// Close the original stream, we'll return another one
// 				fclose( $result );
// 				
// 				$view->file_put_contents( $path_f, $tmp );
// 				
// 				fclose( $tmp );
			
			}
			
			$result = fopen( 'crypt://'.$path_f, $meta['mode'] );
		
		}
		
		// Re-enable the proxy
		\OC_FileProxy::$enabled = true;
		
		return $result;
	
	}

	public function postGetMimeType( $path, $mime ) {
		
		if ( Crypt::isCatfileContent( $path ) ) {
		
			$mime = \OCP\Files::getMimeType( 'crypt://' . $path, 'w' );
		
		}
		
		return $mime;
		
	}

	public function postStat( $path, $data ) {
	
		if ( Crypt::isCatfileContent( $path ) ) {
		
			$cached = \OC\Files\Filesystem::getFileInfo( $path, '' );
			
			$data['size'] = $cached['size'];
			
		}
		
		return $data;
	}

	public function postFileSize( $path, $size ) {
		
		// Reformat path for use with OC_FSV
		$path_split = explode( '/', $path );
		$path_f = implode( '/', array_slice( $path_split, 3 ) );
		
		if ( Crypt::isEncryptedMeta( $path_f ) ) {
			
			// Disable encryption proxy to prevent recursive calls
			\OC_FileProxy::$enabled = false;
				
			// get file info
			$cached = \OC\Files\Filesystem::getFileInfo( $path_f, '' );
			
			// calculate last chunk nr
			$lastChunckNr = floor( $size / 8192);
			
			// open stream
			$result = fopen( 'crypt://'.$path_f, "r" );
			
			// calculate last chunk position
			$lastChunckPos = ( $lastChunckNr * 8192 );
			
			// seek to end
			fseek( $result, $lastChunckPos );
			
			// get the content of the last chunck
			$lastChunkContent = fgets( $result );
			
			// calc the real filesize with the size of the last chunk
			$realSize = ( ( $lastChunckNr * 6126 ) + strlen( $lastChunkContent ) );
			
			// enable proxy
			\OC_FileProxy::$enabled = true;
			
			// set the size
			$cached['size'] = $realSize;
			
			return  $cached['size'];
		
		} else {
		
			return $size;
			
		}
	}
}
