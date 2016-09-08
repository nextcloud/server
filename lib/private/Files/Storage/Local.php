<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Brice Maron <brice@bmaron.net>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Klaas Freitag <freitag@owncloud.com>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sjors van der Pluijm <sjors@desjors.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tigran Mkrtchyan <tigran.mkrtchyan@desy.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Files\Storage;

use OCP\Files\ForbiddenException;

/**
 * for local filestore, we only have to map the paths
 */
class Local extends \OC\Files\Storage\Common {
	protected $datadir;

	protected $dataDirLength;

	protected $allowSymlinks = false;

	protected $realDataDir;

	public function __construct($arguments) {
		if (!isset($arguments['datadir']) || !is_string($arguments['datadir'])) {
			throw new \InvalidArgumentException('No data directory set for local storage');
		}
		$this->datadir = $arguments['datadir'];
		// some crazy code uses a local storage on root...
		if ($this->datadir === '/') {
			$this->realDataDir = $this->datadir;
		} else {
			$this->realDataDir = rtrim(realpath($this->datadir), '/') . '/';
		}
		if (substr($this->datadir, -1) !== '/') {
			$this->datadir .= '/';
		}
		$this->dataDirLength = strlen($this->realDataDir);
	}

	public function __destruct() {
	}

	public function getId() {
		return 'local::' . $this->datadir;
	}

	public function mkdir($path) {
		return @mkdir($this->getSourcePath($path), 0777, true);
	}

