<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

/**
 * Provide a common interface to all different storage options
 */
interface Storage{
	public function __construct($parameters);
	public function getId();
	public function mkdir($path);
	public function rmdir($path);
	public function opendir($path);
	public function is_dir($path);
	public function is_file($path);
	public function stat($path);
	public function filetype($path);
	public function filesize($path);
	public function isCreatable($path);
	public function isReadable($path);
	public function isUpdatable($path);
	public function isDeletable($path);
	public function isSharable($path);
	public function getPermissions($path);
	public function file_exists($path);
	public function filemtime($path);
	public function file_get_contents($path);
	public function file_put_contents($path,$data);
	public function unlink($path);
	public function rename($path1,$path2);
	public function copy($path1,$path2);
	public function fopen($path,$mode);
	public function getMimeType($path);
	public function hash($type,$path,$raw = false);
	public function free_space($path);
	public function search($query);
	public function touch($path, $mtime=null);
	public function getLocalFile($path);// get a path to a local version of the file, whether the original file is local or remote
	public function getLocalFolder($path);// get a path to a local version of the folder, whether the original file is local or remote
	/**
	 * check if a file or folder has been updated since $time
	 * @param int $time
	 * @return bool
	 *
	 * hasUpdated for folders should return at least true if a file inside the folder is add, removed or renamed.
	 * returning true for other changes in the folder is optional
	 */
	public function hasUpdated($path,$time);

	/**
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache();
	/**
	 * @return \OC\Files\Cache\Scanner
	 */
	public function getScanner();

	public function getOwner($path);
}
