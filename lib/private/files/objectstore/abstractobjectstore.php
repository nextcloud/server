<?php
/**
 * @author Jörn Friedrich Dreyer
 * @copyright (c) 2014 Jörn Friedrich Dreyer <jfd@owncloud.com>
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

namespace OC\Files\ObjectStore;

abstract class AbstractObjectStore extends \OC\Files\Storage\Common {

	/**
	 * @var \OC\User\User $user
	 */
	protected $user;

	/**
	 * @var array
	 */
	private static $tmpFiles = array();

	/**
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache($path = '') {
		if (!isset($this->cache)) {
			$this->cache = new \OC\Files\Cache\Cache($this);
		}
		return $this->cache;
	}

	/**
	 * Object Stores use a NoopScanner because metadata is directly stored in
	 * the file cache and cannot really scan the filesystem
	 * @return \OC\Files\ObjectStore\NoopScanner
	 */
	public function getScanner($path = '') {
		if (!isset($this->scanner)) {
			$this->scanner = new NoopScanner($this);
		}
		return $this->scanner;
	}
	
	/**
	 * get the owner of a path
	 *
	 * @param string $path The path to get the owner
	 * @return string uid or false
	 */
	public function getOwner($path) {
		if (is_object($this->user)) {
			return $this->user->getUID();
		}
		return false;
	}
	public function getUser($path = null) {
		return $this->user;
	}
	
	/**
	 * @param string $path
	 * @return string
	 */
	private function normalizePath($path) {
		$path = trim($path, '/');
		//FIXME why do we sometimes get a path like 'files//username'?
		$path = str_replace('//', '/', $path);

		if (!$path) {
			$path = '.';
		}

		return $path;
	}

	public function getId () {
		if (is_object($this->user)) {
			return 'objstore::user:' . $this->user->getUID();
		}
		return 'objstore::root';
	}

	public function __construct($params) {
		if (is_object($params['user'])) {
			$this->user = $params['user'];
		} else {
			$this->user = null;
		}
		//initialize cache with root directory in cache
		if ( ! $this->is_dir('/') ) {
			$this->mkdir('/');
		}

		if (is_object($params['user'])) {
			//create the files folder in the cache when mounting the objectstore for a user
			if ( ! $this->is_dir('files') ) {
				$this->mkdir('files');
			}
		}
	}

	public function mkdir($path) {
		$path = $this->normalizePath($path);

		if($this->is_dir($path)) {
			return false;
		}

		$dirname = dirname($path);
		$parentExists = $this->is_dir($dirname);

		$mtime = time();

		if ($dirname === '.' && ! $parentExists ) {
			//create root on the fly

			$data = array(
				'etag' => $this->getETag($dirname),
				'mimetype' => "httpd/unix-directory",
				'size' => 0,
				'mtime' => $mtime,
				'storage_mtime' => $mtime,
				'permissions' => \OCP\PERMISSION_CREATE,
			);
			$this->getCache()->put('', $data);
			$parentExists = true;

		}

		if ($parentExists) {

			$data = array(
				'etag' => $this->getETag($path),
				'mimetype' => "httpd/unix-directory",
				'size' => 0,
				'mtime' => $mtime,
				'storage_mtime' => $mtime,
				'permissions' => \OCP\PERMISSION_CREATE,
			);

			$this->getCache()->put($path, $data);

			return true;
		}
		return false;
	}

	public function file_exists($path) {
		$path = $this->normalizePath($path);
		return (bool)$this->stat($path);
	}


	public function rmdir($path) {
		$path = $this->normalizePath($path);

		if (!$this->is_dir($path)) {
			return false;
		}

		$this->getCache()->remove($path);
		
		//TODO recursively delete files in s3

		return true;
	}

	public function opendir($path) {
		$path = $this->normalizePath($path);
		
		if ($path === '.') {
			$path = '';
		} else if ($path) {
			$path .= '/';
		}

		try {
			$files = array();
			$folderContents = $this->getCache()->getFolderContents($path);
			foreach ($folderContents as $file) {
				$files[] = $file['name'];
			}
			
			\OC\Files\Stream\Dir::register('s3' . $path, $files);

			return opendir('fakedir://s3' . $path);
		} catch (S3Exception $e) {
			\OCP\Util::writeLog('objectstore', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function stat($path) {
		return $this->getCache()->get($path);
	}

	public function filetype($path) {
		$path = $this->normalizePath($path);
		$stat = $this->stat($path);
		if ($stat) {
			if ($stat['mimetype'] === 'httpd/unix-directory') {
				return 'dir';
			}
			return 'file';
		} else {
			return false;
		}
	}

	abstract protected function deleteObject($urn);
	
	public function unlink($path) {
		$path = $this->normalizePath($path);
		$stat = $this->stat($path);

		if ($stat && isset($stat['fileid'])) {
			//TODO use exception handling?
			$this->deleteObject($this->getURN($stat['fileid']));
			$this->getCache()->remove($path);
			return true;
		}
		return false;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param string $tmpFile path to the local temporary file that should be
	 *        used to store the object
	 * @return null|string
	 */
	abstract protected function getObject($urn, $tmpFile);
	
	public function fopen($path, $mode) {
		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'r':
			case 'rb':
				$stat = $this->stat($path);
				if (is_array($stat)) {
					$tmpFile = \OC_Helper::tmpFile();
					self::$tmpFiles[$tmpFile] = $path;
					$this->getObject($this->getURN($stat['fileid']), $tmpFile);

					return fopen($tmpFile, 'r');
				} else {
					return false;
				}
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				$tmpFile = \OC_Helper::tmpFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				self::$tmpFiles[$tmpFile] = $path;

				return fopen('close://' . $tmpFile, $mode);
		}
		return false;
	}

	public function rename($path1, $path2) {
		$path1 = $this->normalizePath($path1);
		$path2 = $this->normalizePath($path2);
		$stat1 = $this->stat($path1);
		if (is_array($stat1)) {
			$parent = $this->stat(dirname($path2));
			if (is_array($parent)) {
				$stat2 = $this->stat($path2);
				if (is_array($stat2)) {
					$this->unlink($path2);
				}
				$stat1['parent'] = $parent['fileid'];
				$stat1['path'] = $path2;
				$stat1['path_hash'] = md5($path2);
				$stat1['name'] = \OC_Util::basename($path2);
				$stat1['mtime'] = time();
				$stat1['etag'] = $this->getETag($path2);
				$this->getCache()->update($stat1['fileid'], $stat1);
				return true;
			} else {
				return false;
			}
			
		} else {
			return false;
		}
	}
	
	public function getMimeType($path) {
		$path = $this->normalizePath($path);
		$stat = $this->stat($path);
		if (is_array($stat)) {
			return $stat['mimetype'];
		} else {
			return false;
		}
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param string $tmpFile path to the local temporary file that the object
	 *        should be loaded from
	 * @return null|string
	 */
	abstract protected function createObject($urn, $tmpFile = null);
	
	public function touch($path, $mtime = null) {
		if (is_null($mtime)) {
			$mtime = time();
		}

		$path = $this->normalizePath($path);
		$dirname = dirname($path);
		$parentExists = $this->is_dir($dirname);
		if (!$parentExists) {
			return false;
		}

		$stat = $this->stat($path);
		if (is_array($stat)) {
			// update existing mtime in db
			$stat['mtime'] = $mtime;
			$this->getCache()->update($stat['fileid'], $stat);
		} else {
			$mimetype = \OC_Helper::getFileNameMimeType($path);
			// create new file
			$stat = array(
				'etag' => $this->getETag($path),
				'mimetype' => $mimetype,
				'size' => 0,
				'mtime' => $mtime,
				'storage_mtime' => $mtime,
				'permissions' => \OCP\PERMISSION_CREATE,
			);
			$fileid = $this->getCache()->put($path, $stat);
			$this->createObject($this->getURN($fileid));
		}
		return true;
	}

	/**
	 * @param int $fileid the fileid
	 * @return null|string
	 */
	protected function getURN($fileid) {
		if (is_numeric($fileid)) {
			return 'urn:oid:'.$fileid;
		}
		return null;
	}

	public function writeBack($tmpFile) {
		if (!isset(self::$tmpFiles[$tmpFile])) {
			return false;
		}

		$path = self::$tmpFiles[$tmpFile];
		$mimetype = \OC_Helper::getMimeType($tmpFile);
		$size = filesize($tmpFile);
		$mtime = time();
		$stat = $this->stat($path);
		if (is_array($stat)) {
			// update existing db entry
			$stat['size'] = $size;
			$stat['mtime'] = $mtime;
			$stat['mimetype'] = $mimetype;
		} else {
			// create new file
			$stat = array(
				'etag' => $this->getETag($path),
				'mimetype' => $mimetype,
				'size' => $size,
				'mtime' => $mtime,
				'storage_mtime' => $mtime,
				'permissions' => \OCP\PERMISSION_CREATE,
			);
		}
		$fileid = $this->getCache()->put($path, $stat);
		$this->createObject($this->getURN($fileid), $tmpFile);
	}

}