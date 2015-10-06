<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Fabian Henze <flyser42@gmx.de>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author François Kubler <francois@kubler.org>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nicolas Grekas <nicolas.grekas@gmail.com>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Pellaeon Lin <nfsmwlin@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Simon Könnecke <simonkoennecke@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Valerio Ponte <valerio.ponte@gmail.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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
use Symfony\Component\Process\ExecutableFinder;

/**
 * Collection of useful functions
 */
class OC_Helper {
	private static $templateManager;

	/**
	 * Creates an url using a defined route
	 * @param string $route
	 * @param array $parameters with param=>value, will be appended to the returned url
	 * @return string the url
	 * @deprecated Use \OC::$server->getURLGenerator()->linkToRoute($route, $parameters)
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkToRoute($route, $parameters = array()) {
		return OC::$server->getURLGenerator()->linkToRoute($route, $parameters);
	}

	/**
	 * Creates an url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *    The value of $args will be urlencoded
	 * @return string the url
	 * @deprecated Use \OC::$server->getURLGenerator()->linkTo($app, $file, $args)
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkTo( $app, $file, $args = array() ) {
		return OC::$server->getURLGenerator()->linkTo($app, $file, $args);
	}

	/**
	 * @param string $key
	 * @return string url to the online documentation
	 * @deprecated Use \OC::$server->getURLGenerator()->linkToDocs($key)
	 */
	public static function linkToDocs($key) {
		return OC::$server->getURLGenerator()->linkToDocs($key);
	}

	/**
	 * Creates an absolute url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *    The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function linkToAbsolute($app, $file, $args = array()) {
		return OC::$server->getURLGenerator()->getAbsoluteURL(
			self::linkTo($app, $file, $args)
		);
	}

	/**
	 * Makes an $url absolute
	 * @param string $url the url
	 * @return string the absolute url
	 * @deprecated Use \OC::$server->getURLGenerator()->getAbsoluteURL($url)
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function makeURLAbsolute($url) {
		return OC::$server->getURLGenerator()->getAbsoluteURL($url);
	}

	/**
	 * Creates an url for remote use
	 * @param string $service id
	 * @return string the url
	 *
	 * Returns a url to the given service.
	 */
	public static function linkToRemoteBase($service) {
		return self::linkTo('', 'remote.php') . '/' . $service;
	}

	/**
	 * Creates an absolute url for remote use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToRemote($service, $add_slash = true) {
		return OC::$server->getURLGenerator()->getAbsoluteURL(
			self::linkToRemoteBase($service)
				. (($add_slash && $service[strlen($service) - 1] != '/') ? '/' : '')
		);
	}

	/**
	 * Creates an absolute url for public use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToPublic($service, $add_slash = false) {
		if ($service === 'files') {
			$url = OC::$server->getURLGenerator()->getAbsoluteURL('/s');
		} else {
			$url = OC::$server->getURLGenerator()->getAbsoluteURL(self::linkTo('', 'public.php').'?service='.$service);
		}
		return $url . (($add_slash && $service[strlen($service) - 1] != '/') ? '/' : '');
	}

	/**
	 * Creates path to an image
	 * @param string $app app
	 * @param string $image image name
	 * @return string the url
	 * @deprecated Use \OC::$server->getURLGenerator()->imagePath($app, $image)
	 *
	 * Returns the path to the image.
	 */
	public static function imagePath($app, $image) {
		return OC::$server->getURLGenerator()->imagePath($app, $image);
	}

	/**
	 * get path to icon of file type
	 * @param string $mimetype mimetype
	 * @return string the url
	 *
	 * Returns the path to the image of this file type.
	 * @deprecated 8.2.0 Use \OC::$server->getMimeTypeDetector()->mimeTypeIcon($mimetype)
	 */
	public static function mimetypeIcon($mimetype) {
		return \OC::$server->getMimeTypeDetector()->mimeTypeIcon($mimetype);
	}

	/**
	 * get path to preview of file
	 * @param string $path path
	 * @return string the url
	 *
	 * Returns the path to the preview of the file.
	 */
	public static function previewIcon($path) {
		return self::linkToRoute( 'core_ajax_preview', array('x' => 32, 'y' => 32, 'file' => $path ));
	}

