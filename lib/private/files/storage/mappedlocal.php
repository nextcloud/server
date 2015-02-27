<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Files\Storage;

/**
 * for local filestore, we only have to map the paths
 */
class MappedLocal extends \OC\Files\Storage\Common {
	protected $datadir;
	private $mapper;

	public function __construct($arguments) {
		$this->datadir = $arguments['datadir'];
		if (substr($this->datadir, -1) !== '/') {
			$this->datadir .= '/';
		}

		$this->mapper = new \OC\Files\Mapper($this->datadir);
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
			if ($result = @rmdir($this->getSourcePath($path))) {
				$this->cleanMapper($path);
			}
			return $result;
		} catch (\UnexpectedValueException $e) {
			return false;
		}
	}

	public function opendir($path) {
		$files = array('.', '..');
		$physicalPath = $this->getSourcePath($path);

		$logicalPath = $this->mapper->physicalToLogic($physicalPath);
		$dh = opendir($physicalPath);
		if (is_resource($dh)) {
			while (($file = readdir($dh)) !== false) {
				if ($file === '.' or $file === '..') {
					continue;
				}

				$logicalFilePath = $this->mapper->physicalToLogic($physicalPath . '/' . $file);

				$file = $this->mapper->stripRootFolder($logicalFilePath, $logicalPath);
				$file = $this->stripLeading($file);
				$files[] = $file;
			}
		}

		\OC\Files\Stream\Dir::register('local-win32' . $path, $files);
		return opendir('fakedir://local-win32' . $path);
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
		return filemtime($this->getSourcePath($path));
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
		return file_get_contents($this->getSourcePath($path));
	}

	public function file_put_contents($path, $data) {
		return file_put_contents($this->getSourcePath($path), $data);
	}

	public function unlink($path) {
		return $this->delTree($path);
	}

	public function rename($path1, $path2) {
		$srcParent = $this->dirname($path1);
		$dstParent = $this->dirname($path2);

		if (!$this->isUpdatable($srcParent)) {
			\OC_Log::write('core', 'unable to rename, source directory is not writable : ' . $srcParent, \OC_Log::ERROR);
			return false;
		}

		if (!$this->isUpdatable($dstParent)) {
			\OC_Log::write('core', 'unable to rename, destination directory is not writable : ' . $dstParent, \OC_Log::ERROR);
			return false;
		}

		if (!$this->file_exists($path1)) {
			\OC_Log::write('core', 'unable to rename, file does not exists : ' . $path1, \OC_Log::ERROR);
			return false;
		}

		if ($this->is_dir($path2)) {
			$this->rmdir($path2);
		} else if ($this->is_file($path2)) {
			$this->unlink($path2);
		}

		$physicPath1 = $this->getSourcePath($path1);
		$physicPath2 = $this->getSourcePath($path2);
		if ($return = rename($physicPath1, $physicPath2)) {
			// mapper needs to create copies or all children
			$this->copyMapping($path1, $path2);
			$this->cleanMapper($physicPath1, false, true);
		}
		return $return;
	}

	public function copy($path1, $path2) {
		if ($this->is_dir($path1)) {
			if ($this->is_dir($path2)) {
				$this->rmdir($path2);
			} else if ($this->is_file($path2)) {
				$this->unlink($path2);
			}
			$dir = $this->opendir($path1);
			$this->mkdir($path2);
			while ($file = readdir($dir)) {
				if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
					if (!$this->copy($path1 . '/' . $file, $path2 . '/' . $file)) {
						return false;
					}
				}
			}
			closedir($dir);
			return true;
		} else {
			if ($return = copy($this->getSourcePath($path1), $this->getSourcePath($path2))) {
				$this->copyMapping($path1, $path2);
			}
			return $return;
		}
	}

	public function fopen($path, $mode) {
		return fopen($this->getSourcePath($path), $mode);
	}

	/**
	 * @param string $dir
	 * @param bool $isLogicPath
	 * @return bool
	 */
	private function delTree($dir, $isLogicPath = true) {
		$dirRelative = $dir;
		if ($isLogicPath) {
			$dir = $this->getSourcePath($dir);
		}
		if (!file_exists($dir)) {
			return true;
		}
		if (!is_dir($dir) || is_link($dir)) {
			if ($return = unlink($dir)) {
				$this->cleanMapper($dir, false);
				return $return;
			}
		}
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}
			if (is_file($dir . '/' . $item)) {
				if (unlink($dir . '/' . $item)) {
					$this->cleanMapper($dir . '/' . $item, false);
				}
			} elseif (is_dir($dir . '/' . $item)) {
				if (!$this->delTree($dir . "/" . $item, false)) {
					return false;
				};
			}
		}
		if ($return = rmdir($dir)) {
			$this->cleanMapper($dir, false);
		}
		return $return;
	}

	public function hash($type, $path, $raw = false) {
		return hash_file($type, $this->getSourcePath($path), $raw);
	}

	public function free_space($path) {
		$space = @disk_free_space($this->getSourcePath($path));
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
	 */
	protected function searchInDir($query, $dir = '') {
		$files = array();
		$physicalDir = $this->getSourcePath($dir);
		foreach (scandir($physicalDir) as $item) {
			if ($item == '.' || $item == '..')
				continue;
			$physicalItem = $this->mapper->physicalToLogic($physicalDir . '/' . $item);
			$item = substr($physicalItem, strlen($physicalDir) + 1);

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
	 */
	protected function getSourcePath($path) {
		$path = $this->stripLeading($path);
		$fullPath = $this->datadir . $path;
		return $this->mapper->logicToPhysical($fullPath, true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLocal() {
		return true;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function dirName($path) {
		$path = dirname($path);
		if ($path === '.') {
			return '';
		} else {
			return $path;
		}
	}

	/**
	 * @param string $path
	 */
	private function cleanMapper($path, $isLogicPath = true, $recursive = true) {
		$fullPath = $path;
		if ($isLogicPath) {
			$fullPath = $this->datadir . $path;
		}
		$this->mapper->removePath($fullPath, $isLogicPath, $recursive);
	}

	/**
	 * @param string $path1
	 * @param string $path2
	 */
	private function copyMapping($path1, $path2) {
		$path1 = $this->stripLeading($path1);
		$path2 = $this->stripLeading($path2);

		$fullPath1 = $this->datadir . $path1;
		$fullPath2 = $this->datadir . $path2;

		$this->mapper->copy($fullPath1, $fullPath2);
	}

	/**
	 * @param string $path
	 */
	private function stripLeading($path) {
		if (strpos($path, '/') === 0) {
			$path = substr($path, 1);
		}
		if (strpos($path, '\\') === 0) {
			$path = substr($path, 1);
		}
		if ($path === false) {
			return '';
		}

		return $path;
	}
}
