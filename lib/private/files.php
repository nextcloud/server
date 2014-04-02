<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
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
 * Class for fileserver access
 *
 */
class OC_Files {
	static $tmpFiles = array();

	static public function getFileInfo($path, $includeMountPoints = true){
		return \OC\Files\Filesystem::getFileInfo($path, $includeMountPoints);
	}

	static public function getDirectoryContent($path){
		return \OC\Files\Filesystem::getDirectoryContent($path);
	}

	/**
	 * return the content of a file or return a zip file containing multiple files
	 *
	 * @param string $dir
	 * @param string $file ; separated list of files to download
	 * @param boolean $only_header ; boolean to only send header of the request
	 */
	public static function get($dir, $files, $only_header = false) {
		$xsendfile = false;
		if (isset($_SERVER['MOD_X_SENDFILE_ENABLED']) ||
			isset($_SERVER['MOD_X_SENDFILE2_ENABLED']) ||
			isset($_SERVER['MOD_X_ACCEL_REDIRECT_ENABLED'])) {
			$xsendfile = true;
		}

		if (is_array($files) && count($files) == 1) {
			$files = $files[0];
		}

		if (is_array($files)) {
			self::validateZipDownload($dir, $files);
			$executionTime = intval(ini_get('max_execution_time'));
			set_time_limit(0);
			$zip = new ZipArchive();
			$filename = OC_Helper::tmpFile('.zip');
			if ($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==true) {
				$l = OC_L10N::get('lib');
				throw new Exception($l->t('cannot open "%s"', array($filename)));
			}
			foreach ($files as $file) {
				$file = $dir . '/' . $file;
				if (\OC\Files\Filesystem::is_file($file)) {
					$tmpFile = \OC\Files\Filesystem::toTmpFile($file);
					self::$tmpFiles[] = $tmpFile;
					$zip->addFile($tmpFile, basename($file));
				} elseif (\OC\Files\Filesystem::is_dir($file)) {
					self::zipAddDir($file, $zip);
				}
			}
			$zip->close();
			if ($xsendfile) {
				$filename = OC_Helper::moveToNoClean($filename);
			}
			$basename = basename($dir);
			if ($basename) {
				$name = $basename . '.zip';
			} else {
				$name = 'download.zip';
			}
			
			set_time_limit($executionTime);
		} elseif (\OC\Files\Filesystem::is_dir($dir . '/' . $files)) {
			self::validateZipDownload($dir, $files);
			$executionTime = intval(ini_get('max_execution_time'));
			set_time_limit(0);
			$zip = new ZipArchive();
			$filename = OC_Helper::tmpFile('.zip');
			if ($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==true) {
				$l = OC_L10N::get('lib');
				throw new Exception($l->t('cannot open "%s"', array($filename)));
			}
			$file = $dir . '/' . $files;
			self::zipAddDir($file, $zip);
			$zip->close();
			if ($xsendfile) {
				$filename = OC_Helper::moveToNoClean($filename);
			}
			$name = $files . '.zip';
			set_time_limit($executionTime);
		} else {
			$zip = false;
			$filename = $dir . '/' . $files;
			$name = $files;
			if ($xsendfile && OC_App::isEnabled('files_encryption')) {
				$xsendfile = false;
			}
		}
		OC_Util::obEnd();
		if ($zip or \OC\Files\Filesystem::isReadable($filename)) {
			OC_Response::setContentDispositionHeader($name, 'attachment');
			header('Content-Transfer-Encoding: binary');
			OC_Response::disableCaching();
			if ($zip) {
				ini_set('zlib.output_compression', 'off');
				header('Content-Type: application/zip');
				header('Content-Length: ' . filesize($filename));
				self::addSendfileHeader($filename);
			}else{
				$filesize = \OC\Files\Filesystem::filesize($filename);
				header('Content-Type: '.\OC\Files\Filesystem::getMimeType($filename));
				if ($filesize > -1) {
					header("Content-Length: ".$filesize);
				}
				if ($xsendfile) {
					list($storage) = \OC\Files\Filesystem::resolvePath(\OC\Files\Filesystem::getView()->getAbsolutePath($filename));
					if ($storage instanceof \OC\Files\Storage\Wrapper\Wrapper) {
						$storage = $storage->getWrapperStorage();
					}
					if ($storage instanceof \OC\Files\Storage\Local) {
						self::addSendfileHeader(\OC\Files\Filesystem::getLocalFile($filename));
					}
				}
			}
		} elseif ($zip or !\OC\Files\Filesystem::file_exists($filename)) {
			header("HTTP/1.0 404 Not Found");
			$tmpl = new OC_Template('', '404', 'guest');
			$tmpl->assign('file', $name);
			$tmpl->printPage();
		} else {
			header("HTTP/1.0 403 Forbidden");
			die('403 Forbidden');
		}
		if($only_header) {
			return ;
		}
		if ($zip) {
			$handle = fopen($filename, 'r');
			if ($handle) {
				$chunkSize = 8 * 1024; // 1 MB chunks
				while (!feof($handle)) {
					echo fread($handle, $chunkSize);
					flush();
				}
			}
			if (!$xsendfile) {
				unlink($filename);
			}
		}else{
			\OC\Files\Filesystem::readfile($filename);
		}
		foreach (self::$tmpFiles as $tmpFile) {
			if (file_exists($tmpFile) and is_file($tmpFile)) {
				unlink($tmpFile);
			}
		}
	}

