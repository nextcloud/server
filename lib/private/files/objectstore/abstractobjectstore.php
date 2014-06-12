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
	 * @param string $urn the unified resource name used to identify the object
	 * @param string $tmpFile path to the local temporary file that the object
	 *        should be loaded from
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	abstract protected function createObject($urn, $tmpFile = null);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param string $tmpFile path to the local temporary file that should be
	 *        used to store the object
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	abstract protected function getObject($urn, $tmpFile);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	abstract protected function deleteObject($urn);

	/**
	 * @var \OC\User\User $user
	 */
	protected $user;

	/**
	 * @var array
	 */
	private static $tmpFiles = array();

	/**
	 * @param string $path
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
	 * @param string $path
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
	 * @return false|string uid
	 */
	public function getOwner($path) {
		if (is_object($this->user)) {
			return $this->user->getUID();
		}
		return false;
	}

	/**
	 * @param string $path, optional
	 * @return \OC\User\User
	 */
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
		if (isset($params['user']) && is_object($params['user'])) {
			$this->user = $params['user'];
		} else {
			$this->user = null;
		}
		//initialize cache with root directory in cache
		if ( ! $this->is_dir('/') ) {
			$this->mkdir('/');
		}

		if (isset($params['user']) && is_object($params['user'])) {
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

		$dirName = dirname($path);
		$parentExists = $this->is_dir($dirName);

		$mTime = time();

		$data = array(
			'mimetype' => 'httpd/unix-directory',
			'size' => 0,
			'mtime' => $mTime,
			'storage_mtime' => $mTime,
			'permissions' => \OCP\PERMISSION_ALL,
		);

		if ($dirName === '.' && ! $parentExists ) {
			//create root on the fly
			$data['etag'] = $this->getETag($dirName);
			$this->getCache()->put('', $data);
			$parentExists = true;
		}

		if ($parentExists) {
			$data['etag'] = $this->getETag($path);
			$this->getCache()->put($path, $data);
			return true;
		}
		return false;
	}

	public function file_exists($path) {
		$path = $this->normalizePath($path);
		return (bool)$this->stat($path);
	}

	private function rmObjects($path) {
		$children = $this->getCache()->getFolderContents($path);
		foreach ($children as $child) {
			if ($child['mimetype'] === 'httpd/unix-directory') {
				$this->rmObjects($child['path']);
			} else {
				$this->unlink($child['path']);
			}
		}
	}

	public function rmdir($path) {
		$path = $this->normalizePath($path);

		if (!$this->is_dir($path)) {
			return false;
		}

		$this->rmObjects($path);

		$this->getCache()->remove($path);

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
			
			\OC\Files\Stream\Dir::register('objstore' . $path, $files);

			return opendir('fakedir://objstore' . $path);
		} catch (Exception $e) {
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

	
	public function unlink($path) {
		$path = $this->normalizePath($path);
		$stat = $this->stat($path);

		if ($stat && isset($stat['fileid'])) {
			if ($stat['mimetype'] === 'httpd/unix-directory') {
				return $this->rmdir($path);
			}
			try {
				$this->deleteObject($this->getURN($stat['fileid']));
			} catch (\Exception $ex) {
				if ($ex->getCode() !== 404) {
					\OCP\Util::writeLog('objectstore', 'Could not delete object: '.$ex->getMessage(), \OCP\Util::ERROR);
					return false;
				} else {
					//removing from cache is ok as it does not exist in the objectstore anyway
				}
			}
			$this->getCache()->remove($path);
			return true;
		}
		return false;
	}
	
	public function fopen($path, $mode) {
		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'r':
			case 'rb':
				$stat = $this->stat($path);
				if (is_array($stat)) {
					$tmpFile = \OC_Helper::tmpFile();
					self::$tmpFiles[$tmpFile] = $path;
					try {
						$this->getObject($this->getURN($stat['fileid']), $tmpFile);
					} catch (\Exception $ex) {
						\OCP\Util::writeLog('objectstore', 'Could not get object: '.$ex->getMessage(), \OCP\Util::ERROR);
						return false;
					}
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
	
	public function touch($path, $mtime = null) {
		if (is_null($mtime)) {
			$mtime = time();
		}

		$path = $this->normalizePath($path);
		$dirName = dirname($path);
		$parentExists = $this->is_dir($dirName);
		if (!$parentExists) {
			return false;
		}

		$stat = $this->stat($path);
		if (is_array($stat)) {
			// update existing mtime in db
			$stat['mtime'] = $mtime;
			$this->getCache()->update($stat['fileid'], $stat);
		} else {
			$mimeType = \OC_Helper::getFileNameMimeType($path);
			// create new file
			$stat = array(
				'etag' => $this->getETag($path),
				'mimetype' => $mimeType,
				'size' => 0,
				'mtime' => $mtime,
				'storage_mtime' => $mtime,
				'permissions' => \OCP\PERMISSION_ALL,
			);
			$fileId = $this->getCache()->put($path, $stat);
			try {
				$this->createObject($this->getURN($fileId));
			} catch (\Exception $ex) {
				$this->getCache()->remove($path);
				\OCP\Util::writeLog('objectstore', 'Could not create object: '.$ex->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		}
		return true;
	}

	/**
	 * Override this method if you need a different unique resource identifier for your object storage implementation.
	 * The default implementations just appends the fileId to 'urn:oid:'. Make sure the URN is unique over all users.
	 * You may need a mapping table to store your URN if it cannot be generated from the fileid.
	 * @param int $fileId the fileid
	 * @return null|string the unified resource name used to identify the object
	 */
	protected function getURN($fileId) {
		if (is_numeric($fileId)) {
			return 'urn:oid:'.$fileId;
		}
		return null;
	}

	public function writeBack($tmpFile) {
		if (!isset(self::$tmpFiles[$tmpFile])) {
			return false;
		}

		$path = self::$tmpFiles[$tmpFile];
		$stat = $this->stat($path);
		if (empty($stat)) {
			// create new file
			$stat = array(
				'etag' => $this->getETag($path),
				'permissions' => \OCP\PERMISSION_ALL,
			);
		}
		// update stat with new data
		$mTime = time();
		$stat['size'] = filesize($tmpFile);
		$stat['mtime'] = $mTime;
		$stat['storage_mtime'] = $mTime;
		$stat['mimetype'] = \OC_Helper::getMimeType($tmpFile);

		$fileId = $this->getCache()->put($path, $stat);
		try {
			//upload to object storage
			$this->createObject($this->getURN($fileId), $tmpFile);
		} catch (\Exception $ex) {
			$this->getCache()->remove($path);
			\OCP\Util::writeLog('objectstore', 'Could not create object: '.$ex->getMessage(), \OCP\Util::ERROR);
			return false;
		}
		return true;
	}

}