	public function rmdir($path) {
		if (!$this->isDeletable($path)) {
			return false;
		}
		try {
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->getSourcePath($path)),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			/**
			 * RecursiveDirectoryIterator on an NFS path isn't iterable with foreach
			 * This bug is fixed in PHP 5.5.9 or before
			 * See #8376
			 */
			$it->rewind();
			while ($it->valid()) {
				/**
				 * @var \SplFileInfo $file
				 */
				$file = $it->current();
				if (in_array($file->getBasename(), array('.', '..'))) {
					$it->next();
					continue;
				} elseif ($file->isDir()) {
					rmdir($file->getPathname());
				} elseif ($file->isFile() || $file->isLink()) {
					unlink($file->getPathname());
				}
				$it->next();
			}
			return rmdir($this->getSourcePath($path));
		} catch (\UnexpectedValueException $e) {
			return false;
		}
	}

	public function opendir($path) {
		return opendir($this->getSourcePath($path));
	}

	public function is_dir($path) {
		if (substr($path, -1) == '/') {
			$path = substr($path, 0, -1);
		}
		return is_dir($this->getSourcePath($path));
	}

	public function is_file($path) {
		return is_file($this->getSourcePath($path));
	}

	public function stat($path) {
		clearstatcache();
		$fullPath = $this->getSourcePath($path);
		$statResult = stat($fullPath);
		if (PHP_INT_SIZE === 4 && !$this->is_dir($path)) {
			$filesize = $this->filesize($path);
			$statResult['size'] = $filesize;
			$statResult[7] = $filesize;
		}
		return $statResult;
	}

	public function filetype($path) {
		$filetype = filetype($this->getSourcePath($path));
		if ($filetype == 'link') {
			$filetype = filetype(realpath($this->getSourcePath($path)));
		}
		return $filetype;
	}

	public function filesize($path) {
		if ($this->is_dir($path)) {
			return 0;
		}
		$fullPath = $this->getSourcePath($path);
		if (PHP_INT_SIZE === 4) {
			$helper = new \OC\LargeFileHelper;
			return $helper->getFilesize($fullPath);
		}
		return filesize($fullPath);
	}

	public function isReadable($path) {
		return is_readable($this->getSourcePath($path));
	}

	public function isUpdatable($path) {
		return is_writable($this->getSourcePath($path));
	}

	public function file_exists($path) {
		return file_exists($this->getSourcePath($path));
	}

	public function filemtime($path) {
		clearstatcache($this->getSourcePath($path));
		return $this->file_exists($path) ? filemtime($this->getSourcePath($path)) : false;
	}

	public function touch($path, $mtime = null) {
		// sets the modification time of the file to the given value.
		// If mtime is nil the current time is set.
		// note that the access time of the file always changes to the current time.
		if ($this->file_exists($path) and !$this->isUpdatable($path)) {
			return false;
		}
		if (!is_null($mtime)) {
			$result = touch($this->getSourcePath($path), $mtime);
		} else {
			$result = touch($this->getSourcePath($path));
		}
		if ($result) {
			clearstatcache(true, $this->getSourcePath($path));
		}

		return $result;
	}

	public function file_get_contents($path) {
		// file_get_contents() has a memory leak: https://bugs.php.net/bug.php?id=61961
		$fileName = $this->getSourcePath($path);

		$fileSize = filesize($fileName);
		if ($fileSize === 0) {
			return '';
		}

		$handle = fopen($fileName, 'rb');
		$content = fread($handle, $fileSize);
		fclose($handle);
		return $content;
	}

	public function file_put_contents($path, $data) {
		return file_put_contents($this->getSourcePath($path), $data);
	}

	public function unlink($path) {
		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		} else if ($this->is_file($path)) {
			return unlink($this->getSourcePath($path));
		} else {
			return false;
		}

	}

	public function rename($path1, $path2) {
		$srcParent = dirname($path1);
		$dstParent = dirname($path2);

		if (!$this->isUpdatable($srcParent)) {
			\OCP\Util::writeLog('core', 'unable to rename, source directory is not writable : ' . $srcParent, \OCP\Util::ERROR);
			return false;
		}

		if (!$this->isUpdatable($dstParent)) {
			\OCP\Util::writeLog('core', 'unable to rename, destination directory is not writable : ' . $dstParent, \OCP\Util::ERROR);
			return false;
		}

		if (!$this->file_exists($path1)) {
			\OCP\Util::writeLog('core', 'unable to rename, file does not exists : ' . $path1, \OCP\Util::ERROR);
			return false;
		}

		if ($this->is_dir($path2)) {
			$this->rmdir($path2);
		} else if ($this->is_file($path2)) {
			$this->unlink($path2);
		}

		if ($this->is_dir($path1)) {
			// we can't move folders across devices, use copy instead
			$stat1 = stat(dirname($this->getSourcePath($path1)));
			$stat2 = stat(dirname($this->getSourcePath($path2)));
			if ($stat1['dev'] !== $stat2['dev']) {
				$result = $this->copy($path1, $path2);
				if ($result) {
					$result &= $this->rmdir($path1);
				}
				return $result;
			}
		}

		return rename($this->getSourcePath($path1), $this->getSourcePath($path2));
	}

	public function copy($path1, $path2) {
		if ($this->is_dir($path1)) {
			return parent::copy($path1, $path2);
		} else {
			return copy($this->getSourcePath($path1), $this->getSourcePath($path2));
		}
	}

	public function fopen($path, $mode) {
		return fopen($this->getSourcePath($path), $mode);
	}

	public function hash($type, $path, $raw = false) {
		return hash_file($type, $this->getSourcePath($path), $raw);
	}

	public function free_space($path) {
		$sourcePath = $this->getSourcePath($path);
		// using !is_dir because $sourcePath might be a part file or
		// non-existing file, so we'd still want to use the parent dir
		// in such cases
		if (!is_dir($sourcePath)) {
			// disk_free_space doesn't work on files
			$sourcePath = dirname($sourcePath);
		}
		$space = @disk_free_space($sourcePath);
		if ($space === false || is_null($space)) {
			return \OCP\Files\FileInfo::SPACE_UNKNOWN;
		}
		return $space;
	}

	public function search($query) {
		return $this->searchInDir($query);
	}

	public function getLocalFile($path) {
		return $this->getSourcePath($path);
	}

	public function getLocalFolder($path) {
		return $this->getSourcePath($path);
	}

	/**
	 * @param string $query
	 * @param string $dir
	 * @return array
	 */
	protected function searchInDir($query, $dir = '') {
		$files = array();
		$physicalDir = $this->getSourcePath($dir);
		foreach (scandir($physicalDir) as $item) {
			if (\OC\Files\Filesystem::isIgnoredDir($item))
				continue;
			$physicalItem = $physicalDir . '/' . $item;

			if (strstr(strtolower($item), strtolower($query)) !== false) {
				$files[] = $dir . '/' . $item;
			}
			if (is_dir($physicalItem)) {
				$files = array_merge($files, $this->searchInDir($query, $dir . '/' . $item));
			}
		}
		return $files;
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		if ($this->file_exists($path)) {
			return $this->filemtime($path) > $time;
		} else {
			return true;
		}
	}

	/**
	 * Get the source path (on disk) of a given path
	 *
	 * @param string $path
	 * @return string
	 * @throws ForbiddenException
	 */
	public function getSourcePath($path) {
		$fullPath = $this->datadir . $path;
		if ($this->allowSymlinks || $path === '') {
			return $fullPath;
		}
		$pathToResolve = $fullPath;
		$realPath = realpath($pathToResolve);
		while ($realPath === false) { // for non existing files check the parent directory
			$pathToResolve = dirname($pathToResolve);
			$realPath = realpath($pathToResolve);
		}
		if ($realPath) {
			$realPath = $realPath . '/';
		}
		if (substr($realPath, 0, $this->dataDirLength) === $this->realDataDir) {
			return $fullPath;
		} else {
			throw new ForbiddenException("Following symlinks is not allowed ('$fullPath' -> '$realPath' not inside '{$this->realDataDir}')", false);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLocal() {
		return true;
	}

	/**
	 * get the ETag for a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getETag($path) {
		if ($this->is_file($path)) {
			$stat = $this->stat($path);
			return md5(
				$stat['mtime'] .
				$stat['ino'] .
				$stat['dev'] .
				$stat['size']
			);
		} else {
			return parent::getETag($path);
		}
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage->instanceOfStorage('\OC\Files\Storage\Local')) {
			/**
			 * @var \OC\Files\Storage\Local $sourceStorage
			 */
			$rootStorage = new Local(['datadir' => '/']);
			return $rootStorage->copy($sourceStorage->getSourcePath($sourceInternalPath), $this->getSourcePath($targetInternalPath));
		} else {
			return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage->instanceOfStorage('\OC\Files\Storage\Local')) {
			/**
			 * @var \OC\Files\Storage\Local $sourceStorage
			 */
			$rootStorage = new Local(['datadir' => '/']);
			return $rootStorage->rename($sourceStorage->getSourcePath($sourceInternalPath), $this->getSourcePath($targetInternalPath));
		} else {
			return parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
	}
}
