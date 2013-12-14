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
use OCP\Share_Backend_Collection;

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
			$mount = \OC\Files\Filesystem::getMountByNumericId($source['storage']);
			if (is_array($mount)) {
				$fullPath = $mount[key($mount)]->getMountPoint().$source['path'];
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
			$data['storage_mtime'] = (int)$data['storage_mtime'];
			$data['encrypted'] = (bool)$data['encrypted'];
			$data['mimetype'] = $this->getMimetype($data['mimetype']);
			$data['mimepart'] = $this->getMimetype($data['mimepart']);
			if ($data['storage_mtime'] === 0) {
				$data['storage_mtime'] = $data['mtime'];
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
			$file = \OC_Share_Backend_File::getSource($target);
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

		if (strpos($mimetype, '/')) {
			$where = '`mimetype` = ? AND ';
		} else {
			$where = '`mimepart` = ? AND ';
		}

		$value = $this->getMimetypeId($mimetype);

		return $this->searchWithWhere($where, $value);

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
				$files[] = $row;
			}
		}
		return $files;
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
	 * @return string|bool the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		return false;
	}

}
