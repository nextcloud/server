<?php
/**
 * Copyright (c) 2014 Andreas Fischer <bantu@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

/**
 * Helper class for large files on 32-bit platforms.
 */
class LargeFileHelper {
	/**
	* @brief Tries to get the filesize of a file via various workarounds that
	*        even work for large files on 32-bit platforms.
	*
	* @param string $filename Path to the file.
	*
	* @return null|int|float Number of bytes as number (float or int) or
	*                        null on failure.
	*/
	public function getFilesize($filename) {
		$filesize = $this->getFilesizeViaCurl($filename);
		if (!is_null($filesize)) {
			return $filesize;
		}
		$filesize = $this->getFilesizeViaCOM($filename);
		if (!is_null($filesize)) {
			return $filesize;
		}
		$filesize = $this->getFilesizeViaExec($filename);
		if (!is_null($filesize)) {
			return $filesize;
		}
		return null;
	}

	/**
	* @brief Tries to get the filesize of a file via a CURL HEAD request.
	*
	* @param string $filename Path to the file.
	*
	* @return null|int|float Number of bytes as number (float or int) or
	*                        null on failure.
	*/
	public function getFilesizeViaCurl($filename) {
		if (function_exists('curl_init')) {
			$ch = curl_init("file://$filename");
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			$data = curl_exec($ch);
			curl_close($ch);
			if ($data !== false) {
				$matches = array();
				preg_match('/Content-Length: (\d+)/', $data, $matches);
				if (isset($matches[1])) {
					return 0 + $matches[1];
				}
			}
		}
		return null;
	}

	/**
	* @brief Tries to get the filesize of a file via the Windows DOM extension.
	*
	* @param string $filename Path to the file.
	*
	* @return null|int|float Number of bytes as number (float or int) or
	*                        null on failure.
	*/
	public function getFilesizeViaCOM($filename) {
		if (class_exists('COM')) {
			$fsobj = new \COM("Scripting.FileSystemObject");
			$file = $fsobj->GetFile($filename);
			return 0 + $file->Size;
		}
		return null;
	}

	/**
	* @brief Tries to get the filesize of a file via an exec() call.
	*
	* @param string $filename Path to the file.
	*
	* @return null|int|float Number of bytes as number (float or int) or
	*                        null on failure.
	*/
	public function getFilesizeViaExec($filename) {
		if (\OC_Helper::is_function_enabled('exec')) {
			$os = strtolower(php_uname('s'));
			$result = '';
			if (strpos($os, 'linux') !== false) {
				$result = trim(exec('stat -c %s ' . escapeshellarg($filename)));
			} else if (strpos($os, 'bsd') !== false) {
				$result = trim(exec('stat -f %z ' . escapeshellarg($filename)));
			}

			if (ctype_digit($result)) {
				return 0 + $result;
			}
		}
		return null;
	}
}
