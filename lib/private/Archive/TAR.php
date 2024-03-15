<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Remco Brenninkmeijer <requist1@starmail.nl>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Archive;

use Icewind\Streams\CallbackWrapper;

class TAR extends Archive {
	public const PLAIN = 0;
	public const GZIP = 1;
	public const BZIP = 2;

	/**
	 * @var string[]|false
	 */
	private $fileList = false;
	/**
	 * @var array|false
	 */
	private $cachedHeaders = false;

	/**
	 * @var \Archive_Tar
	 */
	private $tar = null;

	/**
	 * @var string
	 */
	private $path;

	public function __construct(string $source) {
		$types = [null, 'gz', 'bz2'];
		$this->path = $source;
		$this->tar = new \Archive_Tar($source, $types[self::getTarType($source)]);
	}

	/**
	 * try to detect the type of tar compression
	 */
	public static function getTarType(string $file): int {
		if (strpos($file, '.')) {
			$extension = substr($file, strrpos($file, '.'));
			switch ($extension) {
				case '.gz':
				case '.tgz':
					return self::GZIP;
				case '.bz':
				case '.bz2':
					return self::BZIP;
				case '.tar':
					return self::PLAIN;
				default:
					return self::PLAIN;
			}
		} else {
			return self::PLAIN;
		}
	}

	/**
	 * add an empty folder to the archive
	 */
	public function addFolder(string $path): bool {
		$tmpBase = \OC::$server->getTempManager()->getTemporaryFolder();
		$path = rtrim($path, '/') . '/';
		if ($this->fileExists($path)) {
			return false;
		}
		$parts = explode('/', $path);
		$folder = $tmpBase;
		foreach ($parts as $part) {
			$folder .= '/' . $part;
			if (!is_dir($folder)) {
				mkdir($folder);
			}
		}
		$result = $this->tar->addModify([$tmpBase . $path], '', $tmpBase);
		rmdir($tmpBase . $path);
		$this->fileList = false;
		$this->cachedHeaders = false;
		return $result;
	}

	/**
	 * add a file to the archive
	 *
	 * @param string $source either a local file or string data
	 */
	public function addFile(string $path, string $source = ''): bool {
		if ($this->fileExists($path)) {
			$this->remove($path);
		}
		if ($source and $source[0] == '/' and file_exists($source)) {
			$source = file_get_contents($source);
		}
		$result = $this->tar->addString($path, $source);
		$this->fileList = false;
		$this->cachedHeaders = false;
		return $result;
	}

	/**
	 * rename a file or folder in the archive
	 */
	public function rename(string $source, string $dest): bool {
		//no proper way to delete, rename entire archive, rename file and remake archive
		$tmp = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->tar->extract($tmp);
		rename($tmp . $source, $tmp . $dest);
		$this->tar = null;
		unlink($this->path);
		$types = [null, 'gz', 'bz'];
		$this->tar = new \Archive_Tar($this->path, $types[self::getTarType($this->path)]);
		$this->tar->createModify([$tmp], '', $tmp . '/');
		$this->fileList = false;
		$this->cachedHeaders = false;
		return true;
	}

	private function getHeader(string $file): ?array {
		if (!$this->cachedHeaders) {
			$this->cachedHeaders = $this->tar->listContent();
		}
		foreach ($this->cachedHeaders as $header) {
			if ($file == $header['filename']
				or $file . '/' == $header['filename']
				or '/' . $file . '/' == $header['filename']
				or '/' . $file == $header['filename']
			) {
				return $header;
			}
		}
		return null;
	}

	/**
	 * get the uncompressed size of a file in the archive
	 */
	public function filesize(string $path): false|int|float {
		$stat = $this->getHeader($path);
		return $stat['size'] ?? false;
	}

	/**
	 * get the last modified time of a file in the archive
	 *
	 * @return int|false
	 */
	public function mtime(string $path) {
		$stat = $this->getHeader($path);
		return $stat['mtime'] ?? false;
	}

	/**
	 * get the files in a folder
	 */
	public function getFolder(string $path): array {
		$files = $this->getFiles();
		$folderContent = [];
		$pathLength = strlen($path);
		foreach ($files as $file) {
			if ($file[0] == '/') {
				$file = substr($file, 1);
			}
			if (substr($file, 0, $pathLength) == $path and $file != $path) {
				$result = substr($file, $pathLength);
				if ($pos = strpos($result, '/')) {
					$result = substr($result, 0, $pos + 1);
				}
				if (!in_array($result, $folderContent)) {
					$folderContent[] = $result;
				}
			}
		}
		return $folderContent;
	}

