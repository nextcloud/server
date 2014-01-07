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
			return @mkdir($this->datadir . $path, 0777, true);
		}

		public function rmdir($path) {
			try {
				$it = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($this->datadir . $path),
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
				return rmdir($this->datadir . $path);
			} catch (\UnexpectedValueException $e) {
				return false;
			}
		}

		public function opendir($path) {
			return opendir($this->datadir . $path);
		}

		public function is_dir($path) {
			if (substr($path, -1) == '/') {
				$path = substr($path, 0, -1);
			}
			return is_dir($this->datadir . $path);
		}

		public function is_file($path) {
			return is_file($this->datadir . $path);
		}

		public function stat($path) {
			$fullPath = $this->datadir . $path;
			$statResult = stat($fullPath);

			$filesize = self::getFileSizeWithTricks($fullPath);
			if (!is_null($filesize)) {
				$statResult['size'] = $filesize;
				$statResult[7] = $filesize;
			}
			return $statResult;
		}

		public function filetype($path) {
			$filetype = filetype($this->datadir . $path);
			if ($filetype == 'link') {
				$filetype = filetype(realpath($this->datadir . $path));
			}
			return $filetype;
		}

		public function filesize($path) {
			if ($this->is_dir($path)) {
				return 0;
			} else {
				$fullPath = $this->datadir . $path;

				$filesize = self::getFileSizeWithTricks($fullPath);
				if (!is_null($filesize)) {
					return $filesize;
				}

				return filesize($fullPath);
			}
		}

		public function isReadable($path) {
			return is_readable($this->datadir . $path);
		}

		public function isUpdatable($path) {
			return is_writable($this->datadir . $path);
		}

		public function file_exists($path) {
			return file_exists($this->datadir . $path);
		}

		public function filemtime($path) {
			return filemtime($this->datadir . $path);
		}

		public function touch($path, $mtime = null) {
			// sets the modification time of the file to the given value.
			// If mtime is nil the current time is set.
			// note that the access time of the file always changes to the current time.
			if ($this->file_exists($path) and !$this->isUpdatable($path)) {
				return false;
			}
			if (!is_null($mtime)) {
				$result = touch($this->datadir . $path, $mtime);
			} else {
				$result = touch($this->datadir . $path);
			}
			if ($result) {
				clearstatcache(true, $this->datadir . $path);
			}

			return $result;
		}

		public function file_get_contents($path) {
			return file_get_contents($this->datadir . $path);
		}

		public function file_put_contents($path, $data) { //trigger_error("$path = ".var_export($path, 1));
			return file_put_contents($this->datadir . $path, $data);
		}

		public function unlink($path) {
			if ($this->is_dir($path)) {
				return $this->rmdir($path);
			} else if ($this->is_file($path)) {
				return unlink($this->datadir . $path);
			} else {
				return false;
			}

		}

		public function rename($path1, $path2) {
			if (!$this->isUpdatable($path1)) {
				\OC_Log::write('core', 'unable to rename, file is not writable : ' . $path1, \OC_Log::ERROR);
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

			return rename($this->datadir . $path1, $this->datadir . $path2);
		}

		public function copy($path1, $path2) {
			if ($this->is_dir($path1)) {
				return parent::copy($path1, $path2);
			} else {
				return copy($this->datadir . $path1, $this->datadir . $path2);
			}
		}

		public function fopen($path, $mode) {
			if ($return = fopen($this->datadir . $path, $mode)) {
				switch ($mode) {
					case 'r':
						break;
					case 'r+':
					case 'w+':
					case 'x+':
					case 'a+':
						break;
					case 'w':
					case 'x':
					case 'a':
						break;
				}
			}
			return $return;
		}

		/**
		* @brief Tries to get the filesize via various workarounds if necessary.
		* @param string $fullPath
		* @return mixed Number of bytes on success and workaround necessary, null otherwise.
		*/
		private static function getFileSizeWithTricks($fullPath) {
			if (PHP_INT_SIZE === 4) {
				// filesize() and stat() are unreliable on 32bit systems
				// for big files.
				// In some cases they cause an E_WARNING and return false,
				// in some other cases they silently wrap around at 2^32,
				// i.e. e.g. report 674347008 bytes instead of 4969314304.
				$filesize = self::getFileSizeFromCurl($fullPath);
				if (!is_null($filesize)) {
					return $filesize;
				}
				$filesize = self::getFileSizeFromOS($fullPath);
				if (!is_null($filesize)) {
					return $filesize;
				}
			}

			return null;
		}

		/**
		* @brief Tries to get the filesize via a CURL HEAD request.
		* @param string $fullPath
		* @return mixed Number of bytes on success, null otherwise.
		*/
		private static function getFileSizeFromCurl($fullPath) {
			if (function_exists('curl_init')) {
				$ch = curl_init("file://$fullPath");
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, true);
				$data = curl_exec($ch);
				curl_close($ch);
				if ($data !== false) {
					$matches = array();
					preg_match('/Content-Length: (\d+)/', $data, $matches);
					if (isset($matches[1])) {
						return 0 + $matches[1];
					}
				}
			}

			return null;
		}

		/**
		* @brief Tries to get the filesize via COM and exec().
		* @param string $fullPath
		* @return mixed Number of bytes on success, null otherwise.
		*/
		private static function getFileSizeFromOS($fullPath) {
			$name = strtolower(php_uname('s'));
			// Windows OS: we use COM to access the filesystem
			if (strpos($name, 'win') !== false) {
				if (class_exists('COM')) {
					$fsobj = new \COM("Scripting.FileSystemObject");
					$f = $fsobj->GetFile($fullPath);
					return $f->Size;
				}
			} else if (strpos($name, 'bsd') !== false) {
				if (\OC_Helper::is_function_enabled('exec')) {
					return 0 + exec('stat -f %z ' . escapeshellarg($fullPath));
				}
			} else if (strpos($name, 'linux') !== false) {
				if (\OC_Helper::is_function_enabled('exec')) {
					return 0 + exec('stat -c %s ' . escapeshellarg($fullPath));
				}
			} else {
				\OC_Log::write('core',
					'Unable to determine file size of "' . $fullPath . '". Unknown OS: ' . $name,
					\OC_Log::ERROR);
			}

			return null;
		}

		public function hash($type, $path, $raw = false) {
			return hash_file($type, $this->datadir . $path, $raw);
		}

		public function free_space($path) {
			$space = @disk_free_space($this->datadir . $path);
			if ($space === false || is_null($space)) {
				return \OC\Files\SPACE_UNKNOWN;
			}
			return $space;
		}

		public function search($query) {
			return $this->searchInDir($query);
		}

		public function getLocalFile($path) {
			return $this->datadir . $path;
		}

		public function getLocalFolder($path) {
			return $this->datadir . $path;
		}

		/**
		 * @param string $query
		 */
		protected function searchInDir($query, $dir = '') {
			$files = array();
			foreach (scandir($this->datadir . $dir) as $item) {
				if ($item == '.' || $item == '..') continue;
				if (strstr(strtolower($item), strtolower($query)) !== false) {
					$files[] = $dir . '/' . $item;
				}
				if (is_dir($this->datadir . $dir . '/' . $item)) {
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
		 * {@inheritdoc}
		 */
		public function isLocal() {
			return true;
		}
	}
}
