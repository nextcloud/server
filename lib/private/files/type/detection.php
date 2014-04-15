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
	protected $secureMimeTypes = array();

	/**
	 * Add an extension -> mimetype mapping
	 *
	 * $mimetype is the assumed correct mime type
	 * The optional $secureMimeType is an alternative to send to send
	 * to avoid potential XSS.
	 *
	 * @param string $extension
	 * @param string $mimetype
	 * @param string|null $secureMimeType
	 */
	public function registerType($extension, $mimetype, $secureMimeType = null) {
		$this->mimetypes[$extension] = array($mimetype, $secureMimeType);
		$this->secureMimeTypes[$mimetype] = $secureMimeType ?: $mimetype;
	}

	/**
	 * Add an array of extension -> mimetype mappings
	 *
	 * The mimetype value is in itself an array where the first index is
	 * the assumed correct mimetype and the second is either a secure alternative
	 * or null if the correct is considered secure.
	 *
	 * @param array $types
	 */
	public function registerTypeArray($types) {
		$this->mimetypes = array_merge($this->mimetypes, $types);

		// Update the alternative mimetypes to avoid having to look them up each time.
		foreach ($this->mimetypes as $mimeType) {
			$this->secureMimeTypes[$mimeType[0]] = $mimeType[1] ?: $mimeType[0];
		}
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
			return (isset($this->mimetypes[$extension]) && isset($this->mimetypes[$extension][0]))
				? $this->mimetypes[$extension][0]
				: 'application/octet-stream';
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
			finfo_close($finfo);
			if ($info) {
				$mimeType = substr($info, 0, strpos($info, ';'));
				return empty($mimeType) ? 'application/octet-stream' : $mimeType;
			}

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

	/**
	 * Get a secure mimetype that won't expose potential XSS.
	 *
	 * @param string $mimeType
	 * @return string
	 */
	public function getSecureMimeType($mimeType) {
		return isset($this->secureMimeTypes[$mimeType])
			? $this->secureMimeTypes[$mimeType]
			: 'application/octet-stream';
	}
}
