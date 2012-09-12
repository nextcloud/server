<?php

/**
* @author Robin Appelman
* @copyright 2011 Robin Appelman icewind1991@gmail.com
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

/**
 * provide caching for filesystem info in the database
 *
 * not used by OC_Filesystem for reading filesystem info,
 * instread apps should use OC_FileCache::get where possible
 *
 * It will try to keep the data up to date but changes from outside ownCloud can invalidate the cache
 */
class OC_FileCache{
	/**
	 * get the filesystem info from the cache
	 * @param string path
	 * @param string root (optional)
	 * @return array
	 *
	 * returns an associative array with the following keys:
	 * - size
	 * - mtime
	 * - ctime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function get($path,$root=false) {
		if(OC_FileCache_Update::hasUpdated($path,$root)) {
			if($root===false) {//filesystem hooks are only valid for the default root
				OC_Hook::emit('OC_Filesystem','post_write',array('path'=>$path));
			}else{
				OC_FileCache_Update::update($path,$root);
			}
		}
		return OC_FileCache_Cached::get($path,$root);
	}

	/**
	 * put filesystem info in the cache
	 * @param string $path
	 * @param array data
	 * @param string root (optional)
	 *
	 * $data is an assiciative array in the same format as returned by get
	 */
	public static function put($path,$data,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		$fullpath=$root.$path;
		$parent=self::getParentId($fullpath);
		$id=self::getId($fullpath,'');
		if(isset(OC_FileCache_Cached::$savedData[$fullpath])) {
			$data=array_merge(OC_FileCache_Cached::$savedData[$fullpath],$data);
			unset(OC_FileCache_Cached::$savedData[$fullpath]);
		}
		if($id!=-1) {
			self::update($id,$data);
			return;
		}

		// add parent directory to the file cache if it does not exist yet.
		if ($parent == -1 && $fullpath != $root) {
			$parentDir = substr(dirname($path), 0, strrpos(dirname($path), DIRECTORY_SEPARATOR));
			self::scanFile($parentDir);
			$parent = self::getParentId($fullpath);
		}

		if(!isset($data['size']) or !isset($data['mtime'])) {//save incomplete data for the next time we write it
			OC_FileCache_Cached::$savedData[$fullpath]=$data;
			return;
		}
		if(!isset($data['encrypted'])) {
			$data['encrypted']=false;
		}
		if(!isset($data['versioned'])) {
			$data['versioned']=false;
		}
		$mimePart=dirname($data['mimetype']);
		$data['size']=(int)$data['size'];
		$data['ctime']=(int)$data['mtime'];
		$data['writable']=(int)$data['writable'];
		$data['encrypted']=(int)$data['encrypted'];
		$data['versioned']=(int)$data['versioned'];
		$user=OC_User::getUser();
		$query=OC_DB::prepare('INSERT INTO `*PREFIX*fscache`(`parent`, `name`, `path`, `path_hash`, `size`, `mtime`, `ctime`, `mimetype`, `mimepart`,`user`,`writable`,`encrypted`,`versioned`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$result=$query->execute(array($parent,basename($fullpath),$fullpath,md5($fullpath),$data['size'],$data['mtime'],$data['ctime'],$data['mimetype'],$mimePart,$user,$data['writable'],$data['encrypted'],$data['versioned']));
		if(OC_DB::isError($result)) {
			OC_Log::write('files','error while writing file('.$fullpath.') to cache',OC_Log::ERROR);
		}

		if($cache=OC_Cache::getUserCache(true)) {
			$cache->remove('fileid/'.$fullpath);//ensure we don't have -1 cached
		}
	}

	/**
	 * update filesystem info of a file
	 * @param int $id
	 * @param array $data
	 */
	private static function update($id,$data) {
		$arguments=array();
		$queryParts=array();
		foreach(array('size','mtime','ctime','mimetype','encrypted','versioned','writable') as $attribute) {
			if(isset($data[$attribute])) {
				//Convert to int it args are false
				if($data[$attribute] === false) {
					$arguments[] = 0;
				}else{
					$arguments[] = $data[$attribute];
				}
				$queryParts[]='`'.$attribute.'`=?';
			}
		}
		if(isset($data['mimetype'])) {
			$arguments[]=dirname($data['mimetype']);
			$queryParts[]='`mimepart`=?';
		}
		$arguments[]=$id;

		$sql = 'UPDATE `*PREFIX*fscache` SET '.implode(' , ',$queryParts).' WHERE `id`=?';
		$query=OC_DB::prepare($sql);
		$result=$query->execute($arguments);
		if(OC_DB::isError($result)) {
			OC_Log::write('files','error while updating file('.$id.') in cache',OC_Log::ERROR);
		}
	}

	/**
	 * register a file move in the cache
	 * @param string oldPath
	 * @param string newPath
	 * @param string root (optional)
	 */
	public static function move($oldPath,$newPath,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		// If replacing an existing file, delete the file
		if (self::inCache($newPath, $root)) {
			self::delete($newPath, $root);
		}
		$oldPath=$root.$oldPath;
		$newPath=$root.$newPath;
		$newParent=self::getParentId($newPath);
		$query=OC_DB::prepare('UPDATE `*PREFIX*fscache` SET `parent`=? ,`name`=?, `path`=?, `path_hash`=? WHERE `path_hash`=?');
		$query->execute(array($newParent,basename($newPath),$newPath,md5($newPath),md5($oldPath)));

		if(($cache=OC_Cache::getUserCache(true)) && $cache->hasKey('fileid/'.$oldPath)) {
			$cache->set('fileid/'.$newPath,$cache->get('fileid/'.$oldPath));
			$cache->remove('fileid/'.$oldPath);
		}

		$query=OC_DB::prepare('SELECT `path` FROM `*PREFIX*fscache` WHERE `path` LIKE ?');
		$oldLength=strlen($oldPath);
		$updateQuery=OC_DB::prepare('UPDATE `*PREFIX*fscache` SET `path`=?, `path_hash`=? WHERE `path_hash`=?');
		while($row= $query->execute(array($oldPath.'/%'))->fetchRow()) {
			$old=$row['path'];
			$new=$newPath.substr($old,$oldLength);
			$updateQuery->execute(array($new,md5($new),md5($old)));

			if(($cache=OC_Cache::getUserCache(true)) && $cache->hasKey('fileid/'.$old)) {
				$cache->set('fileid/'.$new,$cache->get('fileid/'.$old));
				$cache->remove('fileid/'.$old);
			}
		}
	}

	/**
	 * delete info from the cache
	 * @param string path
	 * @param string root (optional)
	 */
	public static function delete($path,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		$query=OC_DB::prepare('DELETE FROM `*PREFIX*fscache` WHERE `path_hash`=?');
		$query->execute(array(md5($root.$path)));

		//delete everything inside the folder
		$query=OC_DB::prepare('DELETE FROM `*PREFIX*fscache` WHERE `path` LIKE ?');
		$query->execute(array($root.$path.'/%'));

		OC_Cache::remove('fileid/'.$root.$path);
	}

	/**
	 * return array of filenames matching the querty
	 * @param string $query
	 * @param boolean $returnData
	 * @param string root (optional)
	 * @return array of filepaths
	 */
	public static function search($search,$returnData=false,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		$rootLen=strlen($root);
		if(!$returnData) {
			$query=OC_DB::prepare('SELECT `path` FROM `*PREFIX*fscache` WHERE `name` LIKE ? AND `user`=?');
		}else{
			$query=OC_DB::prepare('SELECT * FROM `*PREFIX*fscache` WHERE `name` LIKE ? AND `user`=?');
		}
		$result=$query->execute(array("%$search%",OC_User::getUser()));
		$names=array();
		while($row=$result->fetchRow()) {
			if(!$returnData) {
				$names[]=substr($row['path'],$rootLen);
			}else{
				$row['path']=substr($row['path'],$rootLen);
				$names[]=$row;
			}
		}
		return $names;
	}

	/**
	 * get all files and folders in a folder
	 * @param string path
	 * @param string root (optional)
	 * @return array
	 *
	 * returns an array of assiciative arrays with the following keys:
	 * - name
	 * - size
	 * - mtime
	 * - ctime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function getFolderContent($path,$root=false,$mimetype_filter='') {
		if(OC_FileCache_Update::hasUpdated($path,$root,true)) {
			OC_FileCache_Update::updateFolder($path,$root);
		}
		return OC_FileCache_Cached::getFolderContent($path,$root,$mimetype_filter);
	}

	/**
	 * check if a file or folder is in the cache
	 * @param string $path
	 * @param string root (optional)
	 * @return bool
	 */
	public static function inCache($path,$root=false) {
		return self::getId($path,$root)!=-1;
	}

	/**
	 * get the file id as used in the cache
	 * @param string path
	 * @param string root (optional)
	 * @return int
	 */
	public static function getId($path,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}

		$fullPath=$root.$path;
		if(($cache=OC_Cache::getUserCache(true)) && $cache->hasKey('fileid/'.$fullPath)) {
			return $cache->get('fileid/'.$fullPath);
		}

		$query=OC_DB::prepare('SELECT `id` FROM `*PREFIX*fscache` WHERE `path_hash`=?');
		$result=$query->execute(array(md5($fullPath)));
		if(OC_DB::isError($result)) {
			OC_Log::write('files','error while getting file id of '.$path,OC_Log::ERROR);
			return -1;
		}

		$result=$result->fetchRow();
		if(is_array($result)) {
			$id=$result['id'];
		}else{
			$id=-1;
		}
		if($cache=OC_Cache::getUserCache(true)) {
			$cache->set('fileid/'.$fullPath,$id);
		}

		return $id;
	}

