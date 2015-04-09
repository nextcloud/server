<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Jens-Christian Fischer <jens-christian.fischer@switch.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Type;

use OCP\Files\IMimeTypeDetector;

/**
 * Class Detection
 *
 * Mimetype detection
 *
 * @package OC\Files\Type
 */
class Detection implements IMimeTypeDetector {
	protected $mimetypes = array();
	protected $secureMimeTypes = array();

	protected $mimetypeIcons = [];
	/** @var string[] */
	protected $mimeTypeAlias = [
		'application/octet-stream' => 'file', // use file icon as fallback

		'application/illustrator' => 'image/vector',
		'application/postscript' => 'image/vector',
		'image/svg+xml' => 'image/vector',

		'application/coreldraw' => 'image',
		'application/x-gimp' => 'image',
		'application/x-photoshop' => 'image',
		'application/x-dcraw' => 'image',

		'application/font-sfnt' => 'font',
		'application/x-font' => 'font',
		'application/font-woff' => 'font',
		'application/vnd.ms-fontobject' => 'font',

		'application/json' => 'text/code',
		'application/x-perl' => 'text/code',
		'application/x-php' => 'text/code',
		'text/x-shellscript' => 'text/code',
		'application/yaml' => 'text/code',
		'application/xml' => 'text/html',
		'text/css' => 'text/code',
		'application/x-tex' => 'text',

		'application/x-compressed' => 'package/x-generic',
		'application/x-7z-compressed' => 'package/x-generic',
		'application/x-deb' => 'package/x-generic',
		'application/x-gzip' => 'package/x-generic',
		'application/x-rar-compressed' => 'package/x-generic',
		'application/x-tar' => 'package/x-generic',
		'application/vnd.android.package-archive' => 'package/x-generic',
		'application/zip' => 'package/x-generic',

		'application/msword' => 'x-office/document',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'x-office/document',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 'x-office/document',
		'application/vnd.ms-word.document.macroEnabled.12' => 'x-office/document',
		'application/vnd.ms-word.template.macroEnabled.12' => 'x-office/document',
		'application/vnd.oasis.opendocument.text' => 'x-office/document',
		'application/vnd.oasis.opendocument.text-template' => 'x-office/document',
		'application/vnd.oasis.opendocument.text-web' => 'x-office/document',
		'application/vnd.oasis.opendocument.text-master' => 'x-office/document',

		'application/mspowerpoint' => 'x-office/presentation',
		'application/vnd.ms-powerpoint' => 'x-office/presentation',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'x-office/presentation',
		'application/vnd.openxmlformats-officedocument.presentationml.template' => 'x-office/presentation',
		'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'x-office/presentation',
		'application/vnd.ms-powerpoint.addin.macroEnabled.12' => 'x-office/presentation',
		'application/vnd.ms-powerpoint.presentation.macroEnabled.12' => 'x-office/presentation',
		'application/vnd.ms-powerpoint.template.macroEnabled.12' => 'x-office/presentation',
		'application/vnd.ms-powerpoint.slideshow.macroEnabled.12' => 'x-office/presentation',
		'application/vnd.oasis.opendocument.presentation' => 'x-office/presentation',
		'application/vnd.oasis.opendocument.presentation-template' => 'x-office/presentation',

		'application/msexcel' => 'x-office/spreadsheet',
		'application/vnd.ms-excel' => 'x-office/spreadsheet',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'x-office/spreadsheet',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'x-office/spreadsheet',
		'application/vnd.ms-excel.sheet.macroEnabled.12' => 'x-office/spreadsheet',
		'application/vnd.ms-excel.template.macroEnabled.12' => 'x-office/spreadsheet',
		'application/vnd.ms-excel.addin.macroEnabled.12' => 'x-office/spreadsheet',
		'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'x-office/spreadsheet',
		'application/vnd.oasis.opendocument.spreadsheet' => 'x-office/spreadsheet',
		'application/vnd.oasis.opendocument.spreadsheet-template' => 'x-office/spreadsheet',
		'text/csv' => 'x-office/spreadsheet',

		'application/msaccess' => 'database',
	];


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
			$this->secureMimeTypes[$mimeType[0]] = isset($mimeType[1]) ? $mimeType[1]: $mimeType[0];
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

	/**
	 * Get path to the icon of a file type
	 * @param string $mimeType the MIME type
	 * @return string the url
	 */
	public function mimeTypeIcon($mimetype) {
		if (isset($this->mimeTypeAlias[$mimetype])) {
			$mimetype = $this->mimeTypeAlias[$mimetype];
		}
		if (isset($this->mimetypeIcons[$mimetype])) {
			return $this->mimetypeIcons[$mimetype];
		}
		// Replace slash and backslash with a minus
		$icon = str_replace('/', '-', $mimetype);
		$icon = str_replace('\\', '-', $icon);

		// Is it a dir?
		if ($mimetype === 'dir') {
			$this->mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/folder.png';
			return OC::$WEBROOT . '/core/img/filetypes/folder.png';
		}
		if ($mimetype === 'dir-shared') {
			$this->mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/folder-shared.png';
			return OC::$WEBROOT . '/core/img/filetypes/folder-shared.png';
		}
		if ($mimetype === 'dir-external') {
			$this->mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/folder-external.png';
			return OC::$WEBROOT . '/core/img/filetypes/folder-external.png';
		}

		// Icon exists?
		if (file_exists(OC::$SERVERROOT . '/core/img/filetypes/' . $icon . '.png')) {
			$this->mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/' . $icon . '.png';
			return OC::$WEBROOT . '/core/img/filetypes/' . $icon . '.png';
		}

		// Try only the first part of the filetype
		$mimePart = substr($icon, 0, strpos($icon, '-'));
		if (file_exists(OC::$SERVERROOT . '/core/img/filetypes/' . $mimePart . '.png')) {
			$this->mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/' . $mimePart . '.png';
			return OC::$WEBROOT . '/core/img/filetypes/' . $mimePart . '.png';
		} else {
			$this->mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/file.png';
			return OC::$WEBROOT . '/core/img/filetypes/file.png';
		}
	}
}
