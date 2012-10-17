<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Sam Tuke samtuke@owncloud.com, 2011 Robin Appelman icewind1991@gmail.com
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
 *   and then fopen('crypt://streams/foo');
 */

namespace OCA\Encryption;

/**
 * @brief Provides 'crypt://' stream wrapper protocol.
 * @note Paths used with this protocol MUST BE RELATIVE, due to limitations of OC_FilesystemView. crypt:///home/user/owncloud/data <- will put keyfiles in [owncloud]/data/user/files_encryption/keyfiles/home/user/owncloud/data and will not be accessible by other functions.
 * @note Data read and written must always be 8192 bytes long, as this is the buffer size used internally by PHP. The encryption process makes the input data longer, and input is chunked into smaller pieces in order to result in a 8192 encrypted block size.
 */
class Stream {

	public static $sourceStreams = array();

	# TODO: make all below properties private again once unit testing is configured correctly
	public $rawPath; // The raw path received by stream_open
	private $handle; // Resource returned by fopen
	private $path;
	private $readBuffer; // For streams that dont support seeking
	private $meta = array(); // Header / meta for source stream
	private $count;
	private $writeCache;
	public $size;
	private $keyfile;
	private static $view;

	public function stream_open( $path, $mode, $options, &$opened_path ) {
	
		// Get access to filesystem via filesystemview object
		if ( !self::$view ) {

			self::$view = new \OC_FilesystemView( '' );

		}
		
		// Get the bare file path
		$path = str_replace( 'crypt://', '', $path );
		
		$this->rawPath = $path;
		
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
				
				
				
				$this->size = self::$view->filesize( $path, $mode );
				
				//$this->size = filesize( $path );
				
			}

			// Disable fileproxies so we can open the source file without recursive encryption
			\OC_FileProxy::$enabled = false;

			//$this->handle = fopen( $path, $mode );
			
			$this->handle = self::$view->fopen( $path, $mode );
			
			//file_put_contents('/home/samtuke/newtmp.txt', 'fucking hopeless = '.$path );
			
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
	
	trigger_error("\$count = $count");
	
	file_put_contents('/home/samtuke/newtmp.txt', "\$count = $count" );
	
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
		
		//echo "\n\nPRE DECRYPTION = $data\n\n";
// 
// 		if ( strlen( $data ) ) {
			
			$this->getKey();
			
			//$key = file_get_contents( '/home/samtuke/owncloud/git/oc3/data/admin/files_encryption/keyfiles/tmp-1346255589.key' );
			
			$result = Crypt::symmetricDecryptFileContent( $data, $this->keyfile );
			
// 			file_put_contents('/home/samtuke/newtmp.txt', '$result = '.$result );
			
// 			echo "\n\n\n\n-----------------------------\n\nNEWS";
// 			
// 			echo "\n\n\$data = $data";
// 			
// 			echo "\n\n\$key = {$this->keyfile}";
// 			
// 			echo "\n\n\$result = $result";
// 			
// 			echo "\n\n\n\n-----------------------------\n\n";
			
