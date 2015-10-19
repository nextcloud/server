<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Nicolai Ehemann <en@enlightened.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thibaut GRIDEL <tgridel@free.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

use OC\Files\View;
use OC\Streamer;
use OCP\Lock\ILockingProvider;

/**
 * Class for file server access
 *
 */
class OC_Files {
	const FILE = 1;
	const ZIP_FILES = 2;
	const ZIP_DIR = 3;

	const UPLOAD_MIN_LIMIT_BYTES = 1048576; // 1 MiB

	/**
	 * @param string $filename
	 * @param string $name
	 */
	private static function sendHeaders($filename, $name) {
		OC_Response::setContentDispositionHeader($name, 'attachment');
		header('Content-Transfer-Encoding: binary');
		OC_Response::disableCaching();
		$fileSize = \OC\Files\Filesystem::filesize($filename);
		$type = \OC::$server->getMimeTypeDetector()->getSecureMimeType(\OC\Files\Filesystem::getMimeType($filename));
		header('Content-Type: '.$type);
		if ($fileSize > -1) {
			OC_Response::setContentLengthHeader($fileSize);
		}
	}

	/**
	 * return the content of a file or return a zip file containing multiple files
	 *
	 * @param string $dir
	 * @param string $files ; separated list of files to download
	 * @param boolean $onlyHeader ; boolean to only send header of the request
	 */
	public static function get($dir, $files, $onlyHeader = false) {

		$view = \OC\Files\Filesystem::getView();
		$getType = self::FILE;
		$filename = $dir;
		try {

			if (is_array($files) && count($files) === 1) {
				$files = $files[0];
			}

			if (!is_array($files)) {
				$filename = $dir . '/' . $files;
				if (!$view->is_dir($filename)) {
					self::getSingleFile($view, $dir, $files, $onlyHeader);
					return;
				}
			}

			$name = 'download';
			if (is_array($files)) {
				$getType = self::ZIP_FILES;
				$basename = basename($dir);
				if ($basename) {
					$name = $basename;
				}

				$filename = $dir . '/' . $name;
			} else {
				$filename = $dir . '/' . $files;
				$getType = self::ZIP_DIR;
				// downloading root ?
				if ($files !== '') {
					$name = $files;
				}
			}

			$streamer = new Streamer();
			OC_Util::obEnd();

			self::lockFiles($view, $dir, $files);

			$streamer->sendHeaders($name);
			$executionTime = intval(ini_get('max_execution_time'));
			set_time_limit(0);
			if ($getType === self::ZIP_FILES) {
				foreach ($files as $file) {
					$file = $dir . '/' . $file;
					if (\OC\Files\Filesystem::is_file($file)) {
						$fileSize = \OC\Files\Filesystem::filesize($file);
						$fh = \OC\Files\Filesystem::fopen($file, 'r');
						$streamer->addFileFromStream($fh, basename($file), $fileSize);
						fclose($fh);
					} elseif (\OC\Files\Filesystem::is_dir($file)) {
						$streamer->addDirRecursive($file);
					}
				}
			} elseif ($getType === self::ZIP_DIR) {
				$file = $dir . '/' . $files;
				$streamer->addDirRecursive($file);
			}
			$streamer->finalize();
			set_time_limit($executionTime);
			self::unlockAllTheFiles($dir, $files, $getType, $view, $filename);
		} catch (\OCP\Lock\LockedException $ex) {
			self::unlockAllTheFiles($dir, $files, $getType, $view, $filename);
			OC::$server->getLogger()->logException($ex);
			$l = \OC::$server->getL10N('core');
			$hint = method_exists($ex, 'getHint') ? $ex->getHint() : '';
			\OC_Template::printErrorPage($l->t('File is currently busy, please try again later'), $hint);
		} catch (\Exception $ex) {
			self::unlockAllTheFiles($dir, $files, $getType, $view, $filename);
			OC::$server->getLogger()->logException($ex);
			$l = \OC::$server->getL10N('core');
			$hint = method_exists($ex, 'getHint') ? $ex->getHint() : '';
			\OC_Template::printErrorPage($l->t('Can\'t read file'), $hint);
		}
	}