	/**
	 * get all files in the archive
	 */
	public function getFiles(): array {
		if ($this->fileList) {
			return $this->fileList;
		}
		if (!$this->cachedHeaders) {
			$this->cachedHeaders = $this->tar->listContent();
		}
		$files = [];
		foreach ($this->cachedHeaders as $header) {
			$files[] = $header['filename'];
		}
		$this->fileList = $files;
		return $files;
	}

	/**
	 * get the content of a file
	 *
	 * @return string|false
	 */
	public function getFile(string $path) {
		$string = $this->tar->extractInString($path);
		if (is_string($string)) {
			return $string;
		} else {
			return false;
		}
	}

	/**
	 * extract a single file from the archive
	 */
	public function extractFile(string $path, string $dest): bool {
		$tmp = \OC::$server->getTempManager()->getTemporaryFolder();
		if (!$this->fileExists($path)) {
			return false;
		}
		if ($this->fileExists('/' . $path)) {
			$success = $this->tar->extractList(['/' . $path], $tmp);
		} else {
			$success = $this->tar->extractList([$path], $tmp);
		}
		if ($success) {
			rename($tmp . $path, $dest);
		}
		\OCP\Files::rmdirr($tmp);
		return $success;
	}

	/**
	 * extract the archive
	 */
	public function extract(string $dest): bool {
		return $this->tar->extract($dest);
	}

	/**
	 * check if a file or folder exists in the archive
	 */
	public function fileExists(string $path): bool {
		$files = $this->getFiles();
		if ((in_array($path, $files)) or (in_array($path . '/', $files))) {
			return true;
		} else {
			$folderPath = rtrim($path, '/') . '/';
			$pathLength = strlen($folderPath);
			foreach ($files as $file) {
				if (strlen($file) > $pathLength and substr($file, 0, $pathLength) == $folderPath) {
					return true;
				}
			}
		}
		if ($path[0] != '/') { //not all programs agree on the use of a leading /
			return $this->fileExists('/' . $path);
		} else {
			return false;
		}
	}

	/**
	 * remove a file or folder from the archive
	 */
	public function remove(string $path): bool {
		if (!$this->fileExists($path)) {
			return false;
		}
		$this->fileList = false;
		$this->cachedHeaders = false;
		//no proper way to delete, extract entire archive, delete file and remake archive
		$tmp = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->tar->extract($tmp);
		\OCP\Files::rmdirr($tmp . $path);
		$this->tar = null;
		unlink($this->path);
		$this->reopen();
		$this->tar->createModify([$tmp], '', $tmp);
		return true;
	}

	/**
	 * get a file handler
	 *
	 * @return bool|resource
	 */
	public function getStream(string $path, string $mode) {
		$lastPoint = strrpos($path, '.');
		if ($lastPoint !== false) {
			$ext = substr($path, $lastPoint);
		} else {
			$ext = '';
		}
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
		if ($this->fileExists($path)) {
			$this->extractFile($path, $tmpFile);
		} elseif ($mode == 'r' or $mode == 'rb') {
			return false;
		}
		if ($mode == 'r' or $mode == 'rb') {
			return fopen($tmpFile, $mode);
		} else {
			$handle = fopen($tmpFile, $mode);
			return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile) {
				$this->writeBack($tmpFile, $path);
			});
		}
	}

	/**
	 * write back temporary files
	 */
	public function writeBack(string $tmpFile, string $path): void {
		$this->addFile($path, $tmpFile);
		unlink($tmpFile);
	}

	/**
	 * reopen the archive to ensure everything is written
	 */
	private function reopen(): void {
		if ($this->tar) {
			$this->tar->_close();
			$this->tar = null;
		}
		$types = [null, 'gz', 'bz'];
		$this->tar = new \Archive_Tar($this->path, $types[self::getTarType($this->path)]);
	}

	/**
	 * Get error object from archive_tar.
	 */
	public function getError(): ?\PEAR_Error {
		if ($this->tar instanceof \Archive_Tar && $this->tar->error_object instanceof \PEAR_Error) {
			return $this->tar->error_object;
		}
		return null;
	}
}