			//trigger_error("CAT  $result");

			
			
// 		} else {
// 
// 			$result = '';
// 
// 		}

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
	public function getKey( $generate = true ) {
	
		# TODO: Move this user call out of here - it belongs elsewhere
		$user = \OCP\User::getUser();
		
		//echo "\n\$this->rawPath = {$this->rawPath}";
		
		// If a keyfile already exists for a file named identically to file to be written
		if ( self::$view->file_exists( $user . '/'. 'files_encryption' . '/' . 'keyfiles' . '/' . $this->rawPath . '.key' ) ) {
		
			# TODO: add error handling for when file exists but no keyfile
			
			// Fetch existing keyfile
			$this->keyfile = Keymanager::getFileKey( $this->rawPath );
			
			return true;
			
		} else {
		
			if ( $generate ) {
				
				// If the data is to be written to a new file, generate a new keyfile
				$this->keyfile = Crypt::generateKey();
				
				return false;
				
			}
			
		}
		
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
		
		//file_put_contents('/home/samtuke/newtmp.txt', 'stream_write('.$data.')' );
		
		// Disable the file proxies so that encryption is not automatically attempted when the file is written to disk - we are handling that separately here and we don't want to get into an infinite loop
		\OC_FileProxy::$enabled = false;
		
		// Get the length of the unencrypted data that we are handling
		$length = strlen( $data );
		
		// So far this round, no data has been written
		$written = 0;
		
		// Find out where we are up to in the writing of data to the file
		$pointer = ftell( $this->handle );
		
		//echo "\n\n\$rawLength = $length\n";
		
		//echo "\$pointer = $pointer\n";
		
		# TODO: Move this user call out of here - it belongs elsewhere
		$user = \OCP\User::getUser();
		
		// Get / generate the keyfile for the file we're handling
		// If we're writing a new file (not overwriting an existing one), save the newly generated keyfile
		if ( ! $this->getKey() ) {
			
			// Save keyfile in parallel directory structure
			Keymanager::setFileKey( $this->rawPath, $this->keyfile, new \OC_FilesystemView( '/' ) );
			
		}

		// If extra data is left over from the last round, make sure it is integrated into the next 6126 / 8192 block
		if ( $this->writeCache ) {
			
			// Concat writeCache to start of $data
			$data = $this->writeCache . $data;
			
			//echo "\n\ncache + data length = ".strlen($data)."\n";
			
			// Clear the write cache, ready for resuse - it has been flushed and its old contents processed
			$this->writeCache = '';

		}
// 		
// 		// Make sure we always start on a block start
		if ( 0 != ( $pointer % 8192 ) ) { // if the current positoin of file indicator is not aligned to a 8192 byte block, fix it so that it is
// 		
			//echo "\n\nNOT ON BLOCK START ";
// 			echo $pointer % 8192;
// 			
// 			echo "\n\n1. $currentPos\n\n";
// // 			
// 			echo "ftell() = ".ftell($this->handle)."\n";

// 			fseek( $this->handle, - ( $pointer % 8192 ), SEEK_CUR );
// 			
// 			$pointer = ftell( $this->handle );
			
// 			echo "ftell() = ".ftell($this->handle)."\n";
// 
// 			$unencryptedNewBlock = fread( $this->handle, 8192 );
// 
// 			echo "\n\n2. $currentPos\n\n";
// 			
// 			fseek( $this->handle, - ( $currentPos % 8192 ), SEEK_CUR );
// 			
// 			echo "\n\n3. $currentPos\n\n";
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
				
				//echo "\n\nbefore before ".strlen($data)."\n";
				
				// Read the chunk from the start of $data
				$chunk = substr( $data, 0, 6126 );
				
				//echo "before ".strlen($data)."\n";
				
				//echo "\n\$this->keyfile 1 = {$this->keyfile}";
				
				$encrypted = $this->preWriteEncrypt( $chunk, $this->keyfile );
				
				//echo "\n\n\$rawEnc = $encrypted\n\n";
				
				//echo "\$encrypted = ".strlen($encrypted)."\n";
				
				//echo "written = ".strlen($encrypted)."\n";
				
				//echo "after ".strlen($encrypted)."\n\n";
				
				//file_put_contents('/home/samtuke/tmp.txt', $encrypted);
				
				// Write the data chunk to disk. This will be addended to the last data chunk if the file being handled totals more than 6126 bytes
				fwrite( $this->handle, $encrypted );
				
				//$bef = ftell( $this->handle );
				//echo "ftell before = $bef\n";
				
				$writtenLen = strlen( $encrypted );
				//fseek( $this->handle, $writtenLen, SEEK_CUR );
				
// 				$aft = ftell( $this->handle );
// 				echo "ftell after = $aft\n";
// 				echo "ftell sum = ";
// 				echo $aft - $bef."\n";

				// Remove the chunk we just processed from $data, leaving only unprocessed data in $data var, for handling on the next round
				$data = substr( $data, 6126 );

			}
		
		}

		$this->size = max( $this->size, $pointer + $length );
		
		//echo "\$this->size = $this->size\n\n";
		
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

		if ($this->meta['mode']!='r' and $this->meta['mode']!='rb') {

			\OC_FileCache::put($this->path,array('encrypted'=>true,'size'=>$this->size),'');

		}

		return fclose($this->handle);

	}

}
