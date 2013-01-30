<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Sam Tuke <samtuke@owncloud.com>, 2011 Robin Appelman 
 * <icewind1991@gmail.com>
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
 * transparently encrypted filestream
 *
 * you can use it as wrapper around an existing stream by setting CryptStream::$sourceStreams['foo']=array('path'=>$path,'stream'=>$stream)
 * and then fopen('crypt://streams/foo');
 */

namespace OCA\Encryption;

/**
 * @brief Provides 'crypt://' stream wrapper protocol.
 * @note We use a stream wrapper because it is the most secure way to handle 
 * decrypted content transfers. There is no safe way to decrypt the entire file
 * somewhere on the server, so we have to encrypt and decrypt blocks on the fly.
 * @note Paths used with this protocol MUST BE RELATIVE. Use URLs like:
 * crypt://filename, or crypt://subdirectory/filename, NOT 
 * crypt:///home/user/owncloud/data. Otherwise keyfiles will be put in 
 * [owncloud]/data/user/files_encryption/keyfiles/home/user/owncloud/data and 
 * will not be accessible to other methods.
 * @note Data read and written must always be 8192 bytes long, as this is the 
 * buffer size used internally by PHP. The encryption process makes the input 
 * data longer, and input is chunked into smaller pieces in order to result in 
 * a 8192 encrypted block size.
 */
class Stream {

	public static $sourceStreams = array();

	# TODO: make all below properties private again once unit testing is configured correctly
	public $rawPath; // The raw path received by stream_open
	public $path_f; // The raw path formatted to include username and data directory
	private $userId;
	private $handle; // Resource returned by fopen
	private $path;
	private $readBuffer; // For streams that dont support seeking
	private $meta = array(); // Header / meta for source stream
	private $count;
	private $writeCache;
	public $size;
	private $publicKey;
	private $keyfile;
	private $encKeyfile;
	private static $view; // a fsview object set to user dir
	private $rootView; // a fsview object set to '/'

	public function stream_open( $path, $mode, $options, &$opened_path ) {
		
		// Get access to filesystem via filesystemview object
		if ( !self::$view ) {

			self::$view = new \OC_FilesystemView( $this->userId . '/' );

		}
		
		// Set rootview object if necessary
		if ( ! $this->rootView ) {

			$this->rootView = new \OC_FilesystemView( $this->userId . '/' );

		}
		
		$this->userId = \OCP\User::getUser();
		
		// Get the bare file path
		$path = str_replace( 'crypt://', '', $path );
		
		$this->rawPath = $path;
		
		$this->path_f = $this->userId . '/files/' . $path;
		
		if ( 
		dirname( $path ) == 'streams' 
		and isset( self::$sourceStreams[basename( $path )] ) 
		) {
		
			// Is this just for unit testing purposes?

			$this->handle = self::$sourceStreams[basename( $path )]['stream'];

			$this->path = self::$sourceStreams[basename( $path )]['path'];

			$this->size = self::$sourceStreams[basename( $path )]['size'];

		} else {

			if ( 
			$mode == 'w' 
			or $mode == 'w+' 
			or $mode == 'wb' 
			or $mode == 'wb+' 
			) {

				$this->size = 0;

			} else {
				
				
				
				$this->size = self::$view->filesize( $this->path_f, $mode );
				
				//$this->size = filesize( $path );
				
			}

			// Disable fileproxies so we can open the source file without recursive encryption
			\OC_FileProxy::$enabled = false;

			//$this->handle = fopen( $path, $mode );
			
			$this->handle = self::$view->fopen( $this->path_f, $mode );
			
			\OC_FileProxy::$enabled = true;

			if ( !is_resource( $this->handle ) ) {

				\OCP\Util::writeLog( 'files_encryption', 'failed to open '.$path, \OCP\Util::ERROR );

			}

		}

		if ( is_resource( $this->handle ) ) {

			$this->meta = stream_get_meta_data( $this->handle );

		}

		return is_resource( $this->handle );

	}
	
	public function stream_seek( $offset, $whence = SEEK_SET ) {
	
		$this->flush();
		
		fseek( $this->handle, $offset, $whence );
		
	}
	
	public function stream_tell() {
		return ftell($this->handle);
	}
	
