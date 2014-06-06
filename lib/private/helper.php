<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 * Collection of useful functions
 */
class OC_Helper {
	private static $tmpFiles = array();
	private static $mimetypeIcons = array();
	private static $mimetypeDetector;
	private static $templateManager;

	/**
	 * @brief Creates an url using a defined route
	 * @param $route
	 * @param array $parameters
	 * @return
	 * @internal param array $args with param=>value, will be appended to the returned url
	 * @returns the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkToRoute($route, $parameters = array()) {
		return OC::$server->getURLGenerator()->linkToRoute($route, $parameters);
	}

	/**
	 * @brief Creates an url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *    The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkTo( $app, $file, $args = array() ) {
		return OC::$server->getURLGenerator()->linkTo($app, $file, $args);
	}

	/**
	 * @param $key
	 * @return string url to the online documentation
	 */
	public static function linkToDocs($key) {
		$theme = new OC_Defaults();
		return $theme->buildDocLinkToKey($key);
	}

	/**
	 * @brief Creates an absolute url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *    The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function linkToAbsolute($app, $file, $args = array()) {
		$urlLinkTo = self::linkTo($app, $file, $args);
		return self::makeURLAbsolute($urlLinkTo);
	}

	/**
	 * @brief Makes an $url absolute
	 * @param string $url the url
	 * @return string the absolute url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function makeURLAbsolute($url) {
		return OC::$server->getURLGenerator()->getAbsoluteURL($url);
	}

	/**
	 * @brief Creates an url for remote use
	 * @param string $service id
	 * @return string the url
	 *
	 * Returns a url to the given service.
	 */
	public static function linkToRemoteBase($service) {
		return self::linkTo('', 'remote.php') . '/' . $service;
	}

	/**
	 * @brief Creates an absolute url for remote use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToRemote($service, $add_slash = true) {
		return self::makeURLAbsolute(self::linkToRemoteBase($service))
		. (($add_slash && $service[strlen($service) - 1] != '/') ? '/' : '');
	}

	/**
	 * @brief Creates an absolute url for public use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToPublic($service, $add_slash = false) {
		return self::linkToAbsolute('', 'public.php') . '?service=' . $service
		. (($add_slash && $service[strlen($service) - 1] != '/') ? '/' : '');
	}

	/**
	 * @brief Creates path to an image
	 * @param string $app app
	 * @param string $image image name
	 * @return string the url
	 *
	 * Returns the path to the image.
	 */
	public static function imagePath($app, $image) {
		return OC::$server->getURLGenerator()->imagePath($app, $image);
	}