	public static function publicPreviewIcon( $path, $token ) {
		return self::linkToRoute( 'core_ajax_public_preview', array('x' => 32, 'y' => 32, 'file' => $path, 't' => $token));
	}

	/**
	 * shows whether the user has an avatar
	 * @param string $user username
	 * @return bool avatar set or not
	**/
	public static function userAvatarSet($user) {
		$avatar = new \OC\Avatar($user);
		return $avatar->exists();
	}

	/**
	 * Make a human file size
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
		$bytes = round($bytes / 1024, 0);
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
	 * Make a php file size
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
	 * Make a computer file size
	 * @param string $str file size in human readable format
	 * @return float a file size in bytes
	 *
	 * Makes 2kB to 2048.
	 *
	 * Inspired by: http://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize($str) {
		$str = strtolower($str);
		if (is_numeric($str)) {
			return floatval($str);
		}

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
		} else {
			return false;
		}

		$bytes = round($bytes);

		return $bytes;
	}

	/**
	 * Recursive copying of folders
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
	 * Recursive deletion of folders
	 * @param string $dir path to the folder
	 * @param bool $deleteSelf if set to false only the content of the folder will be deleted
	 * @return bool
	 */
	static function rmdirr($dir, $deleteSelf = true) {
		if (is_dir($dir)) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileInfo) {
				/** @var SplFileInfo $fileInfo */
				if ($fileInfo->isDir()) {
					rmdir($fileInfo->getRealPath());
				} else {
					unlink($fileInfo->getRealPath());
				}
			}
			if ($deleteSelf) {
				rmdir($dir);
			}
		} elseif (file_exists($dir)) {
			if ($deleteSelf) {
				unlink($dir);
			}
		}
		if (!$deleteSelf) {
			return true;
		}

		return !file_exists($dir);
	}

	/**
	 * @return \OC\Files\Type\Detection
	 * @deprecated 8.2.0 use \OC::$server->getMimeTypeDetector()
	 */
	static public function getMimetypeDetector() {
		return \OC::$server->getMimeTypeDetector();
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
	 * @deprecated 8.2.0 Use \OC::$server->getMimeTypeDetector()->detectPath($path)
	 */
	static public function getFileNameMimeType($path) {
		return \OC::$server->getMimeTypeDetector()->detectPath($path);
	}

	/**
	 * get the mimetype form a local file
	 *
	 * @param string $path
	 * @return string
	 * does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 * @deprecated 8.2.0 Use \OC::$server->getMimeTypeDetector()->detect($path)
	 */
	static function getMimeType($path) {
		return \OC::$server->getMimeTypeDetector()->detect($path);
	}

	/**
	 * Get a secure mimetype that won't expose potential XSS.
	 *
	 * @param string $mimeType
	 * @return string
	 * @deprecated 8.2.0 Use \OC::$server->getMimeTypeDetector()->getSecureMimeType($mimeType)
	 */
	static function getSecureMimeType($mimeType) {
		return \OC::$server->getMimeTypeDetector()->getSecureMimeType($mimeType);
	}

	/**
	 * get the mimetype form a data string
	 *
	 * @param string $data
	 * @return string
	 * @deprecated 8.2.0 Use \OC::$server->getMimeTypeDetector()->detectString($data)
	 */
	static function getStringMimeType($data) {
		return \OC::$server->getMimeTypeDetector()->detectString($data);
	}

	/**
	 * detect if a given program is found in the search PATH
	 *
	 * @param string $name
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
		$bufSize = 8192;
		$result = true;
		$count = 0;
		while (!feof($source)) {
			$buf = fread($source, $bufSize);
			$bytesWritten = fwrite($target, $buf);
			if ($bytesWritten !== false) {
				$count += $bytesWritten;
			}
			// note: strlen is expensive so only use it when necessary,
			// on the last block
			if ($bytesWritten === false
				|| ($bytesWritten < $bufSize && $bytesWritten < strlen($buf))
			) {
				// write error, could be disk full ?
				$result = false;
				break;
			}
		}
		return array($count, $result);
	}

	/**
	 * create a temporary file with an unique filename
	 *
	 * @param string $postfix
	 * @return string
	 * @deprecated Use the TempManager instead
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFile($postfix = '') {
		return \OC::$server->getTempManager()->getTemporaryFile($postfix);
	}

	/**
	 * create a temporary folder with an unique filename
	 *
	 * @return string
	 * @deprecated Use the TempManager instead
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFolder() {
		return \OC::$server->getTempManager()->getTemporaryFolder();
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param string $path
	 * @param string $filename
	 * @return string
	 */
	public static function buildNotExistingFileName($path, $filename) {
		$view = \OC\Files\Filesystem::getView();
		return self::buildNotExistingFileNameForView($path, $filename, $view);
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 *
	 * @param string $path
	 * @param string $filename
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
				$match_length = 0;
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
	 * Checks if $sub is a subdirectory of $parent
	 *
	 * @param string $sub
	 * @param string $parent
	 * @return bool
	 */
	public static function isSubDirectory($sub, $parent) {
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
	 * Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
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
	 * replaces a copy of string delimited by the start and (optionally) length parameters with the string given in replacement.
	 *
	 * @param string $string
	 * @param string $replacement The replacement string.
	 * @param int $start If start is positive, the replacing will begin at the start'th offset into string. If start is negative, the replacing will begin at the start'th character from the end of string.
	 * @param int $length Length of the part to be replaced
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @return string
	 * @deprecated 8.2.0 Use substr_replace() instead.
	 */
	public static function mb_substr_replace($string, $replacement, $start, $length = 0, $encoding = 'UTF-8') {
		return substr_replace($string, $replacement, $start, $length);
	}

	/**
	 * Replace all occurrences of the search string with the replacement string
	 *
	 * @param string $search The value being searched for, otherwise known as the needle.
	 * @param string $replace The replacement
	 * @param string $subject The string or array being searched and replaced on, otherwise known as the haystack.
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @param int $count If passed, this will be set to the number of replacements performed.
	 * @return string
	 * @deprecated 8.2.0 Use str_replace() instead.
	 *
	 */
	public static function mb_str_replace($search, $replace, $subject, $encoding = 'UTF-8', &$count = null) {
		return str_replace($search, $replace, $subject, $count);
	}

	/**
	 * performs a search in a nested array
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
	 * calculates the maximum upload size respecting system settings, free space and user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @param int $freeSpace the number of bytes free on the storage holding $dir, if not set this will be received from the storage directly
	 * @return int number of bytes representing
	 */
	public static function maxUploadFilesize($dir, $freeSpace = null) {
		if (is_null($freeSpace) || $freeSpace < 0){
			$freeSpace = self::freeSpace($dir);
		}
		return min($freeSpace, self::uploadLimit());
	}

	/**
	 * Calculate free space left within user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @return int number of bytes representing
	 */
	public static function freeSpace($dir) {
		$freeSpace = \OC\Files\Filesystem::free_space($dir);
		if ($freeSpace !== \OCP\Files\FileInfo::SPACE_UNKNOWN) {
			$freeSpace = max($freeSpace, 0);
			return $freeSpace;
		} else {
			return (INF > 0)? INF: PHP_INT_MAX; // work around https://bugs.php.net/bug.php?id=69188
		}
	}

	/**
	 * Calculate PHP upload limit
	 *
	 * @return int PHP upload file size limit
	 */
	public static function uploadLimit() {
		$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
		$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
		if ((int)$upload_max_filesize === 0 and (int)$post_max_size === 0) {
			return INF;
		} elseif ((int)$upload_max_filesize === 0 or (int)$post_max_size === 0) {
			return max($upload_max_filesize, $post_max_size); //only the non 0 value counts
		} else {
			return min($upload_max_filesize, $post_max_size);
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
	 * Try to find a program
	 * Note: currently windows is not supported
	 *
	 * @param string $program
	 * @return null|string
	 */
	public static function findBinaryPath($program) {
		$memcache = \OC::$server->getMemCacheFactory()->create('findBinaryPath');
		if ($memcache->hasKey($program)) {
			return $memcache->get($program);
		}
		$result = null;
		if (!\OC_Util::runningOnWindows() && self::is_function_enabled('exec')) {
			$exeSniffer = new ExecutableFinder();
			// Returns null if nothing is found
			$result = $exeSniffer->find($program);
			if (empty($result)) {
				$paths = getenv('PATH');
				if (empty($paths)) {
					$paths = '/usr/local/bin /usr/bin /opt/bin /bin';
				} else {
					$paths = str_replace(':',' ',getenv('PATH'));
				}
				$command = 'find ' . $paths . ' -name ' . escapeshellarg($program) . ' 2> /dev/null';
				exec($command, $output, $returnCode);
				if (count($output) > 0) {
					$result = escapeshellcmd($output[0]);
				}
			}
		}
		$memcache->set($program, $result, 3600);
		return $result;
	}

	/**
	 * Calculate the disc space for the given path
	 *
	 * @param string $path
	 * @param \OCP\Files\FileInfo $rootInfo (optional)
	 * @return array
	 * @throws \OCP\Files\NotFoundException
	 */
	public static function getStorageInfo($path, $rootInfo = null) {
		// return storage info without adding mount points
		$includeExtStorage = \OC_Config::getValue('quota_include_external_storage', false);

		if (!$rootInfo) {
			$rootInfo = \OC\Files\Filesystem::getFileInfo($path, false);
		}
		if (!$rootInfo instanceof \OCP\Files\FileInfo) {
			throw new \OCP\Files\NotFoundException();
		}
		$used = $rootInfo->getSize();
		if ($used < 0) {
			$used = 0;
		}
		$quota = 0;
		$storage = $rootInfo->getStorage();
		if ($includeExtStorage && $storage->instanceOfStorage('\OC\Files\Storage\Shared')) {
			$includeExtStorage = false;
		}
		if ($includeExtStorage) {
			$quota = OC_Util::getUserQuota(\OCP\User::getUser());
			if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
				// always get free space / total space from root + mount points
				return self::getGlobalStorageInfo();
			}
		}

		// TODO: need a better way to get total space from storage
		if ($storage->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota')) {
			/** @var \OC\Files\Storage\Wrapper\Quota $storage */
			$quota = $storage->getQuota();
		}
		$free = $storage->free_space('');
		if ($free >= 0) {
			$total = $free + $used;
		} else {
			$total = $free; //either unknown or unlimited
		}
		if ($total > 0) {
			if ($quota > 0 && $total > $quota) {
				$total = $quota;
			}
			// prevent division by zero or error codes (negative values)
			$relative = round(($used / $total) * 10000) / 100;
		} else {
			$relative = 0;
		}

		$ownerId = $storage->getOwner($path);
		$ownerDisplayName = '';
		$owner = \OC::$server->getUserManager()->get($ownerId);
		if($owner) {
			$ownerDisplayName = $owner->getDisplayName();
		}

		return [
			'free' => $free,
			'used' => $used,
			'total' => $total,
			'relative' => $relative,
			'owner' => $ownerId,
			'ownerDisplayName' => $ownerDisplayName,
		];
	}

	/**
	 * Get storage info including all mount points and quota
	 *
	 * @return array
	 */
	private static function getGlobalStorageInfo() {
		$quota = OC_Util::getUserQuota(\OCP\User::getUser());

		$rootInfo = \OC\Files\Filesystem::getFileInfo('', 'ext');
		$used = $rootInfo['size'];
		if ($used < 0) {
			$used = 0;
		}

		$total = $quota;
		$free = $quota - $used;

		if ($total > 0) {
			if ($quota > 0 && $total > $quota) {
				$total = $quota;
			}
			// prevent division by zero or error codes (negative values)
			$relative = round(($used / $total) * 10000) / 100;
		} else {
			$relative = 0;
		}

		return array('free' => $free, 'used' => $used, 'total' => $total, 'relative' => $relative);

	}

	/**
	 * Returns whether the config file is set manually to read-only
	 * @return bool
	 */
	public static function isReadOnlyConfigEnabled() {
		return \OC::$server->getConfig()->getSystemValue('config_is_read_only', false);
	}
}
