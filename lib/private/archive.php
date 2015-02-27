<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

abstract class OC_Archive{
	/**
	 * open any of the supported archive types
	 * @param string $path
	 * @return OC_Archive|void
	 */
	public static function open($path) {
		$ext=substr($path, strrpos($path, '.'));
		switch($ext) {
			case '.zip':
				return new OC_Archive_ZIP($path);
			case '.gz':
			case '.bz':
			case '.bz2':
			case '.tgz':
			case '.tar':
				return new OC_Archive_TAR($path);
		}
	}

	/**
	 * @param $source
	 */
	abstract function __construct($source);
	/**
	 * add an empty folder to the archive
	 * @param string $path
	 * @return bool
	 */
	abstract function addFolder($path);
	/**
	 * add a file to the archive
	 * @param string $path
	 * @param string $source either a local file or string data
	 * @return bool
	 */
	abstract function addFile($path, $source='');
	/**
	 * rename a file or folder in the archive
	 * @param string $source
	 * @param string $dest
	 * @return bool
	 */
	abstract function rename($source, $dest);
	/**
	 * get the uncompressed size of a file in the archive
	 * @param string $path
	 * @return int
	 */
	abstract function filesize($path);
	/**
	 * get the last modified time of a file in the archive
	 * @param string $path
	 * @return int
	 */
	abstract function mtime($path);
	/**
	 * get the files in a folder
	 * @param string $path
	 * @return array
	 */
	abstract function getFolder($path);
	/**
	 * get all files in the archive
	 * @return array
	 */
	abstract function getFiles();
	/**
	 * get the content of a file
	 * @param string $path
	 * @return string
	 */
	abstract function getFile($path);
	/**
	 * extract a single file from the archive
	 * @param string $path
	 * @param string $dest
	 * @return bool
	 */
	abstract function extractFile($path, $dest);
	/**
	 * extract the archive
	 * @param string $dest
	 * @return bool
	 */
	abstract function extract($dest);
	/**
	 * check if a file or folder exists in the archive
	 * @param string $path
	 * @return bool
	 */
	abstract function fileExists($path);
	/**
	 * remove a file or folder from the archive
	 * @param string $path
	 * @return bool
	 */
	abstract function remove($path);
	/**
	 * get a file handler
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	abstract function getStream($path, $mode);
	/**
	 * add a folder and all its content
	 * @param string $path
	 * @param string $source
	 * @return boolean|null
	 */
	function addRecursive($path, $source) {
		$dh = opendir($source);
		if(is_resource($dh)) {
			$this->addFolder($path);
			while (($file = readdir($dh)) !== false) {
				if($file=='.' or $file=='..') {
					continue;
				}
				if(is_dir($source.'/'.$file)) {
					$this->addRecursive($path.'/'.$file, $source.'/'.$file);
				}else{
					$this->addFile($path.'/'.$file, $source.'/'.$file);
				}
			}
		}
	}
}