	private static function addSendfileHeader($filename) {
		if (isset($_SERVER['MOD_X_SENDFILE_ENABLED'])) {
			header("X-Sendfile: " . $filename);
 		}
 		if (isset($_SERVER['MOD_X_SENDFILE2_ENABLED'])) {
			if (isset($_SERVER['HTTP_RANGE']) && 
				preg_match("/^bytes=([0-9]+)-([0-9]*)$/", $_SERVER['HTTP_RANGE'], $range)) {
				$filelength = filesize($filename);
 				if ($range[2] == "") {
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
			header("X-Accel-Redirect: " . $filename);
		}
	}

	public static function zipAddDir($dir, $zip, $internalDir='') {
		$dirname=basename($dir);
		$zip->addEmptyDir($internalDir.$dirname);
		$internalDir.=$dirname.='/';
		$files=OC_Files::getDirectoryContent($dir);
		foreach($files as $file) {
			$filename=$file['name'];
			$file=$dir.'/'.$filename;
			if(\OC\Files\Filesystem::is_file($file)) {
				$tmpFile=\OC\Files\Filesystem::toTmpFile($file);
				OC_Files::$tmpFiles[]=$tmpFile;
				$zip->addFile($tmpFile, $internalDir.$filename);
			}elseif(\OC\Files\Filesystem::is_dir($file)) {
				self::zipAddDir($file, $zip, $internalDir);
			}
		}
	}

	/**
	 * checks if the selected files are within the size constraint. If not, outputs an error page.
	 *
	 * @param dir   $dir
	 * @param files $files
	 */
	static function validateZipDownload($dir, $files) {
		if (!OC_Config::getValue('allowZipDownload', true)) {
			$l = OC_L10N::get('lib');
			header("HTTP/1.0 409 Conflict");
			OC_Template::printErrorPage(
					$l->t('ZIP download is turned off.'),
					$l->t('Files need to be downloaded one by one.')
						. '<br/><a href="javascript:history.back()">' . $l->t('Back to Files') . '</a>'
			);
			exit;
		}

		$zipLimit = OC_Config::getValue('maxZipInputSize', OC_Helper::computerFileSize('800 MB'));
		if ($zipLimit > 0) {
			$totalsize = 0;
			if(!is_array($files)) {
				$files = array($files);
			}
			foreach ($files as $file) {
				$path = $dir . '/' . $file;
				if(\OC\Files\Filesystem::is_dir($path)) {
					foreach (\OC\Files\Filesystem::getDirectoryContent($path) as $i) {
						$totalsize += $i['size'];
					}
				} else {
					$totalsize += \OC\Files\Filesystem::filesize($path);
				}
			}
			if ($totalsize > $zipLimit) {
				$l = OC_L10N::get('lib');
				header("HTTP/1.0 409 Conflict");
				OC_Template::printErrorPage(
						$l->t('Selected files too large to generate zip file.'),
						$l->t('Please download the files separately in smaller chunks or kindly ask your administrator.')
						.'<br/><a href="javascript:history.back()">'
						. $l->t('Back to Files') . '</a>'
				);
				exit;
			}
		}
	}

	/**
	 * set the maximum upload size limit for apache hosts using .htaccess
	 *
	 * @param int size filesisze in bytes
	 * @return false on failure, size on success
	 */
	static function setUploadLimit($size) {
		//don't allow user to break his config -- upper boundary
		if ($size > PHP_INT_MAX) {
			//max size is always 1 byte lower than computerFileSize returns
			if ($size > PHP_INT_MAX + 1)
				return false;
			$size -= 1;
		} else {
			$size = OC_Helper::phpFileSize($size);
		}

		//don't allow user to break his config -- broken or malicious size input
		if (intval($size) == 0) {
			return false;
		}

		$htaccess = @file_get_contents(OC::$SERVERROOT . '/.htaccess'); //supress errors in case we don't have permissions for
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
			if ($hasReplaced == 0) {
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
