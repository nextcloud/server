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
	 * Get the source cache of a shared file or folder
	 *
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
			if (is_array($mounts) and !empty($mounts)) {
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
	 * @param string|int $file
	 * @return array
	 */
	public function get($file) {
		if (is_string($file)) {
			$cache = $this->getSourceCache($file);
			if ($cache) {
				$data = $cache->get($this->files[$file]);
				if ($data) {
					$data['displayname_owner'] = \OC_User::getDisplayName($this->storage->getSharedFrom());
					$data['path'] = $file;
					if ($file === '') {
						$data['is_share_mount_point'] = true;
					}
					$data['uid_owner'] = $this->storage->getOwner($file);
					if (isset($data['permissions'])) {
						$data['permissions'] &= $this->storage->getPermissions($file);
					} else {
						$data['permissions'] = $this->storage->getPermissions($file);
					}
				}
				return $data;
			}
		} else {
			$sourceId = $file;
			// if we are at the root of the mount point we want to return the
			// cache information for the source item
			if (!is_int($sourceId) || $sourceId === 0) {
				$sourceId = $this->storage->getSourceId();
			}
			$query = \OC_DB::prepare(
				'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`,'
				. ' `size`, `mtime`, `encrypted`, `unencrypted_size`, `storage_mtime`, `etag`, `permissions`'
				. ' FROM `*PREFIX*filecache` WHERE `fileid` = ?');
			$result = $query->execute(array($sourceId));
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
			$data['permissions'] = (int)$data['permissions'];
			if (!is_int($file) || $file === 0) {
				$data['path'] = '';
				$data['name'] = basename($this->storage->getMountPoint());
				$data['is_share_mount_point'] = true;
			}
			$data['permissions'] &= $this->storage->getPermissions('');
			return $data;
		}
		return false;
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folderId
	 * @return array
	 */
	public function getFolderContentsById($folderId) {
		$cache = $this->getSourceCache('');
		if ($cache) {
			$owner = $this->storage->getSharedFrom();
			$parentPath = $this->getPathById($folderId);
			if ($parentPath !== '') {
				$parentPath .= '/';
			}
			$sourceFolderContent = $cache->getFolderContentsById($folderId);
			foreach ($sourceFolderContent as &$c) {
				$c['path'] = ltrim($parentPath . $c['name'], '/');
				$c['uid_owner'] = $owner;
				$c['displayname_owner'] = \OC_User::getDisplayName($owner);
				$c['permissions'] = $c['permissions'] & $this->storage->getPermissions(false);
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

		$pattern = trim($pattern, '%');

		$normalizedPattern = $this->normalize($pattern);

		$result = array();
		$exploreDirs = array('');
		while (count($exploreDirs) > 0) {
			$dir = array_pop($exploreDirs);
			$files = $this->getFolderContents($dir);
			// no results?
			if (!$files) {
				// maybe it's a single shared file
				$file = $this->get('');
				if ($normalizedPattern === '' || stristr($file['name'], $normalizedPattern) !== false) {
					$result[] = $file;
				}
				continue;
			}
			foreach ($files as $file) {
				if ($normalizedPattern === '' || stristr($file['name'], $normalizedPattern) !== false) {
					$result[] = $file;
				}
				if ($file['mimetype'] === 'httpd/unix-directory') {
					$exploreDirs[] = ltrim($dir . '/' . $file['name'], '/');
				}
			}
		}
		return $result;

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
	 * Checks whether the given file has the given tag.
	 *
	 * @param \OCP\ITags $tagger
	 * @param array $fileData file data
	 * @param string $tag tag to check for
	 * @return boolean true if the given file has the expected tag,
	 * false otherwise
	 */
	private function hasTag($tagger, $fileData, $tag) {
		$tags = $tagger->getTagsForObjects(array((int)$fileData['fileid']));
		return (!empty($tags) && in_array($tag, current($tags)));
	}

	/**
	 * search for files by tag
	 *
	 * @param string|int $tag tag to search for
	 * @param string $userId owner of the tags
	 * @return array file data
	 */
	public function searchByTag($tag, $userId) {
		// TODO: inject this
		$tagger = \OC::$server->getTagManager()->load('files', null, null, $userId);
		$result = array();
		$exploreDirs = array('');
		// check if root is tagged
		$file = $this->get('');
		if ($this->hasTag($tagger, $file, $tag)) {
			$result[] = $file;
		}
		// FIXME: this is so wrong and unefficient, need to replace with actual DB queries
		while (count($exploreDirs) > 0) {
			$dir = array_pop($exploreDirs);
			$files = $this->getFolderContents($dir);
			if (!$files) {
				continue;
			}
			foreach ($files as $file) {
				if ($this->hasTag($tagger, $file, $tag)) {
					$result[] = $file;
				}
				if ($file['mimetype'] === 'httpd/unix-directory') {
					$exploreDirs[] = ltrim($dir . '/' . $file['name'], '/');
				}
			}
		}
		return $result;
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
	 * @return string|null
	 */
	public function getPathById($id, $pathEnd = '') {
		// direct shares are easy
		if ($id === $this->storage->getSourceId()) {
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

	/**
	 * @param integer $id
	 * @return array
	 */
	private function getParentInfo($id) {
		$sql = 'SELECT `parent`, `name` FROM `*PREFIX*filecache` WHERE `fileid` = ?';
		$query = \OC_DB::prepare($sql);
		$result = $query->execute(array($id));
		if ($row = $result->fetchRow()) {
			return array((int)$row['parent'], $row['name']);
		} else {
			return array(-1, '');
		}
	}
}
