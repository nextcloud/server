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
 * @note When files are deleted via webdav, or when they are updated and the
 * previous version deleted, this is handled by OC\Files\View, and thus the
 * encryption proxies are used and keyfiles deleted.
 */
class Stream {
	private $plainKey;
	private $encKeyfiles;

	private $rawPath; // The raw path relative to the data dir
	private $relPath; // rel path to users file dir
	private $userId;
	private $handle; // Resource returned by fopen
	private $meta = array(); // Header / meta for source stream
	private $writeCache;
	private $size;
	private $unencryptedSize;
	private $publicKey;
	private $encKeyfile;
	/**
	 * @var \OC\Files\View
	 */
	private $rootView; // a fsview object set to '/'
	/**
	 * @var \OCA\Encryption\Session
	 */
	private $session;
	private $privateKey;

	/**
	 * @param $path
	 * @param $mode
	 * @param $options
	 * @param $opened_path
	 * @return bool
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {

		if (!isset($this->rootView)) {
			$this->rootView = new \OC_FilesystemView('/');
		}

		$this->session = new \OCA\Encryption\Session($this->rootView);

		$this->privateKey = $this->session->getPrivateKey($this->userId);

		$util = new Util($this->rootView, \OCP\USER::getUser());

		$this->userId = $util->getUserId();

		// Strip identifier text from path, this gives us the path relative to data/<user>/files
		$this->relPath = \OC\Files\Filesystem::normalizePath(str_replace('crypt://', '', $path));

		// rawPath is relative to the data directory
		$this->rawPath = $util->getUserFilesDir() . $this->relPath;

		// Disable fileproxies so we can get the file size and open the source file without recursive encryption
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		if (
			$mode === 'w'
			or $mode === 'w+'
			or $mode === 'wb'
			or $mode === 'wb+'
		) {

			// We're writing a new file so start write counter with 0 bytes
			$this->size = 0;
			$this->unencryptedSize = 0;

		} else {

			if($this->privateKey === false) {
				// if private key is not valid redirect user to a error page
				\OCA\Encryption\Helper::redirectToErrorPage();
			}

			$this->size = $this->rootView->filesize($this->rawPath, $mode);
		}

		$this->handle = $this->rootView->fopen($this->rawPath, $mode);

		\OC_FileProxy::$enabled = $proxyStatus;

		if (!is_resource($this->handle)) {

			\OCP\Util::writeLog('Encryption library', 'failed to open file "' . $this->rawPath . '"', \OCP\Util::ERROR);

		} else {

			$this->meta = stream_get_meta_data($this->handle);

		}


		return is_resource($this->handle);

	}

	/**
	 * @param $offset
	 * @param int $whence
	 */
	public function stream_seek($offset, $whence = SEEK_SET) {

		$this->flush();

		fseek($this->handle, $offset, $whence);

	}

	/**
	 * @param $count
	 * @return bool|string
	 * @throws \Exception
	 */
	public function stream_read($count) {

		$this->writeCache = '';

		if ($count !== 8192) {

			// $count will always be 8192 https://bugs.php.net/bug.php?id=21641
			// This makes this function a lot simpler, but will break this class if the above 'bug' gets 'fixed'
			\OCP\Util::writeLog('Encryption library', 'PHP "bug" 21641 no longer holds, decryption system requires refactoring', \OCP\Util::FATAL);

			die();

		}

		// Get the data from the file handle
		$data = fread($this->handle, 8192);

		$result = null;

		if (strlen($data)) {

			if (!$this->getKey()) {

				// Error! We don't have a key to decrypt the file with
				throw new \Exception(
					'Encryption key not found for "' . $this->rawPath . '" during attempted read via stream');

			} else {

				// Decrypt data
				$result = Crypt::symmetricDecryptFileContent($data, $this->plainKey);
			}

		}

		return $result;

	}