	/**
	 * @brief get path to icon of file type
	 * @param string $mimetype mimetype
	 * @return string the url
	 *
	 * Returns the path to the image of this file type.
	 */
	public static function mimetypeIcon($mimetype) {
		$alias = array(
			'application/xml' => 'code/xml',
			'application/msword' => 'x-office/document',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'x-office/document',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 'x-office/document',
			'application/vnd.ms-word.document.macroEnabled.12' => 'x-office/document',
			'application/vnd.ms-word.template.macroEnabled.12' => 'x-office/document',
			'application/vnd.oasis.opendocument.text' => 'x-office/document',
			'application/vnd.oasis.opendocument.text-template' => 'x-office/document',
			'application/vnd.oasis.opendocument.text-web' => 'x-office/document',
			'application/vnd.oasis.opendocument.text-master' => 'x-office/document',
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
			'application/vnd.ms-excel' => 'x-office/spreadsheet',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'x-office/spreadsheet',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'x-office/spreadsheet',
			'application/vnd.ms-excel.sheet.macroEnabled.12' => 'x-office/spreadsheet',
			'application/vnd.ms-excel.template.macroEnabled.12' => 'x-office/spreadsheet',
			'application/vnd.ms-excel.addin.macroEnabled.12' => 'x-office/spreadsheet',
			'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'x-office/spreadsheet',
			'application/vnd.oasis.opendocument.spreadsheet' => 'x-office/spreadsheet',
			'application/vnd.oasis.opendocument.spreadsheet-template' => 'x-office/spreadsheet',
		);

		if (isset($alias[$mimetype])) {
			$mimetype = $alias[$mimetype];
		}
		if (isset(self::$mimetypeIcons[$mimetype])) {
			return self::$mimetypeIcons[$mimetype];
		}
		// Replace slash and backslash with a minus
		$icon = str_replace('/', '-', $mimetype);
		$icon = str_replace('\\', '-', $icon);

		// Is it a dir?
		if ($mimetype === 'dir') {
			self::$mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/folder.png';
			return OC::$WEBROOT . '/core/img/filetypes/folder.png';
		}
		if ($mimetype === 'dir-shared') {
			self::$mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/folder-shared.png';
			return OC::$WEBROOT . '/core/img/filetypes/folder-shared.png';
		}
		if ($mimetype === 'dir-external') {
			self::$mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/folder-external.png';
			return OC::$WEBROOT . '/core/img/filetypes/folder-external.png';
		}

		// Icon exists?
		if (file_exists(OC::$SERVERROOT . '/core/img/filetypes/' . $icon . '.png')) {
			self::$mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/' . $icon . '.png';
			return OC::$WEBROOT . '/core/img/filetypes/' . $icon . '.png';
		}

		// Try only the first part of the filetype
		$mimePart = substr($icon, 0, strpos($icon, '-'));
		if (file_exists(OC::$SERVERROOT . '/core/img/filetypes/' . $mimePart . '.png')) {
			self::$mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/' . $mimePart . '.png';
			return OC::$WEBROOT . '/core/img/filetypes/' . $mimePart . '.png';
		} else {
			self::$mimetypeIcons[$mimetype] = OC::$WEBROOT . '/core/img/filetypes/file.png';
			return OC::$WEBROOT . '/core/img/filetypes/file.png';
		}
	}

	/**
	 * @brief get path to preview of file
	 * @param string $path path
	 * @return string the url
	 *
	 * Returns the path to the preview of the file.
	 */
	public static function previewIcon($path) {
		return self::linkToRoute( 'core_ajax_preview', array('x' => 36, 'y' => 36, 'file' => $path ));
	}

	public static function publicPreviewIcon( $path, $token ) {
		return self::linkToRoute( 'core_ajax_public_preview', array('x' => 36, 'y' => 36, 'file' => $path, 't' => $token));
	}

	/**
	 * @brief Make a human file size
	 * @param int $bytes file size in bytes
	 * @return string a human readable file size
	 *
	 * Makes 2048 to 2 kB.
	 */
	public static function humanFileSize($bytes) {
		if ($bytes < 0) {
			return "?";
		}
		if ($bytes < 1024) {
			return "$bytes B";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes kB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes MB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes GB";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return "$bytes TB";
		}

		$bytes = round($bytes / 1024, 1);
		return "$bytes PB";
	}

	/**
	 * @brief Make a php file size
	 * @param int $bytes file size in bytes
	 * @return string a php parseable file size
	 *
	 * Makes 2048 to 2k and 2^41 to 2048G
	 */
	public static function phpFileSize($bytes) {
		if ($bytes < 0) {
			return "?";
		}
		if ($bytes < 1024) {
			return $bytes . "B";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return $bytes . "K";
		}
		$bytes = round($bytes / 1024, 1);
		if ($bytes < 1024) {
			return $bytes . "M";
		}
		$bytes = round($bytes / 1024, 1);
		return $bytes . "G";
	}

