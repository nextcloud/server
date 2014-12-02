<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

if (\OC_Util::runningOnWindows()) {
	class Local extends MappedLocal {

	}
} else {

	/**
	 * for local filestore, we only have to map the paths
	 */
	class Local extends \OC\Files\Storage\Common {
		protected $datadir;

		public function __construct($arguments) {
			$this->datadir = $arguments['datadir'];
			if (substr($this->datadir, -1) !== '/') {
				$this->datadir .= '/';
			}
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
		 */
		protected function getSourcePath($path) {
			$fullPath = $this->datadir . $path;
			return $fullPath;
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
	}
}