	/**
	 * @brief Encrypt and pad data ready for writing to disk
	 * @param string $plainData data to be encrypted
	 * @param string $key key to use for encryption
	 * @return string encrypted data on success, false on failure
	 */
	public function preWriteEncrypt($plainData, $key) {

		// Encrypt data to 'catfile', which includes IV
		if ($encrypted = Crypt::symmetricEncryptFileContent($plainData, $key)) {

			return $encrypted;

		} else {

			return false;

		}

	}

	/**
	 * @brief Fetch the plain encryption key for the file and set it as plainKey property
	 * @internal param bool $generate if true, a new key will be generated if none can be found
	 * @return bool true on key found and set, false on key not found and new key generated and set
	 */
	public function getKey() {

		// Check if key is already set
		if (isset($this->plainKey) && isset($this->encKeyfile)) {

			return true;

		}

		// Fetch and decrypt keyfile
		// Fetch existing keyfile
		$this->encKeyfile = Keymanager::getFileKey($this->rootView, $this->userId, $this->relPath);

		// If a keyfile already exists
		if ($this->encKeyfile) {

			// if there is no valid private key return false
			if ($this->privateKey === false) {

				// if private key is not valid redirect user to a error page
				\OCA\Encryption\Helper::redirectToErrorPage();

				return false;
			}

			$shareKey = Keymanager::getShareKey($this->rootView, $this->userId, $this->relPath);

			$this->plainKey = Crypt::multiKeyDecrypt($this->encKeyfile, $shareKey, $this->privateKey);

			return true;

		} else {

			return false;

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
	public function stream_write($data) {

		// if there is no valid private key return false
		if ($this->privateKey === false) {
			$this->size = 0;
			return strlen($data);
		}

		// Disable the file proxies so that encryption is not 
		// automatically attempted when the file is written to disk - 
		// we are handling that separately here and we don't want to 
		// get into an infinite loop
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// Get the length of the unencrypted data that we are handling
		$length = strlen($data);

		// Find out where we are up to in the writing of data to the
		// file
		$pointer = ftell($this->handle);

		// Get / generate the keyfile for the file we're handling
		// If we're writing a new file (not overwriting an existing 
		// one), save the newly generated keyfile
		if (!$this->getKey()) {

			$this->plainKey = Crypt::generateKey();

		}

		// If extra data is left over from the last round, make sure it 
		// is integrated into the next 6126 / 8192 block
		if ($this->writeCache) {

			// Concat writeCache to start of $data
			$data = $this->writeCache . $data;

			// Clear the write cache, ready for reuse - it has been
			// flushed and its old contents processed
			$this->writeCache = '';

		}

		// While there still remains some data to be processed & written
		while (strlen($data) > 0) {

			// Remaining length for this iteration, not of the
			// entire file (may be greater than 8192 bytes)
			$remainingLength = strlen($data);

			// If data remaining to be written is less than the
			// size of 1 6126 byte block
			if ($remainingLength < 6126) {

				// Set writeCache to contents of $data
				// The writeCache will be carried over to the 
				// next write round, and added to the start of 
				// $data to ensure that written blocks are 
				// always the correct length. If there is still 
				// data in writeCache after the writing round 
				// has finished, then the data will be written 
				// to disk by $this->flush().
				$this->writeCache = $data;

				// Clear $data ready for next round
				$data = '';

			} else {

				// Read the chunk from the start of $data
				$chunk = substr($data, 0, 6126);

				$encrypted = $this->preWriteEncrypt($chunk, $this->plainKey);

				// Write the data chunk to disk. This will be 
				// attended to the last data chunk if the file
				// being handled totals more than 6126 bytes
				fwrite($this->handle, $encrypted);

				// Remove the chunk we just processed from
				// $data, leaving only unprocessed data in $data
				// var, for handling on the next round
				$data = substr($data, 6126);

			}

		}

		$this->size = max($this->size, $pointer + $length);
		$this->unencryptedSize += $length;

		\OC_FileProxy::$enabled = $proxyStatus;

		return $length;

	}


	/**
	 * @param $option
	 * @param $arg1
	 * @param $arg2
	 */
	public function stream_set_option($option, $arg1, $arg2) {
		$return = false;
		switch ($option) {
			case STREAM_OPTION_BLOCKING:
				$return = stream_set_blocking($this->handle, $arg1);
				break;
			case STREAM_OPTION_READ_TIMEOUT:
				$return = stream_set_timeout($this->handle, $arg1, $arg2);
				break;
			case STREAM_OPTION_WRITE_BUFFER:
				$return = stream_set_write_buffer($this->handle, $arg1);
		}

		return $return;
	}

	/**
	 * @return array
	 */
	public function stream_stat() {
		return fstat($this->handle);
	}

	/**
	 * @param $mode
	 */
	public function stream_lock($mode) {
		return flock($this->handle, $mode);
	}

	/**
	 * @return bool
	 */
	public function stream_flush() {

		return fflush($this->handle);
		// Not a typo: http://php.net/manual/en/function.fflush.php

	}

	/**
	 * @return bool
	 */
	public function stream_eof() {
		return feof($this->handle);
	}

	private function flush() {

		if ($this->writeCache) {

			// Set keyfile property for file in question
			$this->getKey();

			$encrypted = $this->preWriteEncrypt($this->writeCache, $this->plainKey);

			fwrite($this->handle, $encrypted);

			$this->writeCache = '';

		}

	}

	/**
	 * @return bool
	 */
	public function stream_close() {

		$this->flush();

		// if there is no valid private key return false
		if ($this->privateKey === false) {

				// cleanup
				if ($this->meta['mode'] !== 'r' && $this->meta['mode'] !== 'rb') {

					// Disable encryption proxy to prevent recursive calls
					$proxyStatus = \OC_FileProxy::$enabled;
					\OC_FileProxy::$enabled = false;

					if ($this->rootView->file_exists($this->rawPath) && $this->size === 0) {
						$this->rootView->unlink($this->rawPath);
					}

					// Re-enable proxy - our work is done
					\OC_FileProxy::$enabled = $proxyStatus;
				}

			// if private key is not valid redirect user to a error page
			\OCA\Encryption\Helper::redirectToErrorPage();
		}

		if (
			$this->meta['mode'] !== 'r'
			and $this->meta['mode'] !== 'rb'
				and $this->size > 0
		) {
			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			// Fetch user's public key
			$this->publicKey = Keymanager::getPublicKey($this->rootView, $this->userId);

			// Check if OC sharing api is enabled
			$sharingEnabled = \OCP\Share::isEnabled();

			$util = new Util($this->rootView, $this->userId);

			// Get all users sharing the file includes current user
			$uniqueUserIds = $util->getSharingUsersArray($sharingEnabled, $this->relPath, $this->userId);

			// Fetch public keys for all sharing users
			$publicKeys = Keymanager::getPublicKeys($this->rootView, $uniqueUserIds);

			// Encrypt enc key for all sharing users
			$this->encKeyfiles = Crypt::multiKeyEncrypt($this->plainKey, $publicKeys);

			// Save the new encrypted file key
			Keymanager::setFileKey($this->rootView, $this->relPath, $this->userId, $this->encKeyfiles['data']);

			// Save the sharekeys
			Keymanager::setShareKeys($this->rootView, $this->relPath, $this->encKeyfiles['keys']);

			// get file info
			$fileInfo = $this->rootView->getFileInfo($this->rawPath);
			if (!is_array($fileInfo)) {
				$fileInfo = array();
			}

			// Re-enable proxy - our work is done
			\OC_FileProxy::$enabled = $proxyStatus;

			// set encryption data
			$fileInfo['encrypted'] = true;
			$fileInfo['size'] = $this->size;
			$fileInfo['unencrypted_size'] = $this->unencryptedSize;

			// set fileinfo
			$this->rootView->putFileInfo($this->rawPath, $fileInfo);
		}

		return fclose($this->handle);

	}

}
