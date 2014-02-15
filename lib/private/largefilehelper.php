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
	* pow(2, 53) as a base-10 string.
	* @var string
	*/
	const POW_2_53 = '9007199254740992';

	/**
	* pow(2, 53) - 1 as a base-10 string.
	* @var string
	*/
	const POW_2_53_MINUS_1 = '9007199254740991';

	/**
	* @brief Constructor. Checks whether our assumptions hold on the platform
	*        we are on, throws an exception if they do not hold.
	*/
	public function __construct() {
		$pow_2_53 = floatval(self::POW_2_53_MINUS_1) + 1.0;
		if ($this->formatUnsignedInteger($pow_2_53) !== self::POW_2_53) {
			throw new \RunTimeException(
				'This class assumes floats to be double precision or "better".'
			);
		}
	}

	/**
	* @brief Formats a signed integer or float as an unsigned integer base-10
	*        string. Passed strings will be checked for being base-10.
	*
	* @param int|float|string $number Number containing unsigned integer data
	*
	* @return string Unsigned integer base-10 string
	*/
	public function formatUnsignedInteger($number) {
		if (is_float($number)) {
			// Undo the effect of the php.ini setting 'precision'.
			return number_format($number, 0, '', '');
		} else if (is_string($number) && ctype_digit($number)) {
			return $number;
		} else if (is_int($number)) {
			// Interpret signed integer as unsigned integer.
			return sprintf('%u', $number);
		} else {
			throw new \UnexpectedValueException(
				'Expected int, float or base-10 string'
			);
		}
	}

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
			$arg = escapeshellarg($filename);
			$result = '';
			if (strpos($os, 'linux') !== false) {
				$result = $this->exec("stat -c %s $arg");
			} else if (strpos($os, 'bsd') !== false) {
				$result = $this->exec("stat -f %z $arg");
			} else if (strpos($os, 'win') !== false) {
				$result = $this->exec("for %F in ($arg) do @echo %~zF");
				if (is_null($result)) {
					// PowerShell
					$result = $this->exec("(Get-Item $arg).length");
				}
			}
			return $result;
		}
		return null;
	}

	protected function exec($cmd) {
		$result = trim(exec($cmd));
		return ctype_digit($result) ? 0 + $result : null;
	}
}