	public function stream_read( $count ) {
	
		$this->writeCache = '';

		if ( $count != 8192 ) {
			
			// $count will always be 8192 https://bugs.php.net/bug.php?id=21641
			// This makes this function a lot simpler, but will break this class if the above 'bug' gets 'fixed'
			\OCP\Util::writeLog( 'files_encryption', 'PHP "bug" 21641 no longer holds, decryption system requires refactoring', OCP\Util::FATAL );

			die();

		}

// 		$pos = ftell( $this->handle );
// 
		// Get the data from the file handle
		$data = fread( $this->handle, 8192 );
 
		if ( strlen( $data ) ) {
			
			$this->getKey();
			
			$result = Crypt::symmetricDecryptFileContent( $data, $this->keyfile );
			
		} else {

			$result = '';

		}

// 		$length = $this->size - $pos;
// 
// 		if ( $length < 8192 ) {
// 
// 			$result = substr( $result, 0, $length );
// 
// 		}

		return $result;

	}
	
	/**
	 * @brief Encrypt and pad data ready for writting to disk
	 * @param string $plainData data to be encrypted
	 * @param string $key key to use for encryption
	 * @return encrypted data on success, false on failure
	 */
	public function preWriteEncrypt( $plainData, $key ) {
		
		// Encrypt data to 'catfile', which includes IV
		if ( $encrypted = Crypt::symmetricEncryptFileContent( $plainData, $key ) ) {
		
			return $encrypted; 
			
		} else {
		
			return false;
			
		}
		
	}
	
	/**
	 * @brief Get the keyfile for the current file, generate one if necessary
	 * @param bool $generate if true, a new key will be generated if none can be found
	 * @return bool true on key found and set, false on key not found and new key generated and set
	 */
	public function getKey() {
		
		// If a keyfile already exists for a file named identically to file to be written
		if ( self::$view->file_exists( $this->userId . '/'. 'files_encryption' . '/' . 'keyfiles' . '/' . $this->rawPath . '.key' ) ) {
		
			# TODO: add error handling for when file exists but no keyfile
			
			// Fetch existing keyfile
			$this->encKeyfile = Keymanager::getFileKey( $this->rootView, $this->userId, $this->rawPath );
			
			$this->getUser();
			
			$session = new Session();
			
			$privateKey = $session->getPrivateKey( $this->userId );
			
			$this->keyfile = Crypt::keyDecrypt( $this->encKeyfile, $privateKey );
			
			return true;
			
		} else {
		
			return false;
		
		}
		
	}
	
	public function getuser() {
	
		// Only get the user again if it isn't already set
		if ( empty( $this->userId ) ) {
	
			# TODO: Move this user call out of here - it belongs elsewhere
			$this->userId = \OCP\User::getUser();
		
		}
		
		# TODO: Add a method for getting the user in case OCP\User::
		# getUser() doesn't work (can that scenario ever occur?)
		
	}
	
