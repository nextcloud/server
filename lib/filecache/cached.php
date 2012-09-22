<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


/**
 * get data from the filecache without checking for updates
 */
class OC_FileCache_Cached{
	public static $savedData=array();

	public static function get($path,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		$path=$root.$path;
		$stmt=OC_DB::prepare('SELECT `path`,`ctime`,`mtime`,`mimetype`,`size`,`encrypted`,`versioned`,`writable` FROM `*PREFIX*fscache` WHERE `path_hash`=?');
		if ( ! OC_DB::isError($stmt) ) {
			$result=$stmt->execute(array(md5($path)));
			if ( ! OC_DB::isError($result) ) {
				$result = $result->fetchRow();
			} else {
				OC:Log::write('OC_FileCache_Cached', 'could not execute get: '. OC_DB::getErrorMessage($result), OC_Log::ERROR);
				$result = false;
			}
		} else {
			OC_Log::write('OC_FileCache_Cached', 'could not prepare get: '. OC_DB::getErrorMessage($stmt), OC_Log::ERROR);
			$result = false;
		}
		if(is_array($result)) {
			if(isset(self::$savedData[$path])) {
				$result=array_merge($result, self::$savedData[$path]);
			}
			return $result;
		}else{
			if(isset(self::$savedData[$path])) {
				return self::$savedData[$path];
			}else{
				return array();
			}
		}
	}

	/**
	 * get all files and folders in a folder
	 * @param string path
	 * @param string root (optional)
	 * @return array
	 *
	 * returns an array of assiciative arrays with the following keys:
	 * - path
	 * - name
	 * - size
	 * - mtime
	 * - ctime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function getFolderContent($path,$root=false,$mimetype_filter='') {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		$parent=OC_FileCache::getId($path, $root);
		if($parent==-1) {
			return array();
		}
		$query=OC_DB::prepare('SELECT `id`,`path`,`name`,`ctime`,`mtime`,`mimetype`,`size`,`encrypted`,`versioned`,`writable` FROM `*PREFIX*fscache` WHERE `parent`=? AND (`mimetype` LIKE ? OR `mimetype` = ?)');
		$result=$query->execute(array($parent, $mimetype_filter.'%', 'httpd/unix-directory'))->fetchAll();
		if(is_array($result)) {
			return $result;
		}else{
			OC_Log::write('files', 'getFolderContent(): file not found in cache ('.$path.')', OC_Log::DEBUG);
			return false;
		}
	}
}