<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Type;

/**
 * Class Detection
 *
 * Mimetype detection
 *
 * @package OC\Files\Type
 */
class Detection {
	protected $mimetypes = array();

	/**
	 * add an extension -> mimetype mapping
	 *
	 * @param string $extension
	 * @param string $mimetype
	 */
	public function registerType($extension, $mimetype) {
		$this->mimetypes[$extension] = $mimetype;
	}

	/**
	 * add an array of extension -> mimetype mappings
	 *
	 * @param array $types
	 */
	public function registerTypeArray($types) {
		$this->mimetypes = array_merge($this->mimetypes, $types);
	}

	/**
	 * detect mimetype only based on filename, content of file is not used
	 *
	 * @param string $path
	 * @return string
	 */
	public function detectPath($path) {
		if (strpos($path, '.')) {
			//try to guess the type by the file extension
			$extension = strtolower(strrchr(basename($path), "."));
			$extension = substr($extension, 1); //remove leading .
			return (isset($this->mimetypes[$extension])) ? $this->mimetypes[$extension] : 'application/octet-stream';
		} else {
			return 'application/octet-stream';
		}
	}

	/**
	 * detect mimetype based on both filename and content
	 *
	 * @param string $path
	 * @return string
	 */
	public function detect($path) {
		if (@is_dir($path)) {
			// directories are easy
			return "httpd/unix-directory";
		}

		$mimeType = $this->detectPath($path);

		if ($mimeType === 'application/octet-stream' and function_exists('finfo_open')
			and function_exists('finfo_file') and $finfo = finfo_open(FILEINFO_MIME)
		) {
			$info = @strtolower(finfo_file($finfo, $path));
			if ($info) {
				$mimeType = substr($info, 0, strpos($info, ';'));
				return empty($mimeType) ? 'application/octet-stream' : $mimeType;
			}
			finfo_close($finfo);
		}
		$isWrapped = (strpos($path, '://') !== false) and (substr($path, 0, 7) === 'file://');
		if (!$isWrapped and $mimeType === 'application/octet-stream' && function_exists("mime_content_type")) {
			// use mime magic extension if available
			$mimeType = mime_content_type($path);
		}
		if (!$isWrapped and $mimeType === 'application/octet-stream' && \OC_Helper::canExecute("file")) {
			// it looks like we have a 'file' command,
			// lets see if it does have mime support
			$path = escapeshellarg($path);
			$fp = popen("file -b --mime-type $path 2>/dev/null", "r");
			$reply = fgets($fp);
			pclose($fp);

			//trim the newline
			$mimeType = trim($reply);

			if (empty($mimeType)) {
				$mimeType = 'application/octet-stream';
			}

		}
		return $mimeType;
	}

	/**
	 * detect mimetype based on the content of a string
	 *
	 * @param string $data
	 * @return string
	 */
	public function detectString($data) {
		if (function_exists('finfo_open') and function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME);
			return finfo_buffer($finfo, $data);
		} else {
			$tmpFile = \OC_Helper::tmpFile();
			$fh = fopen($tmpFile, 'wb');
			fwrite($fh, $data, 8024);
			fclose($fh);
			$mime = $this->detect($tmpFile);
			unset($tmpFile);
			return $mime;
		}
	}
}