	/**
	 * @brief Handle plain data from the stream, and write it in 8192 byte blocks
	 * @param string $data data to be written to disk
	 * @note the data will be written to the path stored in the stream handle, set in stream_open()
	 * @note $data is only ever be a maximum of 8192 bytes long. This is set by PHP internally. stream_write() is called multiple times in a loop on data larger than 8192 bytes
	 * @note Because the encryption process used increases the length of $data, a writeCache is used to carry over data which would not fit in the required block size
	 * @note Padding is added to each encrypted block to ensure that the resulting block is exactly 8192 bytes. This is removed during stream_read
	 * @note PHP automatically updates the file pointer after writing data to reflect it's length. There is generally no need to update the poitner manually using fseek
	 */
	public function stream_write( $data ) {
		
		// Disable the file proxies so that encryption is not automatically attempted when the file is written to disk - we are handling that separately here and we don't want to get into an infinite loop
		\OC_FileProxy::$enabled = false;
		
		// Get the length of the unencrypted data that we are handling
		$length = strlen( $data );
		
		// So far this round, no data has been written
		$written = 0;
		
		// Find out where we are up to in the writing of data to the file
		$pointer = ftell( $this->handle );
		
		// Make sure the userId is set
		$this->getuser();
		
		// Get / generate the keyfile for the file we're handling
		// If we're writing a new file (not overwriting an existing one), save the newly generated keyfile
		if ( ! $this->getKey() ) {
		
			$this->keyfile = Crypt::generateKey();
			
			$this->publicKey = Keymanager::getPublicKey( $this->rootView, $this->userId );
			
			$this->encKeyfile = Crypt::keyEncrypt( $this->keyfile, $this->publicKey );
			
			// Save the new encrypted file key
			Keymanager::setFileKey( $this->rawPath, $this->encKeyfile, new \OC_FilesystemView( '/' ) );
			
			# TODO: move this new OCFSV out of here some how, use DI
			
		}

		// If extra data is left over from the last round, make sure it is integrated into the next 6126 / 8192 block
		if ( $this->writeCache ) {
			
			// Concat writeCache to start of $data
			$data = $this->writeCache . $data;
			
			// Clear the write cache, ready for resuse - it has been flushed and its old contents processed
			$this->writeCache = '';

		}
// 		
// 		// Make sure we always start on a block start
		if ( 0 != ( $pointer % 8192 ) ) { // if the current positoin of file indicator is not aligned to a 8192 byte block, fix it so that it is

// 			fseek( $this->handle, - ( $pointer % 8192 ), SEEK_CUR );
// 			
// 			$pointer = ftell( $this->handle );
// 
// 			$unencryptedNewBlock = fread( $this->handle, 8192 );
// 			
// 			fseek( $this->handle, - ( $currentPos % 8192 ), SEEK_CUR );
// 
// 			$block = Crypt::symmetricDecryptFileContent( $unencryptedNewBlock, $this->keyfile );
// 
// 			$x =  substr( $block, 0, $currentPos % 8192 );
// 
// 			$data = $x . $data;
// 			
// 			fseek( $this->handle, - ( $currentPos % 8192 ), SEEK_CUR );
// 
		}

// 		$currentPos = ftell( $this->handle );
		
// 		// While there still remains somed data to be processed & written
		while( strlen( $data ) > 0 ) {
// 			
// 			// Remaining length for this iteration, not of the entire file (may be greater than 8192 bytes)
// 			$remainingLength = strlen( $data );
// 			
// 			// If data remaining to be written is less than the size of 1 6126 byte block
			if ( strlen( $data ) < 6126 ) {
				
				// Set writeCache to contents of $data
				// The writeCache will be carried over to the next write round, and added to the start of $data to ensure that written blocks are always the correct length. If there is still data in writeCache after the writing round has finished, then the data will be written to disk by $this->flush().
				$this->writeCache = $data;

				// Clear $data ready for next round
				$data = '';
// 
			} else {
				
				// Read the chunk from the start of $data
				$chunk = substr( $data, 0, 6126 );
				
				$encrypted = $this->preWriteEncrypt( $chunk, $this->keyfile );
				
				// Write the data chunk to disk. This will be addended to the last data chunk if the file being handled totals more than 6126 bytes
				fwrite( $this->handle, $encrypted );
				
				$writtenLen = strlen( $encrypted );
				//fseek( $this->handle, $writtenLen, SEEK_CUR );

				// Remove the chunk we just processed from $data, leaving only unprocessed data in $data var, for handling on the next round
				$data = substr( $data, 6126 );

			}
		
		}

		$this->size = max( $this->size, $pointer + $length );
		
		return $length;

	}


	public function stream_set_option($option,$arg1,$arg2) {
		switch($option) {
			case STREAM_OPTION_BLOCKING:
				stream_set_blocking($this->handle,$arg1);
				break;
			case STREAM_OPTION_READ_TIMEOUT:
				stream_set_timeout($this->handle,$arg1,$arg2);
				break;
			case STREAM_OPTION_WRITE_BUFFER:
				stream_set_write_buffer($this->handle,$arg1,$arg2);
		}
	}

	public function stream_stat() {
		return fstat($this->handle);
	}
	
	public function stream_lock($mode) {
		flock($this->handle,$mode);
	}
	
	public function stream_flush() {
	
		return fflush($this->handle); // Not a typo: http://php.net/manual/en/function.fflush.php
		
	}

	public function stream_eof() {
		return feof($this->handle);
	}

	private function flush() {
		
		if ( $this->writeCache ) {
			
			// Set keyfile property for file in question
			$this->getKey();
			
			$encrypted = $this->preWriteEncrypt( $this->writeCache, $this->keyfile );
			
			fwrite( $this->handle, $encrypted );
			
			$this->writeCache = '';
		
		}
	
	}

	public function stream_close() {
	
		$this->flush();

		if ( 
		$this->meta['mode']!='r' 
		and $this->meta['mode']!='rb' 
		) {

			\OC_FileCache::put( $this->path, array( 'encrypted' => true, 'size' => $this->size ), '' );

		}

		return fclose( $this->handle );

	}

}