	/**
	 * get the file path from the id, relative to the home folder of the user
	 * @param int id
	 * @param string user (optional)
	 * @return string
	 */
	public static function getPath($id,$user='') {
		if(!$user) {
			$user=OC_User::getUser();
		}
		$query=OC_DB::prepare('SELECT `path` FROM `*PREFIX*fscache` WHERE `id`=? AND `user`=?');
		$result=$query->execute(array($id,$user));
		$row=$result->fetchRow();
		$path=$row['path'];
		$root='/'.$user.'/files';
		if(substr($path,0,strlen($root))!=$root) {
			return false;
		}
		return substr($path,strlen($root));
	}

	/**
	 * get the file id of the parent folder, taking into account '/' has no parent
	 * @param string $path
	 * @return int
	 */
	private static function getParentId($path) {
		if($path=='/') {
			return -1;
		}else{
			return self::getId(dirname($path),'');
		}
	}

	/**
	 * adjust the size of the parent folders
	 * @param string $path
	 * @param int $sizeDiff
	 * @param string root (optinal)
	 */
	public static function increaseSize($path,$sizeDiff, $root=false) {
		if($sizeDiff==0) return;
		$id=self::getId($path,$root);
		while($id!=-1) {//walk up the filetree increasing the size of all parent folders
			$query=OC_DB::prepare('UPDATE `*PREFIX*fscache` SET `size`=`size`+? WHERE `id`=?');
			$query->execute(array($sizeDiff,$id));
			$id=self::getParentId($path);
			$path=dirname($path);
		}
	}

