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
 * transparent encryption
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
		
			self::$enableEncryption = ( \OCP\Config::getAppValue( 'files_encryption', 'enable_encryption', 'true' ) == 'true' && Crypt::mode() == 'server' );
			
		}
		
		if( !self::$enableEncryption ) {
		
			return false;
			
		}
		
		if( is_null(self::$blackList ) ) {
		
			self::$blackList = explode(',', \OCP\Config::getAppValue( 'files_encryption','type_blacklist','jpg,png,jpeg,avi,mpg,mpeg,mkv,mp3,oga,ogv,ogg' ) );
			
		}
		
		if( Crypt::isEncryptedContent( $path ) ) {
		
			return true;
			
		}
		
		$extension = substr( $path, strrpos( $path,'.' ) +1 );
		
		if ( array_search( $extension, self::$blackList ) === false ){
		
			return true;
			
		}
		
		return false;
	}

	/**
	 * Check if a file is encrypted according to database file cache
	 * @param string $path
	 * @return bool
	 */
	private static function isEncrypted( $path ){
	
		// Fetch all file metadata from DB
		$metadata = \OC_FileCache_Cached::get( $path, '' );
		
		// Return encryption status
		return isset( $metadata['encrypted'] ) and ( bool )$metadata['encrypted'];
	
	}
	
	public function preFile_put_contents( $path, &$data ) {
		
		if ( self::shouldEncrypt( $path ) ) {
		
			if ( !is_resource( $data ) ) { //stream put contents should have been converter to fopen
			
				// Set the filesize for userland, before encrypting
				$size = strlen( $data );
				
				// Disable encryption proxy to prevent recursive calls
				\OC_FileProxy::$enabled = false;
				
				// Encrypt plain data and fetch key
				$encrypted = Crypt::keyEncryptKeyfile( $data, Keymanager::getPublicKey() );
				
				// Replace plain content with encrypted content by reference
				$data = $encrypted['data'];
				
				$filePath = explode( '/', $path );
				
				$filePath = array_slice( $filePath, 3 );
				
				$filePath = '/' . implode( '/', $filePath );
				
				# TODO: make keyfile dir dynamic from app config
				$view = new \OC_FilesystemView( '/' . \OCP\USER::getUser() . '/files_encryption/keyfiles' );
				
				// Save keyfile for newly encrypted file in parallel directory tree
				Keymanager::setFileKey( $filePath, $encrypted['key'], $view, '\OC_DB' );
				
				// Update the file cache with file info
				\OC_FileCache::put( $path, array( 'encrypted'=>true, 'size' => $size ), '' );
				
				// Re-enable proxy - our work is done
				\OC_FileProxy::$enabled = true;
				
			}
		}
	}
	
	public function postFile_get_contents( $path, $data ) {
	
		# TODO: Use dependency injection to add required args for view and user etc. to this method
	
		if ( Crypt::mode() == 'server' && Crypt::isEncryptedContent( $data ) ) {
		
			$filePath = explode( '/', $path );
			
			$filePath = array_slice( $filePath, 3 );
			
			$filePath = '/' . implode( '/', $filePath );
			
			$cached = \OC_FileCache_Cached::get( $path, '' );
			
			// Disable encryption proxy to prevent recursive calls
			\OC_FileProxy::$enabled = false;
			
			$keyFile = Keymanager::getFileKey( $filePath );
			
			$data = Crypt::keyDecryptKeyfile( $data, $keyFile, $_SESSION['enckey'] );
			
			\OC_FileProxy::$enabled = true;
		
		}
		
		return $data;
		
	}
	
	public function postFopen( $path, &$result ){
	
		if ( !$result ) {
		
			return $result;
			
		}
		
		// Disable encryption proxy to prevent recursive calls
		\OC_FileProxy::$enabled = false;
		
		$meta = stream_get_meta_data( $result );
		
		$view = new \OC_FilesystemView();
		
		$util = new Util( $view, \OCP\USER::getUser());
		
		// If file is encrypted, decrypt using crypto protocol
		if ( Crypt::mode() == 'server' && $util->isEncryptedPath( $path ) ) {
		
			file_put_contents('/home/samtuke/newtmp.txt', "bar" );
		
			$tmp = fopen( 'php://temp' );
			
			\OCP\Files::streamCopy( $result, $tmp );
			
			fclose( $result );
			
			\OC_Filesystem::file_put_contents( $path, $tmp );
			
			fclose( $tmp );
			
			$result = fopen( 'crypt://' . $path, $meta['mode'] );
		
// 			file_put_contents('/home/samtuke/newtmp.txt', "mode= server" );
		
// 			$keyFile = Keymanager::getFileKey( $filePath );
// 		
// 			$tmp = tmpfile();
// 			
// 			file_put_contents( $tmp, Crypt::keyDecryptKeyfile( $result, $keyFile, $_SESSION['enckey'] ) );
// 		
// 			fclose ( $result );
// 			
// 			$result = fopen( $tmp );
			
		} /*elseif ( 
		self::shouldEncrypt( $path ) 
		and $meta ['mode'] != 'r' 
		and $meta['mode'] != 'rb' 
		) {
		
		# TODO: figure out what this does
		
			if ( 
			\OC_Filesystem::file_exists( $path ) 
			and \OC_Filesystem::filesize( $path ) > 0 
			) {
			
				//first encrypt the target file so we don't end up with a half encrypted file
				\OCP\Util::writeLog( 'files_encryption', 'Decrypting '.$path.' before writing', \OCP\Util::DEBUG );
				
				$tmp = fopen( 'php://temp' );
				
				\OCP\Files::streamCopy( $result, $tmp );
				
				// Close the original stream, we'll return another one
				fclose( $result );
				
				\OC_Filesystem::file_put_contents( $path, $tmp );
				
				fclose( $tmp );
			
			}
			
			$result = fopen( 'crypt://'.$path, $meta['mode'] );
		
		}*/
		
		return $result;
	
	}

	public function postGetMimeType($path,$mime){
		if( Crypt::isEncryptedContent($path)){
			$mime = \OCP\Files::getMimeType('crypt://'.$path,'w');
		}
		return $mime;
	}

	public function postStat($path,$data){
		if( Crypt::isEncryptedContent($path)){
			$cached=  \OC_FileCache_Cached::get($path,'');
			$data['size']=$cached['size'];
		}
		return $data;
	}

	public function postFileSize($path,$size){
		if( Crypt::isEncryptedContent($path)){
			$cached = \OC_FileCache_Cached::get($path,'');
			return  $cached['size'];
		}else{
			return $size;
		}
	}
}
