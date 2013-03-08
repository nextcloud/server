<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
 */

namespace OC\Files\Cache;

/**
 * Metadata cache for shared files
 *
 * don't use this class directly if you need to get metadata, use \OC\Files\Filesystem::getFileInfo instead
 */
class Shared_Cache extends Cache {

	private $storage;
	private $files = array();

	public function __construct($storage) {
		$this->storage = $storage;
	}

	/**
	 * @brief Get the source cache of a shared file or folder
	 * @param string $target Shared target file path
	 * @return \OC\Files\Cache\Cache
	 */
	private function getSourceCache($target) {
		$source = \OC_Share_Backend_File::getSource($target);
		if (isset($source['path']) && isset($source['fileOwner'])) {
			\OC\Files\Filesystem::initMountPoints($source['fileOwner']);
			$mount = \OC\Files\Mount::findByNumericId($source['storage']);
			if ($mount) {
				$fullPath = $mount->getMountPoint().$source['path'];
				list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($fullPath);
				if ($storage) {
					$this->files[$target] = $internalPath;
					$cache = $storage->getCache();
					$this->storageId = $storage->getId();
					$this->numericId = $cache->getNumericStorageId();
					return $cache;
				}
			}
		}
		return false;
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string/int $file
	 * @return array
	 */
	public function get($file) {
		if ($file == '') {
			$data = \OCP\Share::getItemsSharedWith('file', \OC_Share_Backend_File::FORMAT_FILE_APP_ROOT);
			$etag = \OCP\Config::getUserValue(\OCP\User::getUser(), 'files_sharing', 'etag');
			if (!isset($etag)) {
				$etag = $this->storage->getETag('');
				\OCP\Config::setUserValue(\OCP\User::getUser(), 'files_sharing', 'etag', $etag);
			}
			$data['etag'] = $etag;
			return $data;
		} else if (is_string($file)) {
			if ($cache = $this->getSourceCache($file)) {
				return $cache->get($this->files[$file]);
			}
		} else {
			$query = \OC_DB::prepare(
				'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`,'
				.' `size`, `mtime`, `encrypted`'
				.' FROM `*PREFIX*filecache` WHERE `fileid` = ?');
			$result = $query->execute(array($file));
			$data = $result->fetchRow();
			$data['fileid'] = (int)$data['fileid'];
			$data['size'] = (int)$data['size'];
			$data['mtime'] = (int)$data['mtime'];
			$data['encrypted'] = (bool)$data['encrypted'];
			$data['mimetype'] = $this->getMimetype($data['mimetype']);
			$data['mimepart'] = $this->getMimetype($data['mimepart']);
			return $data;
		}
		return false;
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return array
	 */
	public function getFolderContents($folder) {
		if ($folder == '') {
			$files = \OCP\Share::getItemsSharedWith('file', \OC_Share_Backend_File::FORMAT_GET_FOLDER_CONTENTS);
			foreach ($files as &$file) {
				$file['mimetype'] = $this->getMimetype($file['mimetype']);
				$file['mimepart'] = $this->getMimetype($file['mimepart']);
			}
			return $files;
		} else {
			if ($cache = $this->getSourceCache($folder)) {
				return $cache->getFolderContents($this->files[$folder]);
			}
		}
		return false;
	}

	/**
	 * store meta data for a file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 */
	public function put($file, array $data) {
		if ($file === '' && isset($data['etag'])) {
			return \OCP\Config::setUserValue(\OCP\User::getUser(), 'files_sharing', 'etag', $data['etag']);
		} else if ($cache = $this->getSourceCache($file)) {
			return $cache->put($this->files[$file], $data);
		}
		return false;
	}

	/**
	 * get the file id for a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		if ($cache = $this->getSourceCache($file)) {
			return $cache->getId($this->files[$file]);
		}
		return -1;
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		if ($file == '') {
			return true;
		}
		return parent::inCache($file);
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		if ($cache = $this->getSourceCache($file)) {
			$cache->remove($this->files[$file]);
		}
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		if ($cache = $this->getSourceCache($source)) {
			$targetPath = \OC_Share_Backend_File::getSourcePath(dirname($target));
			if ($targetPath) {
				$targetPath .= '/' . basename($target);
				$cache->move($this->files[$source], $targetPath);
			}

		}
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		// Not a valid action for Shared Cache
	}

	/**
	 * @param string $file
	 *
	 * @return int, Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		if ($file == '') {
			return self::COMPLETE;
		}
		if ($cache = $this->getSourceCache($file)) {
			return $cache->getStatus($this->files[$file]);
		}
		return self::NOT_FOUND;
	}

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern
	 * @return array of file data
	 */
	public function search($pattern) {
		// TODO
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $part1
	 * @param string $part2
	 * @return array
	 */
	public function searchByMime($mimetype) {
		if (strpos($mimetype, '/')) {
			$where = '`mimetype` = ?';
		} else {
			$where = '`mimepart` = ?';
		}
		$mimetype = $this->getMimetypeId($mimetype);
		$ids = $this->getAll();
		$placeholders = join(',', array_fill(0, count($ids), '?'));
		$query = \OC_DB::prepare('
			SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`, `encrypted`
			FROM `*PREFIX*filecache` WHERE ' . $where . ' AND `fileid` IN (' . $placeholders . ')'
		);
		$result = $query->execute(array_merge(array($mimetype), $ids));
		return $result->fetchAll();
	}

	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @return int
	 */
	public function calculateFolderSize($path) {
		if ($cache = $this->getSourceCache($path)) {
			return $cache->calculateFolderSize($this->files[$path]);
		}
		return 0;
	}

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll() {
		return \OCP\Share::getItemsSharedWith('file', \OC_Share_Backend_File::FORMAT_GET_ALL);
	}

}
