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
	 * @return array
	 *
	 * returns an assiciative array with the following keys:
	 * - size
	 * - mtime
	 * - ctime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public static function get($path){
		$path=OC_Filesystem::getRoot().$path;
		$query=OC_DB::prepare('SELECT ctime,mtime,mimetype,size,encrypted,versioned FROM *PREFIX*fscache WHERE path=?');
		$result=$query->execute(array($path))->fetchRow();
		if(is_array($result)){
			return $result;
		}else{
			OC_Log::write('file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return false;
		}
	}

	/**
	 * put filesystem info in the cache
	 * @param string $path
	 * @param array data
	 *
	 * $data is an assiciative array in the same format as returned by get
	 */
	public static function put($path,$data){
		$path=OC_Filesystem::getRoot().$path;
		if($path=='/'){
			$parent=-1;
		}else{
			$parent=self::getFileId(dirname($path));
		}
		$id=self::getFileId($path);
		if($id!=-1){
			self::update($id,$data);
			return;
		}
		if(!isset($data['encrypted'])){
			$data['encrypted']=false;
		}
		if(!isset($data['versioned'])){
			$data['versioned']=false;
		}
		$mimePart=dirname($data['mimetype']);
		$user=OC_User::getUser();
		$query=OC_DB::prepare('INSERT INTO *PREFIX*fscache(parent, name, path, size, mtime, ctime, mimetype, mimepart,user) VALUES(?,?,?,?,?,?,?,?,?)');
		$query->execute(array($parent,basename($path),$path,$data['size'],$data['mtime'],$data['ctime'],$data['mimetype'],$mimePart,$user));
		
	}

	/**
	 * update filesystem info of a file
	 * @param int $id
	 * @param array $data
	 */
	private static function update($id,$data){
		$arguments=array();
		$queryParts=array();
		foreach(array('size','mtime','ctime','mimetype','encrypted','versioned') as $attribute){
			if(isset($data[$attribute])){
				$arguments[]=$data[$attribute];
				$queryParts[]=$attribute.'=?';
			}
		}
		if(isset($data['mimetype'])){
			$arguments[]=dirname($data['mimetype']);
			$queryParts[]='mimepart=?';
		}
		$arguments[]=$id;
		$query=OC_DB::prepare('UPDATE *PREFIX*fscache SET '.implode(' , ',$queryParts).' WHERE id=?');
		$query->execute($arguments);
	}

	/**
	 * register a file move in the cache
	 * @param string oldPath
	 * @param string newPath
	 */
	public static function move($oldPath,$newPath){
		$oldPath=OC_Filesystem::getRoot().$oldPath;
		$newPath=OC_Filesystem::getRoot().$newPath;
		$newParent=self::getParentId($newPath);
		$query=OC_DB::prepare('UPDATE *PREFIX*fscache SET parent=? ,name=?, path=? WHERE path=?');
		$query->execute(array($newParent,basename($newPath),$newPath,$oldPath));
	}

	/**
	 * delete info from the cache
	 * @param string $path
	 */
	public static function delete($path){
		$path=OC_Filesystem::getRoot().$path;
		$query=OC_DB::prepare('DELETE FROM *PREFIX*fscache WHERE path=?');
		$query->execute(array($path));
	}
	
	/**
	 * return array of filenames matching the querty
	 * @param string $query
	 * @param boolean $returnData
	 * @return array of filepaths
	 */
	public static function search($search,$returnData=false){
		$root=OC_Filesystem::getRoot();
		$rootLen=strlen($root);
		if(!$returnData){
			$query=OC_DB::prepare('SELECT path FROM *PREFIX*fscache WHERE name LIKE ? AND user=?');
		}else{
			$query=OC_DB::prepare('SELECT * FROM *PREFIX*fscache WHERE name LIKE ? AND user=?');
		}
		$result=$query->execute(array("%$search%",OC_User::getUser()));
		$names=array();
		while($row=$result->fetchRow()){
			if(!$returnData){
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
	public static function getFolderContent($path){
		$path=OC_Filesystem::getRoot().$path;
		$parent=self::getFileId($path);
		$query=OC_DB::prepare('SELECT name,ctime,mtime,mimetype,size,encrypted,versioned FROM *PREFIX*fscache WHERE parent=?');
		$result=$query->execute(array($parent))->fetchAll();
		if(is_array($result)){
			return $result;
		}else{
			OC_Log::write('file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return false;
		}
	}

	/**
	 * check if a file or folder is in the cache
	 * @param string $path
	 * @return bool
	 */
	public static function inCache($path){
		$path=OC_Filesystem::getRoot().$path;
		$inCache=self::getFileId($path)!=-1;
		return $inCache;
	}

	/**
	 * get the file id as used in the cache
	 * @param string $path
	 * @return int
	 */
	private static function getFileId($path){
		$query=OC_DB::prepare('SELECT id FROM *PREFIX*fscache WHERE path=?');
		$result=$query->execute(array($path))->fetchRow();
		if(is_array($result)){
			return $result['id'];
		}else{
			OC_Log::write('file not found in cache ('.$path.')','core',OC_Log::DEBUG);
			return -1;
		}
	}

	/**
	 * get the file id of the parent folder, taking into account '/' has no parent
	 * @param string $path
	 * @return int
	 */
	private static function getParentId($path){
		if($path=='/'){
			return -1;
		}else{
			return self::getFileId(dirname($path));
		}
	}

	/**
	 * called when changes are made to files
	 */
	public static function fileSystemWatcherWrite($params){
		$path=$params['path'];
		$fullPath=OC_Filesystem::getRoot().$path;
		$mimetype=OC_Filesystem::getMimeType($path);
		if($mimetype=='httpd/unix-directory'){
			$size=0;
		}else{
			$id=self::getFileId($fullPath);
			if($id!=-1){
				$oldInfo=self::get($path);
				$oldSize=$oldInfo['size'];
			}else{
				$oldSize=0;
			}
			$size=OC_Filesystem::filesize($path);
			self::increaseSize(dirname($fullPath),$size-$oldSize);
		}
		$mtime=OC_Filesystem::filemtime($path);
		$ctime=OC_Filesystem::filectime($path);
		self::put($path,array('size'=>$size,'mtime'=>$mtime,'ctime'=>$ctime,'mimetype'=>$mimetype));
	}

	/**
	 * called when files are deleted
	 */
	public static function fileSystemWatcherDelete($params){
		$path=$params['path'];
		$fullPath=OC_Filesystem::getRoot().$path;
		if(self::getFileId($fullPath)==-1){
			return;
		}
		$size=OC_Filesystem::filesize($path);
		self::increaseSize(dirname($fullPath),-$size);
		self::delete($path);
	}

	/**
	 * called when files are deleted
	 */
	public static function fileSystemWatcherRename($params){
		$oldPath=$params['oldpath'];
		$newPath=$params['newpath'];
		$fullOldPath=OC_Filesystem::getRoot().$oldPath;
		$fullNewPath=OC_Filesystem::getRoot().$newPath;
		if(($id=self::getFileId($fullOldPath))!=-1){
			$oldInfo=self::get($fullOldPath);
			$oldSize=$oldInfo['size'];
		}else{
			return;
		}
		$size=OC_Filesystem::filesize($oldPath);
		self::increaseSize(dirname($fullOldPath),-$oldSize);
		self::increaseSize(dirname($fullNewPath),$oldSize);
		self::move($oldPath,$newPath);
	}

	/**
	 * adjust the size of the parent folders
	 * @param string $path
	 * @param int $sizeDiff
	 */
	private static function increaseSize($path,$sizeDiff){
		while(($id=self::getFileId($path))!=-1){
			$query=OC_DB::prepare('UPDATE *PREFIX*fscache SET size=size+? WHERE id=?');
			$query->execute(array($sizeDiff,$id));
			$path=dirname($path);
		}
	}

	/**
	 * recursively scan the filesystem and fill the cache
	 * @param string $path
	 * @param bool $onlyChilds
	 * @param OC_EventSource $enventSource
	 */
	public static function scan($path,$onlyChilds,$eventSource){
		$dh=OC_Filesystem::opendir($path);
		$stat=OC_Filesystem::stat($path);
		$mimetype=OC_Filesystem::getMimeType($path);
		$stat['mimetype']=$mimetype;
		if($path=='/'){
			$path='';
		}
		self::put($path,$stat);
		$fullPath=OC_Filesystem::getRoot().$path;
		$totalSize=0;
		if($dh){
			while (($filename = readdir($dh)) !== false) {
				if($filename != '.' and $filename != '..'){
					$file=$path.'/'.$filename;
					if(OC_Filesystem::is_dir($file)){
						self::scan($file,true,$eventSource);
					}else{
						$stat=OC_Filesystem::stat($file);
						$mimetype=OC_Filesystem::getMimeType($file);
						$stat['mimetype']=$mimetype;
						self::put($file,$stat);
						if($eventSource){
							$eventSource->send('scanned',$file);
						}
						$totalSize+=$stat['size'];
					}
				}
			}
		}
		self::increaseSize($fullPath,$totalSize);
	}

	/**
	 * fine files by mimetype
	 * @param string $part1
	 * @param string $part2 (optional)
	 * @return array of file paths
	 *
	 * $part1 and $part2 together form the complete mimetype.
	 * e.g. searchByMime('text','plain')
	 *
	 * seccond mimetype part can be ommited
	 * e.g. searchByMime('audio')
	 */
	public static function searchByMime($part1,$part2=''){
		if($part2){
			$query=OC_DB::prepare('SELECT path FROM *PREFIX*fscache WHERE mimepart=?');
			$result=$query->execute(array($part1));
		}else{
			$query=OC_DB::prepare('SELECT path FROM *PREFIX*fscache WHERE mimetype=?');
			$result=$query->execute(array($part1.'/'.$part2));
		}
		$names=array();
		while($row=$result->fetchRow()){
			$names[]=$row['path'];
		}
		return $names;
	}
}

//watch for changes and try to keep the cache up to date
OC_Hook::connect('OC_Filesystem','post_write','OC_FileCache','fileSystemWatcherWrite');
OC_Hook::connect('OC_Filesystem','delete','OC_FileCache','fileSystemWatcherDelete');
OC_Hook::connect('OC_Filesystem','rename','OC_FileCache','fileSystemWatcherRename');
