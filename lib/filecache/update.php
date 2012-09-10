<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


/**
 * handles updating the filecache according to outside changes
 */
class OC_FileCache_Update{
	/**
	 * check if a file or folder is updated outside owncloud
	 * @param string path
	 * @param string root (optional)
	 * @param boolean folder
	 * @return bool
	 */
	public static function hasUpdated($path,$root=false,$folder=false) {
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}
		if(!$view->file_exists($path)) {
			return false;
		}
		$cachedData=OC_FileCache_Cached::get($path, $root);
		if(isset($cachedData['mtime'])) {
			$cachedMTime=$cachedData['mtime'];
			if($folder) {
				return $view->hasUpdated($path.'/', $cachedMTime);
			}else{
				return $view->hasUpdated($path, $cachedMTime);
			}
		}else{//file not in cache, so it has to be updated
			if(($path=='/' or $path=='') and $root===false) {//dont auto update the home folder, it will be scanned
				return false;
			}
			return true;
		}
	}

	/**
	 * delete non existing files from the cache
	 */
	public static function cleanFolder($path,$root=false) {
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}

		$cachedContent=OC_FileCache_Cached::getFolderContent($path,$root);
		foreach($cachedContent as $fileData) {
			$path=$fileData['path'];
			$file=$view->getRelativePath($path);
			if(!$view->file_exists($file)) {
				if($root===false) {//filesystem hooks are only valid for the default root
					OC_Hook::emit('OC_Filesystem', 'post_delete', array('path'=>$file));
				}else{
					self::delete($file, $root);
				}
			}
		}
	}

	/**
	 * update the cache according to changes in the folder
	 * @param string path
	 * @param string root (optional)
	 */
	public static function updateFolder($path,$root=false) {
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}
		$dh=$view->opendir($path.'/');
		if($dh) {//check for changed/new files
			while (($filename = readdir($dh)) !== false) {
				if($filename != '.' and $filename != '..') {
					$file=$path.'/'.$filename;
					if(self::hasUpdated($file, $root)) {
						if($root===false) {//filesystem hooks are only valid for the default root
							OC_Hook::emit('OC_Filesystem', 'post_write', array('path'=>$file));
						}else{
							self::update($file, $root);
						}
					}
				}
			}
		}

		self::cleanFolder($path, $root);

		//update the folder last, so we can calculate the size correctly
		if($root===false) {//filesystem hooks are only valid for the default root
			OC_Hook::emit('OC_Filesystem', 'post_write', array('path'=>$path));
		}else{
			self::update($path, $root);
		}
	}

	/**
	 * called when changes are made to files
	 * @param array $params
	 * @param string root (optional)
	 */
	public static function fileSystemWatcherWrite($params) {
		$path=$params['path'];
		self::update($path);
	}

	/**
	 * called when files are deleted
	 * @param array $params
	 * @param string root (optional)
	 */
	public static function fileSystemWatcherDelete($params) {
		$path=$params['path'];
		self::delete($path);
	}

	/**
	 * called when files are deleted
	 * @param array $params
	 * @param string root (optional)
	 */
	public static function fileSystemWatcherRename($params) {
		$oldPath=$params['oldpath'];
		$newPath=$params['newpath'];
		self::rename($oldPath, $newPath);
	}

	/**
	 * update the filecache according to changes to the fileysystem
	 * @param string path
	 * @param string root (optional)
	 */
	public static function update($path,$root=false) {
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}

		$mimetype=$view->getMimeType($path);

		$size=0;
		$cached=OC_FileCache_Cached::get($path,$root);
		$cachedSize=isset($cached['size'])?$cached['size']:0;

		if($view->is_dir($path.'/')) {
			if(OC_FileCache::inCache($path, $root)) {
				$cachedContent=OC_FileCache_Cached::getFolderContent($path, $root);
				foreach($cachedContent as $file) {
					$size+=$file['size'];
				}
				$mtime=$view->filemtime($path.'/');
				$ctime=$view->filectime($path.'/');
				$writable=$view->is_writable($path.'/');
				OC_FileCache::put($path, array('size'=>$size,'mtime'=>$mtime,'ctime'=>$ctime,'mimetype'=>$mimetype,'writable'=>$writable));
			}else{
				$count=0;
				OC_FileCache::scan($path, null, $count, $root);
				return; //increaseSize is already called inside scan
			}
		}else{
			$size=OC_FileCache::scanFile($path, $root);
		}
		OC_FileCache::increaseSize(dirname($path), $size-$cachedSize, $root);
	}

	/**
	 * update the filesystem after a delete has been detected
	 * @param string path
	 * @param string root (optional)
	 */
	public static function delete($path,$root=false) {
		$cached=OC_FileCache_Cached::get($path, $root);
		if(!isset($cached['size'])) {
			return;
		}
		$size=$cached['size'];
		OC_FileCache::increaseSize(dirname($path), -$size, $root);
		OC_FileCache::delete($path, $root);
	}

	/**
	 * update the filesystem after a rename has been detected
	 * @param string oldPath
	 * @param string newPath
	 * @param string root (optional)
	 */
	public static function rename($oldPath,$newPath,$root=false) {
		if(!OC_FileCache::inCache($oldPath, $root)) {
			return;
		}
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}

		$cached=OC_FileCache_Cached::get($oldPath, $root);
		$oldSize=$cached['size'];
		OC_FileCache::increaseSize(dirname($oldPath), -$oldSize, $root);
		OC_FileCache::increaseSize(dirname($newPath), $oldSize, $root);
		OC_FileCache::move($oldPath, $newPath);
	}
}