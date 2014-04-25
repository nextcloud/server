<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle, Michael Gapczynski
 * @copyright 2012 Michael Gapczynski <mtgap@owncloud.com>
 *            2014 Bjoern Schiessle <schiessle@owncloud.com>
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

use OCP\Share_Backend_Collection;

/**
 * Metadata cache for shared files
 *
 * don't use this class directly if you need to get metadata, use \OC\Files\Filesystem::getFileInfo instead
 */
class Shared_Cache extends Cache {

	private $storage;
	private $files = array();

	/**
	 * @param \OC\Files\Storage\Shared $storage
	 */
	public function __construct($storage) {
		$this->storage = $storage;
	}

	/**
	 * @brief Get the source cache of a shared file or folder
	 * @param string $target Shared target file path
	 * @return \OC\Files\Cache\Cache
	 */
	private function getSourceCache($target) {
		if ($target === false || $target === $this->storage->getMountPoint()) {
			$target = '';
		}
		$source = \OC_Share_Backend_File::getSource($target, $this->storage->getMountPoint(), $this->storage->getItemType());
		if (isset($source['path']) && isset($source['fileOwner'])) {
			\OC\Files\Filesystem::initMountPoints($source['fileOwner']);
			$mounts = \OC\Files\Filesystem::getMountByNumericId($source['storage']);
			if (is_array($mounts) and count($mounts)) {
				$fullPath = $mounts[0]->getMountPoint() . $source['path'];
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

	public function getNumericStorageId() {
		if (isset($this->numericId)) {
			return $this->numericId;
		} else {
			return false;
		}
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string /int $file
	 * @return array
	 */
	public function get($file) {
		if (is_string($file)) {
			if ($cache = $this->getSourceCache($file)) {
				$data = $cache->get($this->files[$file]);
				$data['displayname_owner'] = \OC_User::getDisplayName($this->storage->getSharedFrom());
				$data['path'] = $file;
				if ($file === '') {
					$data['is_share_mount_point'] = true;
				}
				$data['uid_owner'] = $this->storage->getOwner($file);
				return $data;
			}
		} else {
			// if we are at the root of the mount point we want to return the
			// cache information for the source item
			if (!is_int($file) || $file === 0) {
				$file = $this->storage->getSourceId();
			}
			$query = \OC_DB::prepare(
				'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`,'
				. ' `size`, `mtime`, `encrypted`, `unencrypted_size`, `storage_mtime`, `etag`'
				. ' FROM `*PREFIX*filecache` WHERE `fileid` = ?');
			$result = $query->execute(array($file));
			$data = $result->fetchRow();
			$data['fileid'] = (int)$data['fileid'];
			$data['mtime'] = (int)$data['mtime'];
			$data['storage_mtime'] = (int)$data['storage_mtime'];
			$data['encrypted'] = (bool)$data['encrypted'];
			$data['mimetype'] = $this->getMimetype($data['mimetype']);
			$data['mimepart'] = $this->getMimetype($data['mimepart']);
			if ($data['storage_mtime'] === 0) {
				$data['storage_mtime'] = $data['mtime'];
			}
			if ($data['encrypted'] or ($data['unencrypted_size'] > 0 and $data['mimetype'] === 'httpd/unix-directory')) {
				$data['encrypted_size'] = (int)$data['size'];
				$data['size'] = (int)$data['unencrypted_size'];
			} else {
				$data['size'] = (int)$data['size'];
			}
			if (!is_int($file) || $file === 0) {
				$data['path'] = '';
				$data['is_share_mount_point'] = true;
			}
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

		if ($folder === false) {
			$folder = '';
		}

		$dir = ($folder !== '') ? $folder . '/' : '';

		$cache = $this->getSourceCache($folder);
		if ($cache) {
			$parent = $this->storage->getFile($folder);
			$sourceFolderContent = $cache->getFolderContents($this->files[$folder]);
			foreach ($sourceFolderContent as $key => $c) {
				$sourceFolderContent[$key]['path'] = $dir . $c['name'];
				$sourceFolderContent[$key]['uid_owner'] = $parent['uid_owner'];
				$sourceFolderContent[$key]['displayname_owner'] = $parent['uid_owner'];
			}

			return $sourceFolderContent;
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
		$file = ($file === false) ? '' : $file;
		if ($cache = $this->getSourceCache($file)) {
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
		if ($file === false) {
			return $this->storage->getSourceId();
		}
		$cache = $this->getSourceCache($file);
		if ($cache) {
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
		$file = ($file === false) ? '' : $file;
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
			$file = \OC_Share_Backend_File::getSource($target, $this->storage->getMountPoint(), $this->storage->getItemType());
			if ($file && isset($file['path'])) {
				$cache->move($this->files[$source], $file['path']);
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

		$where = '`name` LIKE ? AND ';

		// normalize pattern
		$value = $this->normalize($pattern);

		return $this->searchWithWhere($where, $value);

	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return array
	 */
	public function searchByMime($mimetype) {
		$mimepart = null;
		if (strpos($mimetype, '/') === false) {
			$mimepart = $mimetype;
			$mimetype = null;
		}

		// note: searchWithWhere is currently broken as it doesn't
		// recurse into subdirs nor returns the correct
		// file paths, so using getFolderContents() for now

		$result = array();
		$exploreDirs = array('');
		while (count($exploreDirs) > 0) {
			$dir = array_pop($exploreDirs);
			$files = $this->getFolderContents($dir);
			// no results?
			if (!$files) {
				// maybe it's a single shared file
				$file = $this->get('');
				if (($mimepart && $file['mimepart'] === $mimepart) || ($mimetype && $file['mimetype'] === $mimetype)) {
					$result[] = $file;
				}
				continue;
			}
			foreach ($files as $file) {
				if ($file['mimetype'] === 'httpd/unix-directory') {
					$exploreDirs[] = ltrim($dir . '/' . $file['name'], '/');
				} else if (($mimepart && $file['mimepart'] === $mimepart) || ($mimetype && $file['mimetype'] === $mimetype)) {
					$result[] = $file;
				}
			}
		}
		return $result;
	}

	/**
	 * The maximum number of placeholders that can be used in an SQL query.
	 * Value MUST be <= 1000 for oracle:
	 * see ORA-01795 maximum number of expressions in a list is 1000
	 * FIXME we should get this from doctrine as other DBs allow a lot more placeholders
	 */
	const MAX_SQL_CHUNK_SIZE = 1000;

	/**
	 * search for files with a custom where clause and value
	 * the $wherevalue will be array_merge()d with the file id chunks
	 *
	 * @param string $sqlwhere
	 * @param string $wherevalue
	 * @return array
	 */
	private function searchWithWhere($sqlwhere, $wherevalue, $chunksize = self::MAX_SQL_CHUNK_SIZE) {

		$ids = $this->getAll();

		$files = array();

		// divide into chunks
		$chunks = array_chunk($ids, $chunksize);

		foreach ($chunks as $chunk) {
			$placeholders = join(',', array_fill(0, count($chunk), '?'));
			$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`,
					`encrypted`, `unencrypted_size`, `etag`
					FROM `*PREFIX*filecache` WHERE ' . $sqlwhere . ' `fileid` IN (' . $placeholders . ')';

			$stmt = \OC_DB::prepare($sql);

			$result = $stmt->execute(array_merge(array($wherevalue), $chunk));

			while ($row = $result->fetchRow()) {
				if (substr($row['path'], 0, 6) === 'files/') {
					$row['path'] = substr($row['path'], 6); // remove 'files/' from path as it's relative to '/Shared'
				}
				$row['mimetype'] = $this->getMimetype($row['mimetype']);
				$row['mimepart'] = $this->getMimetype($row['mimepart']);
				if ($row['encrypted'] or ($row['unencrypted_size'] > 0 and $row['mimetype'] === 'httpd/unix-directory')) {
					$row['encrypted_size'] = $row['size'];
					$row['size'] = $row['unencrypted_size'];
				}
				$files[] = $row;
			}
		}
		return $files;
	}

	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array $entry (optional) meta data of the folder
	 * @return int
	 */
	public function calculateFolderSize($path, $entry = null) {
		$path = ($path === false) ? '' : $path;
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
		$ids = \OCP\Share::getItemsSharedWith('file', \OC_Share_Backend_File::FORMAT_GET_ALL);
		$folderBackend = \OCP\Share::getBackend('folder');
		if ($folderBackend instanceof Share_Backend_Collection) {
			foreach ($ids as $file) {
				/** @var $folderBackend Share_Backend_Collection */
				$children = $folderBackend->getChildren($file);
				foreach ($children as $child) {
					$ids[] = (int)$child['source'];
				}

			}
		}

		return $ids;
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiply incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return boolean the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		return false;
	}

	/**
	 * get the path of a file on this storage relative to the mount point by it's id
	 *
	 * @param int $id
	 * @param string $pathEnd (optional) used internally for recursive calls
	 * @return string | null
	 */
	public function getPathById($id, $pathEnd = '') {
		// direct shares are easy
		$path = $this->getShareById($id);
		if (is_string($path)) {
			return ltrim($pathEnd, '/');
		} else {
			// if the item is a direct share we try and get the path of the parent and append the name of the item to it
			list($parent, $name) = $this->getParentInfo($id);
			if ($parent > 0) {
				return $this->getPathById($parent, '/' . $name . $pathEnd);
			} else {
				return null;
			}
		}
	}

	private function getShareById($id) {
		$item = \OCP\Share::getItemSharedWithBySource('file', $id);
		if ($item) {
			return trim($item['file_target'], '/');
		}
		$item = \OCP\Share::getItemSharedWithBySource('folder', $id);
		if ($item) {
			return trim($item['file_target'], '/');
		}
		return null;
	}

	private function getParentInfo($id) {
		$sql = 'SELECT `parent`, `name` FROM `*PREFIX*filecache` WHERE `fileid` = ?';
		$query = \OC_DB::prepare($sql);
		$result = $query->execute(array($id));
		if ($row = $result->fetchRow()) {
			return array($row['parent'], $row['name']);
		} else {
			return array(-1, '');
		}
	}
}
