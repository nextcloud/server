<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2011 Robin Appelman icewind1991@gmail.com
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

namespace OCA_Encryption;

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

				//$this->size = self::$view->filesize( $path, $mode );

				$this->size = filesize( $path );
				
			}

			// Disable fileproxies so we can open the source file without recursive encryption
			\OC_FileProxy::$enabled = false;

			$this->handle = fopen( $path, $mode );
			
			//$this->handle = self::$view->fopen( $path, $mode );

			\OC_FileProxy::$enabled = true;

			if ( !is_resource( $this->handle ) ) {

				\OCP\Util::writeLog( 'files_encryption','failed to open '.$path,OCP\Util::ERROR );

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
		$data = fread( $this->handle, 8192 );
		
		//echo "\n\nPRE DECRYPTION = $data\n\n";
// 
// 		if ( strlen( $data ) ) {
			
			$this->getKey();
			
			echo "\n\nGROWL {$this->keyfile}\n\n";
			
			$key = file_get_contents( '/home/samtuke/owncloud/git/oc3/data/admin/files_encryption/keyfiles/tmp-1346255589.key' );
			
			$result = Crypt::symmetricDecryptFileContent( $data, $this->keyfile );
			
			echo "\n\n\n\n-----------------------------\n\nNEWS";
			
			echo "\n\n\$data = $data";
			
			echo "\n\n\$key = $key";
			
			echo "\n\n\$result = $result";
			
			echo "\n\n\n\n-----------------------------\n\n";
			
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
	 * @brief Get the keyfile for the current file, generate one if necessary
	 * @param bool $generate if true, a new key will be generated if none can be found
	 */
	public function getKey( $generate = true ) {
	
		# TODO: Move this user call out of here - it belongs elsewhere
		$user = \OCP\User::getUser();
		
		if ( self::$view->file_exists( $this->rawPath ) ) {
			
			# TODO: add error handling for when file exists but no keyfile
			
			// If the data is to be written to an existing file, fetch its keyfile
			$this->keyfile = Keymanager::getFileKey( $this->rawPath );
			
		} else {
		
			if ( $generate ) {
		
				// If the data is to be written to a new file, generate a new keyfile
				$this->keyfile = Crypt::generateKey();
				
			}
			
		}
		
	}
	
	/**
	 * @brief Take plain data destined to be written, encrypt it, and write it block by block
	 */
	public function stream_write( $data ) {
		
		\OC_FileProxy::$enabled = false;
		
		$length = strlen( $data );

		$written = 0;

		$currentPos = ftell( $this->handle );
		
		# TODO: Move this user call out of here - it belongs elsewhere
		$user = \OCP\User::getUser();
		
		// Set keyfile property for file in question
		$this->getKey();
		
		if ( ! self::$view->file_exists( $this->rawPath . $user ) ) {
			
			// Save keyfile in parallel directory structure
			Keymanager::setFileKey( $this->rawPath, $this->keyfile, new \OC_FilesystemView( '/' ) );
			
		}

// 		// If data exists in the writeCache
// 		if ( $this->writeCache ) {
// 		
// 			trigger_error("write cache is set");
// 			
// 			// Concat writeCache to start of $data
// 			$data = $this->writeCache . $data;
// 
// 			$this->writeCache = '';
// 
// 		}
// 		
// 		// Make sure we always start on a block start
// 		if ( 0 != ( $currentPos % 8192 ) ) { // If we're not at the end of file yet (in the final chunk), if there will be no bytes left to read after the current chunk
// 
// 			fseek( $this->handle, - ( $currentPos % 8192 ), SEEK_CUR );
// 
// 			$encryptedBlock = fread( $this->handle, 8192 );
// 
// 			fseek( $this->handle, - ( $currentPos % 8192 ), SEEK_CUR );
// 
// 			$block = Crypt::symmetricDecryptFileContent( $encryptedBlock, $this->keyfile );
// 
// 			$x =  substr( $block, 0, $currentPos % 8192 );
// 
// 			$data = $x . $data;
// 			
// 			fseek( $this->handle, - ( $currentPos % 8192 ), SEEK_CUR );
// 
// 		}
/*
		$currentPos = ftell( $this->handle );*/
		
// 		// While there still remains somed data to be written
// 		while( strlen( $data ) > 0 ) {
// 			
// 			$remainingLength = strlen( $data );
// 			
// 			// If data remaining to be written is less than the size of 1 block
// 			if ( $remainingLength < 8192 ) {
// 			
// 				//trigger_error("remaining length < 8192");
// 				
// 				// Set writeCache to contents of $data
// 				$this->writeCache = $data;
// 
// 				$data = '';
// 
// 			} else {
				
				$encrypted = Crypt::symmetricEncryptFileContent( $data, $this->keyfile );
				
				file_put_contents('/home/samtuke/tmp.txt', $encrypted);
				
				//echo "\n\nFRESHLY ENCRYPTED = $encrypted\n\n";
				
				fwrite( $this->handle, $encrypted );

				$data = substr( $data, 8192 );

// 			}
// 
// 		}

		$this->size = max( $this->size, $currentPos + $length );

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
			
			//echo "\n\nFLUSH = {$this->writeCache}\n\n";
			
			$encrypted = Crypt::symmetricBlockEncryptFileContent( $this->writeCache, $this->keyfile );
			
			//echo "\n\nENCFLUSH = $encrypted\n\n";
			
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