	/**
	 * recursively scan the filesystem and fill the cache
	 * @param string $path
	 * @param OC_EventSource $enventSource (optional)
	 * @param int count (optional)
	 * @param string root (optional)
	 */
	public static function scan($path,$eventSource=false,&$count=0,$root=false) {
		if($eventSource) {
			$eventSource->send('scanning',array('file'=>$path,'count'=>$count));
		}
		$lastSend=$count;
		// NOTE: Ugly hack to prevent shared files from going into the cache (the source already exists somewhere in the cache)
		if (substr($path, 0, 7) == '/Shared') {
			return;
		}
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}
		self::scanFile($path,$root);
		$dh=$view->opendir($path.'/');
		$totalSize=0;
		if($dh) {
			while (($filename = readdir($dh)) !== false) {
				if($filename != '.' and $filename != '..') {
					$file=$path.'/'.$filename;
					if($view->is_dir($file.'/')) {
						self::scan($file,$eventSource,$count,$root);
					}else{
						$totalSize+=self::scanFile($file,$root);
						$count++;
						if($count>$lastSend+25 and $eventSource) {
							$lastSend=$count;
							$eventSource->send('scanning',array('file'=>$path,'count'=>$count));
						}
					}
				}
			}
		}

		OC_FileCache_Update::cleanFolder($path,$root);
		self::increaseSize($path,$totalSize,$root);
	}

	/**
	 * scan a single file
	 * @param string path
	 * @param string root (optional)
	 * @return int size of the scanned file
	 */
	public static function scanFile($path,$root=false) {
		// NOTE: Ugly hack to prevent shared files from going into the cache (the source already exists somewhere in the cache)
		if (substr($path, 0, 7) == '/Shared') {
			return;
		}
		if($root===false) {
			$view=OC_Filesystem::getView();
		}else{
			$view=new OC_FilesystemView($root);
		}
		if(!$view->is_readable($path)) return; //cant read, nothing we can do
		clearstatcache();
		$mimetype=$view->getMimeType($path);
		$stat=$view->stat($path);
		if($mimetype=='httpd/unix-directory') {
			$writable=$view->is_writable($path.'/');
		}else{
			$writable=$view->is_writable($path);
		}
		$stat['mimetype']=$mimetype;
		$stat['writable']=$writable;
		if($path=='/') {
			$path='';
		}
		self::put($path,$stat,$root);
		return $stat['size'];
	}

	/**
	 * find files by mimetype
	 * @param string $part1
	 * @param string $part2 (optional)
	 * @param string root (optional)
	 * @return array of file paths
	 *
	 * $part1 and $part2 together form the complete mimetype.
	 * e.g. searchByMime('text','plain')
	 *
	 * seccond mimetype part can be ommited
	 * e.g. searchByMime('audio')
	 */
	public static function searchByMime($part1,$part2=null,$root=false) {
		if($root===false) {
			$root=OC_Filesystem::getRoot();
		}
		$rootLen=strlen($root);
		$root .= '%';
		$user=OC_User::getUser();
		if(!$part2) {
			$query=OC_DB::prepare('SELECT `path` FROM `*PREFIX*fscache` WHERE `mimepart`=? AND `user`=? AND `path` LIKE ?');
			$result=$query->execute(array($part1,$user, $root));
		}else{
			$query=OC_DB::prepare('SELECT `path` FROM `*PREFIX*fscache` WHERE `mimetype`=? AND `user`=? AND `path` LIKE ? ');
			$result=$query->execute(array($part1.'/'.$part2,$user, $root));
		}
		$names=array();
		while($row=$result->fetchRow()) {
			$names[]=substr($row['path'],$rootLen);
		}
		return $names;
	}

	/**
	 * clean old pre-path_hash entries
	 */
	public static function clean() {
		$query=OC_DB::prepare('DELETE FROM `*PREFIX*fscache` WHERE LENGTH(`path_hash`)<30');
		$query->execute();
	}

	/**
	 * clear filecache entries
	 * @param string user (optonal)
	 */
	public static function clear($user='') {
		if($user) {
			$query=OC_DB::prepare('DELETE FROM `*PREFIX*fscache` WHERE user=?');
			$query->execute(array($user));
		}else{
			$query=OC_DB::prepare('DELETE FROM `*PREFIX*fscache`');
			$query->execute();
		}
	}
}

//watch for changes and try to keep the cache up to date
OC_Hook::connect('OC_Filesystem','post_write','OC_FileCache_Update','fileSystemWatcherWrite');
OC_Hook::connect('OC_Filesystem','post_delete','OC_FileCache_Update','fileSystemWatcherDelete');
OC_Hook::connect('OC_Filesystem','post_rename','OC_FileCache_Update','fileSystemWatcherRename');
