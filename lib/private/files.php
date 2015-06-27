<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author dratini0 <dratini0@gmail.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author mvn23 <schopdiedwaas@gmail.com>
 * @author Nicolai Ehemann <en@enlightened.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thibaut GRIDEL <tgridel@free.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

// TODO: get rid of this using proper composer packages
require_once 'mcnetic/phpzipstreamer/ZipStreamer.php';

use OC\Lock\NoopLockingProvider;
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
	 * @param bool $zip
	 */
	private static function sendHeaders($filename, $name, $zip = false) {
		OC_Response::setContentDispositionHeader($name, 'attachment');
		header('Content-Transfer-Encoding: binary');
		OC_Response::disableCaching();
		if ($zip) {
			header('Content-Type: application/zip');
		} else {
			$filesize = \OC\Files\Filesystem::filesize($filename);
			header('Content-Type: '.\OC_Helper::getSecureMimeType(\OC\Files\Filesystem::getMimeType($filename)));
			if ($filesize > -1) {
				OC_Response::setContentLengthHeader($filesize);
			}
		}
	}

	/**
	 * return the content of a file or return a zip file containing multiple files
	 *
	 * @param string $dir
	 * @param string $files ; separated list of files to download
	 * @param boolean $only_header ; boolean to only send header of the request
	 */
	public static function get($dir, $files, $only_header = false) {
		$view = \OC\Files\Filesystem::getView();
		$xsendfile = false;
		if (\OC::$server->getLockingProvider() instanceof NoopLockingProvider) {
			if (isset($_SERVER['MOD_X_SENDFILE_ENABLED']) ||
				isset($_SERVER['MOD_X_SENDFILE2_ENABLED']) ||
				isset($_SERVER['MOD_X_ACCEL_REDIRECT_ENABLED'])
			) {
				$xsendfile = true;
			}
		}

		if (is_array($files) && count($files) === 1) {
			$files = $files[0];
		}

		if (is_array($files)) {
			$get_type = self::ZIP_FILES;
			$basename = basename($dir);
			if ($basename) {
				$name = $basename . '.zip';
			} else {
				$name = 'download.zip';
			}

			$filename = $dir . '/' . $name;
		} else {
			$filename = $dir . '/' . $files;
			if (\OC\Files\Filesystem::is_dir($dir . '/' . $files)) {
				$get_type = self::ZIP_DIR;
				// downloading root ?
				if ($files === '') {
					$name = 'download.zip';
				} else {
					$name = $files . '.zip';
				}

			} else {
				$get_type = self::FILE;
				$name = $files;
			}
		}

		if ($get_type === self::FILE) {
			$zip = false;
			if ($xsendfile && \OC::$server->getEncryptionManager()->isEnabled()) {
				$xsendfile = false;
			}
		} else {
			$zip = new ZipStreamer(false);
		}
		OC_Util::obEnd();

		try {
			if ($get_type === self::FILE) {
				$view->lockFile($filename, ILockingProvider::LOCK_SHARED);
			}
			if ($zip or \OC\Files\Filesystem::isReadable($filename)) {
				self::sendHeaders($filename, $name, $zip);
			} elseif (!\OC\Files\Filesystem::file_exists($filename)) {
				header("HTTP/1.0 404 Not Found");
				$tmpl = new OC_Template('', '404', 'guest');
				$tmpl->printPage();
				exit();
			} else {
				header("HTTP/1.0 403 Forbidden");
				die('403 Forbidden');
			}
			if ($only_header) {
				return;
			}
			if ($zip) {
				$executionTime = intval(ini_get('max_execution_time'));
				set_time_limit(0);
				if ($get_type === self::ZIP_FILES) {
					foreach ($files as $file) {
						$file = $dir . '/' . $file;
						if (\OC\Files\Filesystem::is_file($file)) {
							$fh = \OC\Files\Filesystem::fopen($file, 'r');
							$zip->addFileFromStream($fh, basename($file));
							fclose($fh);
						} elseif (\OC\Files\Filesystem::is_dir($file)) {
							self::zipAddDir($file, $zip);
						}
					}
				} elseif ($get_type === self::ZIP_DIR) {
					$file = $dir . '/' . $files;
					self::zipAddDir($file, $zip);
				}
				$zip->finalize();
				set_time_limit($executionTime);
			} else {
				if ($xsendfile) {
					/** @var $storage \OC\Files\Storage\Storage */
					list($storage) = $view->resolvePath($filename);
					if ($storage->isLocal()) {
						self::addSendfileHeader($filename);
					} else {
						\OC\Files\Filesystem::readfile($filename);
					}
				} else {
					\OC\Files\Filesystem::readfile($filename);
				}
			}
			if ($get_type === self::FILE) {
				$view->unlockFile($filename, ILockingProvider::LOCK_SHARED);
			}
		} catch (\OCP\Lock\LockedException $ex) {
			$l = \OC::$server->getL10N('core');
			$hint = method_exists($ex, 'getHint') ? $ex->getHint() : '';
			\OC_Template::printErrorPage($l->t('File is currently busy, please try again later'), $hint);
		} catch (\Exception $ex) {
			$l = \OC::$server->getL10N('core');
			$hint = method_exists($ex, 'getHint') ? $ex->getHint() : '';
			\OC_Template::printErrorPage($l->t('Can\'t read file'), $hint);
		}
	}

	/**
	 * @param false|string $filename
	 */
	private static function addSendfileHeader($filename) {
		if (isset($_SERVER['MOD_X_SENDFILE_ENABLED'])) {
			$filename = \OC\Files\Filesystem::getLocalFile($filename);
			header("X-Sendfile: " . $filename);
 		}
 		if (isset($_SERVER['MOD_X_SENDFILE2_ENABLED'])) {
			$filename = \OC\Files\Filesystem::getLocalFile($filename);
			if (isset($_SERVER['HTTP_RANGE']) &&
				preg_match("/^bytes=([0-9]+)-([0-9]*)$/", $_SERVER['HTTP_RANGE'], $range)) {
				$filelength = filesize($filename);
 				if ($range[2] === "") {
 					$range[2] = $filelength - 1;
 				}
 				header("Content-Range: bytes $range[1]-$range[2]/" . $filelength);
 				header("HTTP/1.1 206 Partial content");
 				header("X-Sendfile2: " . str_replace(",", "%2c", rawurlencode($filename)) . " $range[1]-$range[2]");
 			} else {
 				header("X-Sendfile: " . $filename);
 			}
		}

		if (isset($_SERVER['MOD_X_ACCEL_REDIRECT_ENABLED'])) {
			if (isset($_SERVER['MOD_X_ACCEL_REDIRECT_PREFIX'])) {
				$filename = $_SERVER['MOD_X_ACCEL_REDIRECT_PREFIX'] . \OC\Files\Filesystem::getLocalFile($filename);
			} else {
				$filename = \OC::$WEBROOT . '/data' . \OC\Files\Filesystem::getRoot() . $filename;
			}
			header("X-Accel-Redirect: " . $filename);
		}
	}

	/**
	 * @param string $dir
	 * @param ZipStreamer $zip
	 * @param string $internalDir
	 */
	public static function zipAddDir($dir, ZipStreamer $zip, $internalDir='') {
		$dirname=basename($dir);
		$rootDir = $internalDir.$dirname;
		if (!empty($rootDir)) {
			$zip->addEmptyDir($rootDir);
		}
		$internalDir.=$dirname.='/';
		// prevent absolute dirs
		$internalDir = ltrim($internalDir, '/');

		$files=\OC\Files\Filesystem::getDirectoryContent($dir);
		foreach($files as $file) {
			$filename=$file['name'];
			$file=$dir.'/'.$filename;
			if(\OC\Files\Filesystem::is_file($file)) {
				$fh = \OC\Files\Filesystem::fopen($file, 'r');
				$zip->addFileFromStream($fh, $internalDir.$filename);
				fclose($fh);
			}elseif(\OC\Files\Filesystem::is_dir($file)) {
				self::zipAddDir($file, $zip, $internalDir);
			}
		}
	}

	/**
	 * set the maximum upload size limit for apache hosts using .htaccess
	 *
	 * @param int $size file size in bytes
	 * @return bool false on failure, size on success
	 */
	static function setUploadLimit($size) {
		//don't allow user to break his config
		if ($size > PHP_INT_MAX) {
			//max size is always 1 byte lower than computerFileSize returns
			if ($size > PHP_INT_MAX + 1)
				return false;
			$size -= 1;
		}
		if ($size < self::UPLOAD_MIN_LIMIT_BYTES) {
			return false;
		}
		$size = OC_Helper::phpFileSize($size);

		//don't allow user to break his config -- broken or malicious size input
		if (intval($size) === 0) {
			return false;
		}

		//suppress errors in case we don't have permissions for
		$htaccess = @file_get_contents(OC::$SERVERROOT . '/.htaccess');
		if (!$htaccess) {
			return false;
		}

		$phpValueKeys = array(
			'upload_max_filesize',
			'post_max_size'
		);

		foreach ($phpValueKeys as $key) {
			$pattern = '/php_value ' . $key . ' (\S)*/';
			$setting = 'php_value ' . $key . ' ' . $size;
			$hasReplaced = 0;
			$content = preg_replace($pattern, $setting, $htaccess, 1, $hasReplaced);
			if ($content !== null) {
				$htaccess = $content;
			}
			if ($hasReplaced === 0) {
				$htaccess .= "\n" . $setting;
			}
		}

		//check for write permissions
		if (is_writable(OC::$SERVERROOT . '/.htaccess')) {
			file_put_contents(OC::$SERVERROOT . '/.htaccess', $htaccess);
			return OC_Helper::computerFileSize($size);
		} else {
			OC_Log::write('files',
				'Can\'t write upload limit to ' . OC::$SERVERROOT . '/.htaccess. Please check the file permissions',
				OC_Log::WARN);
		}
		return false;
	}
}
