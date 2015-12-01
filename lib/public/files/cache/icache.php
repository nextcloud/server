<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCP\Files\Cache;

/**
 * Metadata cache for a storage
 *
 * The cache stores the metadata for all files and folders in a storage and is kept up to date trough the following mechanisms:
 *
 * - Scanner: scans the storage and updates the cache where needed
 * - Watcher: checks for changes made to the filesystem outside of the ownCloud instance and rescans files and folder when a change is detected
 * - Updater: listens to changes made to the filesystem inside of the ownCloud instance and updates the cache where needed
 * - ChangePropagator: updates the mtime and etags of parent folders whenever a change to the cache is made to the cache by the updater
 */
interface ICache {
	const NOT_FOUND = 0;
	const PARTIAL = 1; //only partial data available, file not cached in the database
	const SHALLOW = 2; //folder in cache, but not all child files are completely scanned
	const COMPLETE = 3;

	/**
	 * Get the numeric storage id for this cache's storage
	 *
	 * @return int
	 */
	public function getNumericStorageId();

	/**
	 * get the stored metadata of a file or folder
	 *
	 * the returned cache entry contains at least the following values:
	 * [
	 *        'fileid' => int, the numeric id of a file (see getId)
	 *        'storage' => int, the numeric id of the storage the file is stored on
	 *        'path' => string, the path of the file within the storage ('foo/bar.txt')
	 *        'name' => string, the basename of a file ('bar.txt)
	 *        'mimetype' => string, the full mimetype of the file ('text/plain')
	 *        'mimepart' => string, the first half of the mimetype ('text')
	 *        'size' => int, the size of the file or folder in bytes
	 *        'mtime' => int, the last modified date of the file as unix timestamp as shown in the ui
	 *        'storage_mtime' => int, the last modified date of the file as unix timestamp as stored on the storage
	 *            Note that when a file is updated we also update the mtime of all parent folders to make it visible to the user which folder has had updates most recently
	 *            This can differ from the mtime on the underlying storage which usually only changes when a direct child is added, removed or renamed
	 *        'etag' => string, the etag for the file
	 *            An etag is used for change detection of files and folders, an etag of a file changes whenever the content of the file changes
	 *            Etag for folders change whenever a file in the folder has changed
	 *        'permissions' int, the permissions for the file stored as bitwise combination of \OCP\PERMISSION_READ, \OCP\PERMISSION_CREATE
	 *            \OCP\PERMISSION_UPDATE, \OCP\PERMISSION_DELETE and \OCP\PERMISSION_SHARE
	 * ]
	 *
	 * @param string | int $file either the path of a file or folder or the file id for a file or folder
	 * @return array|false the cache entry as array of false if the file is not found in the cache
	 */
	public function get($file);

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return array
	 */
	public function getFolderContents($folder);

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param int $fileId the file id of the folder
	 * @return array
	 */
	public function getFolderContentsById($fileId);

	/**
	 * store meta data for a file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 */
	public function put($file, array $data);

	/**
	 * update the metadata of an existing file or folder in the cache
	 *
	 * @param int $id the fileid of the existing file or folder
	 * @param array $data [$key => $value] the metadata to update, only the fields provided in the array will be updated, non-provided values will remain unchanged
	 */
	public function update($id, array $data);

	/**
	 * get the file id for a file
	 *
	 * A file id is a numeric id for a file or folder that's unique within an owncloud instance which stays the same for the lifetime of a file
	 *
	 * File ids are easiest way for apps to store references to a file since unlike paths they are not affected by renames or sharing
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file);

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file);

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file);

	/**
	 * remove a file or folder from the cache
	 *
	 * when removing a folder from the cache all files and folders inside the folder will be removed as well
	 *
	 * @param string $file
	 */
	public function remove($file);

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target);

	/**
	 * Move a file or folder in the cache
	 *
	 * @param \OCP\Files\Cache\ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @throws \OC\DatabaseException
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath);

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear();

	/**
	 * Get the scan status of a file
	 *
	 * - ICache::NOT_FOUND: File is not in the cache
	 * - ICache::PARTIAL: File is not stored in the cache but some incomplete data is known
	 * - ICache::SHALLOW: The folder and it's direct children are in the cache but not all sub folders are fully scanned
	 * - ICache::COMPLETE: The file or folder, with all it's children) are fully scanned
	 *
	 * @param string $file
	 *
	 * @return int ICache::NOT_FOUND, ICache::PARTIAL, ICache::SHALLOW or ICache::COMPLETE
	 */
	public function getStatus($file);

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern the search pattern using SQL search syntax (e.g. '%searchstring%')
	 * @return array an array of cache entries where the name matches the search pattern
	 */
	public function search($pattern);

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype either a full mimetype to search ('text/plain') or only the first part of a mimetype ('image')
	 *        where it will search for all mimetypes in the group ('image/*')
	 * @return array  an array of cache entries where the mimetype matches the search
	 */
	public function searchByMime($mimetype);

	/**
	 * Search for files by tag of a given users.
	 *
	 * Note that every user can tag files differently.
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return array file data
	 */
	public function searchByTag($tag, $userId);

	/**
	 * Re-calculate the folder size and the size of all parent folders
	 *
	 * @param string|boolean $path
	 * @param array $data (optional) meta data of the folder
	 */
	public function correctFolderSize($path, $data = null);

	/**
	 * calculate the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array $entry (optional) meta data of the folder
	 * @return int
	 */
	public function calculateFolderSize($path, $entry = null);

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll();

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiple incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|bool the path of the folder or false when no folder matched
	 */
	public function getIncomplete();

	/**
	 * get the path of a file on this storage by it's file id
	 *
	 * @param int $id the file id of the file or folder to search
	 * @return string|null the path of the file (relative to the storage) or null if a file with the given id does not exists within this cache
	 */
	public function getPathById($id);

	/**
	 * normalize the given path for usage in the cache
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalize($path);
}
