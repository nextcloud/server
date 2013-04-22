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
		
			self::$blackList = explode(',', \OCP\Config::getAppValue( 'files_encryption', 'type_blacklist', 'jpg,png,jpeg,avi,mpg,mpeg,mkv,mp3,oga,ogv,ogg' ) );
			
		}
		
		if ( Crypt::isCatfile( $path ) ) {
		
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
		
			if ( !is_resource( $data ) ) { //stream put contents should have been converted to fopen
			
				$userId = \OCP\USER::getUser();
				
				$rootView = new \OC_FilesystemView( '/' );
			
				// Set the filesize for userland, before encrypting
				$size = strlen( $data );
				
				// Disable encryption proxy to prevent recursive calls
				\OC_FileProxy::$enabled = false;
				
				// TODO: Check if file is shared, if so, use multiKeyEncrypt
				
				// Encrypt plain data and fetch key
				$encrypted = Crypt::keyEncryptKeyfile( $data, Keymanager::getPublicKey( $rootView, $userId ) );
				
				// Replace plain content with encrypted content by reference
				$data = $encrypted['data'];
				
				$filePath = explode( '/', $path );
				
				$filePath = array_slice( $filePath, 3 );
				
				$filePath = '/' . implode( '/', $filePath );
				
				// TODO: make keyfile dir dynamic from app config
				
				$view = new \OC_FilesystemView( '/' );
				
				// Save keyfile for newly encrypted file in parallel directory tree
				Keymanager::setFileKey( $view, $filePath, $userId, $encrypted['key'] );
				
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
	
		// TODO: Use dependency injection to add required args for view and user etc. to this method

		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		// If data is a catfile
		if ( 
			Crypt::mode() == 'server' 
			&& Crypt::isCatfile( $data ) 
		) {
			
			$split = explode( '/', $path );
			
			$filePath = array_slice( $split, 3 );
			
			$filePath = '/' . implode( '/', $filePath );
			
			//$cached = \OC\Files\Filesystem::getFileInfo( $path, '' );
			
			$view = new \OC_FilesystemView( '' );
			
			$userId = \OCP\USER::getUser();
			
			// TODO: Check if file is shared, if so, use multiKeyDecrypt
			
			$encryptedKeyfile = Keymanager::getFileKey( $view, $userId, $filePath );

			$session = new Session();
			
			$decrypted = Crypt::keyDecryptKeyfile( $data, $encryptedKeyfile, $session->getPrivateKey( $split[1] ) );
			
		} elseif (
		Crypt::mode() == 'server' 
		&& isset( $_SESSION['legacyenckey'] )
		&& Crypt::isEncryptedMeta( $path ) 
		) {
			
			$decrypted = Crypt::legacyDecrypt( $data, $_SESSION['legacyenckey'] );
			
		}
		
		\OC_FileProxy::$enabled = true;
		
		if ( ! isset( $decrypted ) ) {
		
			$decrypted = $data;
			
		}
		
		return $decrypted;
		
	}
	
	/**
	 * @brief When a file is deleted, remove its keyfile also
	 */
	public function preUnlink( $path ) {
	
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView( '/' );
		
		$userId = \OCP\USER::getUser();
		
		// Format path to be relative to user files dir
		$trimmed = ltrim( $path, '/' );
		$split = explode( '/', $trimmed );
		$sliced = array_slice( $split, 2 );
		$relPath = implode( '/', $sliced );
		
		if ( $view->is_dir( $path ) ) {
			
			// Dirs must be handled separately as deleteFileKey 
			// doesn't handle them
			$view->unlink( $userId . '/' . 'files_encryption' . '/' . 'keyfiles' . '/'. $relPath );
			
		} else {
		
			// Delete keyfile so it isn't orphaned
			$result = Keymanager::deleteFileKey( $view, $userId, $relPath );
		
			\OC_FileProxy::$enabled = true;
			
			return $result;
		
		}
	
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
		$path_f = implode( array_slice( $path_split, 3 ) );
		
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
		
		if ( Crypt::isCatfile( $path ) ) {
		
			$mime = \OCP\Files::getMimeType( 'crypt://' . $path, 'w' );
		
		}
		
		return $mime;
		
	}

	public function postStat( $path, $data ) {
	
		if ( Crypt::isCatfile( $path ) ) {
		
			$cached = \OC\Files\Filesystem::getFileInfo( $path, '' );
			
			$data['size'] = $cached['size'];
			
		}
		
		return $data;
	}

	public function postFileSize( $path, $size ) {
		
		if ( Crypt::isCatfile( $path ) ) {
			
			$cached = \OC\Files\Filesystem::getFileInfo( $path, '' );
			
			return  $cached['size'];
		
		} else {
		
			return $size;
			
		}
	}
}