	/**
	 * @brief Make a computer file size
	 * @param string $str file size in a fancy format
	 * @return int a file size in bytes
	 *
	 * Makes 2kB to 2048.
	 *
	 * Inspired by: http://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize($str) {
		$str = strtolower($str);

		$bytes_array = array(
			'b' => 1,
			'k' => 1024,
			'kb' => 1024,
			'mb' => 1024 * 1024,
			'm' => 1024 * 1024,
			'gb' => 1024 * 1024 * 1024,
			'g' => 1024 * 1024 * 1024,
			'tb' => 1024 * 1024 * 1024 * 1024,
			't' => 1024 * 1024 * 1024 * 1024,
			'pb' => 1024 * 1024 * 1024 * 1024 * 1024,
			'p' => 1024 * 1024 * 1024 * 1024 * 1024,
		);

		$bytes = floatval($str);

		if (preg_match('#([kmgtp]?b?)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
			$bytes *= $bytes_array[$matches[1]];
		}

		$bytes = round($bytes, 2);

		return $bytes;
	}

	/**
	 * @brief Recursive copying of folders
	 * @param string $src source folder
	 * @param string $dest target folder
	 *
	 */
	static function copyr($src, $dest) {
		if (is_dir($src)) {
			if (!is_dir($dest)) {
				mkdir($dest);
			}
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					self::copyr("$src/$file", "$dest/$file");
				}
			}
		} elseif (file_exists($src) && !\OC\Files\Filesystem::isFileBlacklisted($src)) {
			copy($src, $dest);
		}
	}

	/**
	 * @brief Recursive deletion of folders
	 * @param string $dir path to the folder
	 * @return bool
	 */
	static function rmdirr($dir) {
		if (is_dir($dir)) {
			$files = scandir($dir);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					self::rmdirr("$dir/$file");
				}
			}
			rmdir($dir);
		} elseif (file_exists($dir)) {
			unlink($dir);
		}
		if (file_exists($dir)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @return \OC\Files\Type\Detection
	 */
	static public function getMimetypeDetector() {
		if (!self::$mimetypeDetector) {
			self::$mimetypeDetector = new \OC\Files\Type\Detection();
			self::$mimetypeDetector->registerTypeArray(include 'mimetypes.list.php');
		}
		return self::$mimetypeDetector;
	}

	/**
	 * @return \OC\Files\Type\TemplateManager
	 */
	static public function getFileTemplateManager() {
		if (!self::$templateManager) {
			self::$templateManager = new \OC\Files\Type\TemplateManager();
		}
		return self::$templateManager;
	}

	/**
	 * Try to guess the mimetype based on filename
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getFileNameMimeType($path) {
		return self::getMimetypeDetector()->detectPath($path);
	}

	/**
	 * get the mimetype form a local file
	 *
	 * @param string $path
	 * @return string
	 * does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 */
	static function getMimeType($path) {
		return self::getMimetypeDetector()->detect($path);
	}

	/**
	 * Get a secure mimetype that won't expose potential XSS.
	 *
	 * @param string $mimeType
	 * @return string
	 */
	static function getSecureMimeType($mimeType) {
		return self::getMimetypeDetector()->getSecureMimeType($mimeType);
	}
	
	/**
	 * get the mimetype form a data string
	 *
	 * @param string $data
	 * @return string
	 */
	static function getStringMimeType($data) {
		return self::getMimetypeDetector()->detectString($data);
	}

	/**
	 * @brief Checks $_REQUEST contains a var for the $s key. If so, returns the html-escaped value of this var; otherwise returns the default value provided by $d.
	 * @param string $s name of the var to escape, if set.
	 * @param string $d default value.
	 * @return string the print-safe value.
	 *
	 */

	//FIXME: should also check for value validation (i.e. the email is an email).
	public static function init_var($s, $d = "") {
		$r = $d;
		if (isset($_REQUEST[$s]) && !empty($_REQUEST[$s])) {
			$r = OC_Util::sanitizeHTML($_REQUEST[$s]);
		}

		return $r;
	}

	/**
	 * returns "checked"-attribute if request contains selected radio element
	 * OR if radio element is the default one -- maybe?
	 *
	 * @param string $s Name of radio-button element name
	 * @param string $v Value of current radio-button element
	 * @param string $d Value of default radio-button element
	 */
	public static function init_radio($s, $v, $d) {
		if ((isset($_REQUEST[$s]) && $_REQUEST[$s] == $v) || (!isset($_REQUEST[$s]) && $v == $d))
			print "checked=\"checked\" ";
	}

	/**
	 * detect if a given program is found in the search PATH
	 *
	 * @param $name
	 * @param bool $path
	 * @internal param string $program name
	 * @internal param string $optional search path, defaults to $PATH
	 * @return bool    true if executable program found in path
	 */
	public static function canExecute($name, $path = false) {
		// path defaults to PATH from environment if not set
		if ($path === false) {
			$path = getenv("PATH");
		}
		// check method depends on operating system
		if (!strncmp(PHP_OS, "WIN", 3)) {
			// on Windows an appropriate COM or EXE file needs to exist
			$exts = array(".exe", ".com");
			$check_fn = "file_exists";
		} else {
			// anywhere else we look for an executable file of that name
			$exts = array("");
			$check_fn = "is_executable";
		}
		// Default check will be done with $path directories :
		$dirs = explode(PATH_SEPARATOR, $path);
		// WARNING : We have to check if open_basedir is enabled :
		$obd = ini_get('open_basedir');
		if ($obd != "none") {
			$obd_values = explode(PATH_SEPARATOR, $obd);
			if (count($obd_values) > 0 and $obd_values[0]) {
				// open_basedir is in effect !
				// We need to check if the program is in one of these dirs :
				$dirs = $obd_values;
			}
		}
		foreach ($dirs as $dir) {
			foreach ($exts as $ext) {
				if ($check_fn("$dir/$name" . $ext))
					return true;
			}
		}
		return false;
	}

	/**
	 * copy the contents of one stream to another
	 *
	 * @param resource $source
	 * @param resource $target
	 * @return array the number of bytes copied and result
	 */
	public static function streamCopy($source, $target) {
		if (!$source or !$target) {
			return array(0, false);
		}
		$result = true;
		$count = 0;
		while (!feof($source)) {
			if (($c = fwrite($target, fread($source, 8192))) === false) {
				$result = false;
			} else {
				$count += $c;
			}
		}
		return array($count, $result);
	}

	/**
	 * create a temporary file with an unique filename
	 *
	 * @param string $postfix
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFile($postfix = '') {
		$file = get_temp_dir() . '/' . md5(time() . rand()) . $postfix;
		$fh = fopen($file, 'w');
		fclose($fh);
		self::$tmpFiles[] = $file;
		return $file;
	}

	/**
	 * move a file to oc-noclean temp dir
	 *
	 * @param string $filename
	 * @return mixed
	 *
	 */
	public static function moveToNoClean($filename = '') {
		if ($filename == '') {
			return false;
		}
		$tmpDirNoClean = get_temp_dir() . '/oc-noclean/';
		if (!file_exists($tmpDirNoClean) || !is_dir($tmpDirNoClean)) {
			if (file_exists($tmpDirNoClean)) {
				unlink($tmpDirNoClean);
			}
			mkdir($tmpDirNoClean);
		}
		$newname = $tmpDirNoClean . basename($filename);
		if (rename($filename, $newname)) {
			return $newname;
		} else {
			return false;
		}
	}

	/**
	 * create a temporary folder with an unique filename
	 *
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFolder() {
		$path = get_temp_dir() . '/' . md5(time() . rand());
		mkdir($path);
		self::$tmpFiles[] = $path;
		return $path . '/';
	}

	/**
	 * remove all files created by self::tmpFile
	 */
	public static function cleanTmp() {
		$leftoversFile = get_temp_dir() . '/oc-not-deleted';
		if (file_exists($leftoversFile)) {
			$leftovers = file($leftoversFile);
			foreach ($leftovers as $file) {
				self::rmdirr($file);
			}
			unlink($leftoversFile);
		}

		foreach (self::$tmpFiles as $file) {
			if (file_exists($file)) {
				if (!self::rmdirr($file)) {
					file_put_contents($leftoversFile, $file . "\n", FILE_APPEND);
				}
			}
		}
	}

	/**
	 * remove all files in PHP /oc-noclean temp dir
	 */
	public static function cleanTmpNoClean() {
		$tmpDirNoCleanName=get_temp_dir() . '/oc-noclean/';
		if(file_exists($tmpDirNoCleanName) && is_dir($tmpDirNoCleanName)) {
			$files=scandir($tmpDirNoCleanName);
			foreach($files as $file) {
				$fileName = $tmpDirNoCleanName . $file;
				if (!\OC\Files\Filesystem::isIgnoredDir($file) && filemtime($fileName) + 600 < time()) {
					unlink($fileName);
				}
			}
			// if oc-noclean is empty delete it
			$isTmpDirNoCleanEmpty = true;
			$tmpDirNoClean = opendir($tmpDirNoCleanName);
			if(is_resource($tmpDirNoClean)) {
				while (false !== ($file = readdir($tmpDirNoClean))) {
					if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
						$isTmpDirNoCleanEmpty = false;
					}
				}
			}
			if ($isTmpDirNoCleanEmpty) {
				rmdir($tmpDirNoCleanName);
			}
		}
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param $path
	 * @param $filename
	 * @return string
	 */
	public static function buildNotExistingFileName($path, $filename) {
		$view = \OC\Files\Filesystem::getView();
		return self::buildNotExistingFileNameForView($path, $filename, $view);
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param $path
	 * @param $filename
	 * @return string
	 */
	public static function buildNotExistingFileNameForView($path, $filename, \OC\Files\View $view) {
		if ($path === '/') {
			$path = '';
		}
		if ($pos = strrpos($filename, '.')) {
			$name = substr($filename, 0, $pos);
			$ext = substr($filename, $pos);
		} else {
			$name = $filename;
			$ext = '';
		}

		$newpath = $path . '/' . $filename;
		if ($view->file_exists($newpath)) {
			if (preg_match_all('/\((\d+)\)/', $name, $matches, PREG_OFFSET_CAPTURE)) {
				//Replace the last "(number)" with "(number+1)"
				$last_match = count($matches[0]) - 1;
				$counter = $matches[1][$last_match][0] + 1;
				$offset = $matches[0][$last_match][1];
				$match_length = strlen($matches[0][$last_match][0]);
			} else {
				$counter = 2;
				$offset = false;
			}
			do {
				if ($offset) {
					//Replace the last "(number)" with "(number+1)"
					$newname = substr_replace($name, '(' . $counter . ')', $offset, $match_length);
				} else {
					$newname = $name . ' (' . $counter . ')';
				}
				$newpath = $path . '/' . $newname . $ext;
				$counter++;
			} while ($view->file_exists($newpath));
		}

		return $newpath;
	}

	/**
	 * @brief Checks if $sub is a subdirectory of $parent
	 *
	 * @param string $sub
	 * @param string $parent
	 * @return bool
	 */
	public static function issubdirectory($sub, $parent) {
		$realpathSub = realpath($sub);
		$realpathParent = realpath($parent);

		// realpath() may return false in case the directory does not exist
		// since we can not be sure how different PHP versions may behave here
		// we do an additional check whether realpath returned false
		if($realpathSub === false ||  $realpathParent === false) {
			return false;
		}

		// Check whether $sub is a subdirectory of $parent
		if (strpos($realpathSub, $realpathParent) === 0) {
			return true;
		}

		return false;
	}

	/**
	 * @brief Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	 *
	 * @param array $input The array to work on
	 * @param int $case Either MB_CASE_UPPER or MB_CASE_LOWER (default)
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @return array
	 *
	 * Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	 * based on http://www.php.net/manual/en/function.array-change-key-case.php#107715
	 *
	 */
	public static function mb_array_change_key_case($input, $case = MB_CASE_LOWER, $encoding = 'UTF-8') {
		$case = ($case != MB_CASE_UPPER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		$ret = array();
		foreach ($input as $k => $v) {
			$ret[mb_convert_case($k, $case, $encoding)] = $v;
		}
		return $ret;
	}

	/**
	 * @brief replaces a copy of string delimited by the start and (optionally) length parameters with the string given in replacement.
	 *
	 * @param $string
	 * @param string $replacement The replacement string.
	 * @param int $start If start is positive, the replacing will begin at the start'th offset into string. If start is negative, the replacing will begin at the start'th character from the end of string.
	 * @param int $length Length of the part to be replaced
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @internal param string $input The input string. .Opposite to the PHP build-in function does not accept an array.
	 * @return string
	 */
	public static function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = 'UTF-8') {
		$start = intval($start);
		$length = intval($length);
		$string = mb_substr($string, 0, $start, $encoding) .
			$replacement .
			mb_substr($string, $start + $length, mb_strlen($string, 'UTF-8') - $start, $encoding);

		return $string;
	}

	/**
	 * @brief Replace all occurrences of the search string with the replacement string
	 *
	 * @param string $search The value being searched for, otherwise known as the needle.
	 * @param string $replace The replacement
	 * @param string $subject The string or array being searched and replaced on, otherwise known as the haystack.
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @param int $count If passed, this will be set to the number of replacements performed.
	 * @return string
	 *
	 */
	public static function mb_str_replace($search, $replace, $subject, $encoding = 'UTF-8', &$count = null) {
		$offset = -1;
		$length = mb_strlen($search, $encoding);
		while (($i = mb_strrpos($subject, $search, $offset, $encoding)) !== false) {
			$subject = OC_Helper::mb_substr_replace($subject, $replace, $i, $length);
			$offset = $i - mb_strlen($subject, $encoding);
			$count++;
		}
		return $subject;
	}

	/**
	 * @brief performs a search in a nested array
	 * @param array $haystack the array to be searched
	 * @param string $needle the search string
	 * @param string $index optional, only search this key name
	 * @return mixed the key of the matching field, otherwise false
	 *
	 * performs a search in a nested array
	 *
	 * taken from http://www.php.net/manual/en/function.array-search.php#97645
	 */
	public static function recursiveArraySearch($haystack, $needle, $index = null) {
		$aIt = new RecursiveArrayIterator($haystack);
		$it = new RecursiveIteratorIterator($aIt);

		while ($it->valid()) {
			if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND ($it->current() == $needle)) {
				return $aIt->key();
			}

			$it->next();
		}

		return false;
	}

	/**
	 * Shortens str to maxlen by replacing characters in the middle with '...', eg.
	 * ellipsis('a very long string with lots of useless info to make a better example', 14) becomes 'a very ...example'
	 *
	 * @param string $str the string
	 * @param string $maxlen the maximum length of the result
	 * @return string with at most maxlen characters
	 */
	public static function ellipsis($str, $maxlen) {
		if (strlen($str) > $maxlen) {
			$characters = floor($maxlen / 2);
			return substr($str, 0, $characters) . '...' . substr($str, -1 * $characters);
		}
		return $str;
	}

	/**
	 * @brief calculates the maximum upload size respecting system settings, free space and user quota
	 *
	 * @param $dir the current folder where the user currently operates
	 * @return number of bytes representing
	 */
	public static function maxUploadFilesize($dir) {
		$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
		$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
		$freeSpace = \OC\Files\Filesystem::free_space($dir);
		if ((int)$upload_max_filesize === 0 and (int)$post_max_size === 0) {
			$maxUploadFilesize = \OC\Files\SPACE_UNLIMITED;
		} elseif ((int)$upload_max_filesize === 0 or (int)$post_max_size === 0) {
			$maxUploadFilesize = max($upload_max_filesize, $post_max_size); //only the non 0 value counts
		} else {
			$maxUploadFilesize = min($upload_max_filesize, $post_max_size);
		}

		if ($freeSpace !== \OC\Files\SPACE_UNKNOWN) {
			$freeSpace = max($freeSpace, 0);

			return min($maxUploadFilesize, $freeSpace);
		} else {
			return $maxUploadFilesize;
		}
	}

	/**
	 * Checks if a function is available
	 *
	 * @param string $function_name
	 * @return bool
	 */
	public static function is_function_enabled($function_name) {
		if (!function_exists($function_name)) {
			return false;
		}
		$disabled = explode(',', ini_get('disable_functions'));
		$disabled = array_map('trim', $disabled);
		if (in_array($function_name, $disabled)) {
			return false;
		}
		$disabled = explode(',', ini_get('suhosin.executor.func.blacklist'));
		$disabled = array_map('trim', $disabled);
		if (in_array($function_name, $disabled)) {
			return false;
		}
		return true;
	}

	/**
	 * Calculate the disc space for the given path
	 *
	 * @param string $path
	 * @return array
	 */
	public static function getStorageInfo($path) {
		// return storage info without adding mount points
		$rootInfo = \OC\Files\Filesystem::getFileInfo($path, false);
		$used = $rootInfo['size'];
		if ($used < 0) {
			$used = 0;
		}

		$free = \OC\Files\Filesystem::free_space($path);
		if ($free >= 0) {
			$total = $free + $used;
		} else {
			$total = $free; //either unknown or unlimited
		}
		if ($total > 0) {
			// prevent division by zero or error codes (negative values)
			$relative = round(($used / $total) * 10000) / 100;
		} else {
			$relative = 0;
		}

		return array('free' => $free, 'used' => $used, 'total' => $total, 'relative' => $relative);
	}
}