	/**
	 * @param View $view
	 * @param string $name
	 */
	private static function getSingleFile($view, $dir, $name, $onlyHeader) {
		$filename = $dir . '/' . $name;
		OC_Util::obEnd();
		$view->lockFile($filename, ILockingProvider::LOCK_SHARED);

		if (\OC\Files\Filesystem::isReadable($filename)) {
			self::sendHeaders($filename, $name);
		} elseif (!\OC\Files\Filesystem::file_exists($filename)) {
			header("HTTP/1.0 404 Not Found");
			$tmpl = new OC_Template('', '404', 'guest');
			$tmpl->printPage();
			exit();
		} else {
			header("HTTP/1.0 403 Forbidden");
			die('403 Forbidden');
		}
		if ($onlyHeader) {
			return;
		}
		$view->readfile($filename);
	}

	/**
	 * @param View $view
	 * @param $dir
	 * @param string[]|string $files
	 */
	public static function lockFiles($view, $dir, $files) {
		if (!is_array($files)) {
			$file = $dir . '/' . $files;
			$files = [$file];
		}
		foreach ($files as $file) {
			$file = $dir . '/' . $file;
			$view->lockFile($file, ILockingProvider::LOCK_SHARED);
			if ($view->is_dir($file)) {
				$contents = $view->getDirectoryContent($file);
				$contents = array_map(function($fileInfo) use ($file) {
					/** @var \OCP\Files\FileInfo $fileInfo */
					return $file . '/' . $fileInfo->getName();
				}, $contents);
				self::lockFiles($view, $dir, $contents);
			}
		}
	}

	/**
	 * set the maximum upload size limit for apache hosts using .htaccess
	 *
	 * @param int $size file size in bytes
	 * @param array $files override '.htaccess' and '.user.ini' locations
	 * @return bool false on failure, size on success
	 */
	public static function setUploadLimit($size, $files = []) {
		//don't allow user to break his config
		$size = intval($size);
		if ($size < self::UPLOAD_MIN_LIMIT_BYTES) {
			return false;
		}
		$size = OC_Helper::phpFileSize($size);

		$phpValueKeys = array(
			'upload_max_filesize',
			'post_max_size'
		);

		// default locations if not overridden by $files
		$files = array_merge([
			'.htaccess' => OC::$SERVERROOT . '/.htaccess',
			'.user.ini' => OC::$SERVERROOT . '/.user.ini'
		], $files);

		$updateFiles = [
			$files['.htaccess'] => [
				'pattern' => '/php_value %1$s (\S)*/',
				'setting' => 'php_value %1$s %2$s'
			],
			$files['.user.ini'] => [
				'pattern' => '/%1$s=(\S)*/',
				'setting' => '%1$s=%2$s'
			]
		];

		$success = true;

		foreach ($updateFiles as $filename => $patternMap) {
			// suppress warnings from fopen()
			$handle = @fopen($filename, 'r+');
			if (!$handle) {
				\OCP\Util::writeLog('files',
					'Can\'t write upload limit to ' . $filename . '. Please check the file permissions',
					\OCP\Util::WARN);
				$success = false;
				continue; // try to update as many files as possible
			}

			$content = '';
			while (!feof($handle)) {
				$content .= fread($handle, 1000);
			}

			foreach ($phpValueKeys as $key) {
				$pattern = vsprintf($patternMap['pattern'], [$key]);
				$setting = vsprintf($patternMap['setting'], [$key, $size]);
				$hasReplaced = 0;
				$newContent = preg_replace($pattern, $setting, $content, 1, $hasReplaced);
				if ($newContent !== null) {
					$content = $newContent;
				}
				if ($hasReplaced === 0) {
					$content .= "\n" . $setting;
				}
			}

			// write file back
			ftruncate($handle, 0);
			rewind($handle);
			fwrite($handle, $content);

			fclose($handle);
		}

		if ($success) {
			return OC_Helper::computerFileSize($size);
		}
		return false;
	}

	/**
	 * @param $dir
	 * @param $files
	 * @param $getType
	 * @param View $view
	 * @param $filename
	 */
	private static function unlockAllTheFiles($dir, $files, $getType, $view, $filename) {
		if ($getType === self::FILE) {
			$view->unlockFile($filename, ILockingProvider::LOCK_SHARED);
		}
		if ($getType === self::ZIP_FILES) {
			foreach ($files as $file) {
				$file = $dir . '/' . $file;
				$view->unlockFile($file, ILockingProvider::LOCK_SHARED);
			}
		}
		if ($getType === self::ZIP_DIR) {
			$file = $dir . '/' . $files;
			$view->unlockFile($file, ILockingProvider::LOCK_SHARED);
		}
	}

}
