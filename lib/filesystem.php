<?php

/**
* ownCloud
*
* @author Frank Karlitschek 
* @copyright 2010 Frank Karlitschek karlitschek@kde.org 
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
 * Class for abstraction of filesystem functions
 * This class won't call any filesystem functions for itself but but will pass them to the correct OC_Filestorage object
 * this class should also handle all the file premission related stuff
 *
 * Hooks provided:
 *   read(path)
 *   write(path, &run)
 *   post_write(path)
 *   create(path, &run) (when a file is created, both create and write will be emited in that order)
 *   post_create(path)
 *   delete(path, &run)
 *   post_delete(path)
 *   rename(oldpath,newpath, &run)
 *   post_rename(oldpath,newpath)
 *   copy(oldpath,newpath, &run) (if the newpath doesn't exists yes, copy, create and write will be emited in that order)
 *   post_rename(oldpath,newpath)
 *
 *   the &run parameter can be set to false to prevent the operation from occuring
 */
class OC_Filesystem{
	static private $storages=array();
	static private $fakeRoot='';
	static private $storageTypes=array();
	
	
	/**
	* register a storage type
	* @param  string  type
	* @param  string  classname
	* @param  array  arguments     an associative array in the form of name=>type (eg array('datadir'=>'string'))
	*/
	static public function registerStorageType($type,$classname,$arguments){
		self::$storageTypes[$type]=array('type'=>$type,'classname'=>$classname,'arguments'=>$arguments);
	}
	
	/**
	* check if the filesystem supports a specific storagetype
	* @param  string  type
	* @return bool
	*/
	static public function hasStorageType($type){
		return isset(self::$storageTypes[$type]);
	}
	
	/**
	* get the list of names of storagetypes that the filesystem supports
	* @return array
	*/
	static public function getStorageTypeNames(){
		return array_keys(self::$storageTypes);
	}
	
	/**
	 * tear down the filesystem, removing all storage providers
	 */
	static public function tearDown(){
		foreach(self::$storages as $mountpoint=>$storage){
			unset(self::$storages[$mountpoint]);
		}
		$fakeRoot='';
	}
	
	/**
	* create a new storage of a specific type
	* @param  string  type
	* @param  array  arguments
	* @return OC_Filestorage
	*/
	static public function createStorage($type,$arguments){
		if(!self::hasStorageType($type)){
			return false;
		}
		$className=self::$storageTypes[$type]['classname'];
		if(class_exists($className)){
			return new $className($arguments);
		}else{
			return false;
		}
	}
	
	/**
	* change the root to a fake toor
	* @param  string  fakeRoot
	* @return bool
	*/
	static public function chroot($fakeRoot){
		if(!$fakeRoot==''){
			if($fakeRoot[0]!=='/'){
				$fakeRoot='/'.$fakeRoot;
			}
		}
		self::$fakeRoot=$fakeRoot;
	}
	
	/**
	* get the part of the path relative to the mountpoint of the storage it's stored in
	* @param  string  path
	* @return bool
	*/
	static public function getInternalPath($path){
		$mountPoint=self::getMountPoint($path);
		$path=self::$fakeRoot.$path;
		$internalPath=substr($path,strlen($mountPoint));
		return $internalPath;
	}
	
	/**
	* check if the current users has the right premissions to read a file
	* @param  string  path
	* @return bool
	*/
	static private function canRead($path){
		if(substr($path,0,1)!=='/'){
			$path='/'.$path;
		}
		if(strstr($path,'/../') || strrchr($path, '/') === '/..' ){
			return false;
		}
		return true;//dummy untill premissions are correctly implemented, also the correcty value because for now users are locked in their seperate data dir and can read/write everything in there
	}
	/**
	* check if the current users has the right premissions to write a file
	* @param  string  path
	* @return bool
	*/
	static private function canWrite($path){
		if(substr($path,0,1)!=='/'){
			$path='/'.$path;
		}
		if(strstr($path,'/../') || strrchr($path, '/') === '/..' ){
			return false;
		}
		return true;//dummy untill premissions are correctly implemented, also the correcty value because for now users are locked in their seperate data dir and can read/write everything in there
	}
	
	/**
	* mount an OC_Filestorage in our virtual filesystem
	* @param OC_Filestorage storage
	* @param string mountpoint
	*/
	static public function mount($storage,$mountpoint){
		if(substr($mountpoint,0,1)!=='/'){
			$mountpoint='/'.$mountpoint;
		}
		self::$storages[self::$fakeRoot.$mountpoint]=$storage;
	}
	
	/**
	* get the storage object for a path
	* @param string path
	* @return OC_Filestorage
	*/
	static public function getStorage($path){
		$mountpoint=self::getMountPoint($path);
		if($mountpoint){
			return self::$storages[$mountpoint];
		}
	}
	
	/**
	* get the mountpoint of the storage object for a path
	( note: because a storage is not always mounted inside the fakeroot, the returned mountpoint is relative to the absolute root of the filesystem and doesn't take the chroot into account
	*
	* @param string path
	* @return string
	*/
	static public function getMountPoint($path){
		if(!$path){
			$path='/';
		}
		if(substr($path,0,1)!=='/'){
			$path='/'.$path;
		}
		if(substr($path,-1)!=='/'){
			$path=$path.'/';
		}
		$path=self::$fakeRoot.$path;
		$foundMountPoint='';
		foreach(self::$storages as $mountpoint=>$storage){
			if(substr($mountpoint,-1)!=='/'){
				$mountpoint=$mountpoint.'/';
			}
			if($mountpoint==$path){
				return $mountpoint;
			}
			if(strpos($path,$mountpoint)===0 and strlen($mountpoint)>strlen($foundMountPoint)){
				$foundMountPoint=$mountpoint;
			}
		}
		return $foundMountPoint;
	}
	
	/**
	* return the path to a local version of the file
	* we need this because we can't know if a file is stored local or not from outside the filestorage and for some purposes a local file is needed
	* @param string path
	* @return string
	*/
	static public function getLocalFile($path){
		$parent=substr($path,0,strrpos($path,'/'));
		if(self::canRead($parent) and $storage=self::getStorage($path)){
			return $storage->getLocalFile(self::getInternalPath($path));
		}
	}
	
	static public function mkdir($path){
		$parent=substr($path,0,strrpos($path,'/'));
		if(self::canWrite($parent) and $storage=self::getStorage($path)){
			$run=true;
			OC_Hook::emit( 'OC_Filesystem', 'create', array( 'path' => $path, 'run' => &$run));
			if($run){
				OC_Hook::emit( 'OC_Filesystem', 'write', array( 'path' => $path, 'run' => &$run));
			}
			if($run){
				$result=$storage->mkdir(self::getInternalPath($path));
				OC_Hook::emit( 'OC_Filesystem', 'post_create', array( 'path' => $path));
				OC_Hook::emit( 'OC_Filesystem', 'post_write', array( 'path' => $path));
				return $result;
			}
		}
	}
	static public function rmdir($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$run=true;
			OC_Hook::emit( 'OC_Filesystem', 'delete', array( 'path' => $path, 'run' => &$run));
			if($run){
				$result=$storage->rmdir(self::getInternalPath($path));
				OC_Hook::emit( 'OC_Filesystem', 'post_delete', array( 'path' => $path));
				return $result;
			}
		}
	}
	static public function opendir($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
			return $storage->opendir(self::getInternalPath($path));
		}
	}
	static public function is_dir($path){
		if($path=='/'){
			return true;
		}
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->is_dir(self::getInternalPath($path));
		}
	}
	static public function is_file($path){
		if($path=='/'){
			return false;
		}
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->is_file(self::getInternalPath($path));
		}
	}
	static public function stat($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->stat(self::getInternalPath($path));
		}
	}
	static public function filetype($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->filetype(self::getInternalPath($path));
		}
	}
	static public function filesize($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->filesize(self::getInternalPath($path));
		}
	}
	static public function readfile($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
			return $storage->readfile(self::getInternalPath($path));
		}
	}
	static public function is_readable($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->is_readable(self::getInternalPath($path));
		}
		return false;
	}
	static public function is_writeable($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->is_writeable(self::getInternalPath($path));
		}
		return false;
	}
	static public function file_exists($path){
		if($path=='/'){
			return true;
		}
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->file_exists(self::getInternalPath($path));
		}
		return false;
	}
	static public function filectime($path){
		if($storage=self::getStorage($path)){
			return $storage->filectime(self::getInternalPath($path));
		}
	}
	static public function filemtime($path){
		if($storage=self::getStorage($path)){
			return $storage->filemtime(self::getInternalPath($path));
		}
	}
	static public function fileatime($path){
		if($storage=self::getStorage($path)){
			return $storage->fileatime(self::getInternalPath($path));
		}
	}
	static public function file_get_contents($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
			return $storage->file_get_contents(self::getInternalPath($path));
		}
	}
	static public function file_put_contents($path,$data){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$run=true;
			$exists=self::file_exists($path);
			if(!$exists){
				OC_Hook::emit( 'OC_Filesystem', 'create', array( 'path' => $path, 'run' => &$run));
			}
			if($run){
				OC_Hook::emit( 'OC_Filesystem', 'write', array( 'path' => $path, 'run' => &$run));
			}
			if($run){
				$result=$storage->file_put_contents(self::getInternalPath($path),$data);
				if(!$exists){
					OC_Hook::emit( 'OC_Filesystem', 'post_create', array( 'path' => $path));
				}
				OC_Hook::emit( 'OC_Filesystem', 'post_write', array( 'path' => $path));
				return $result;
			}
		}
	}
	static public function unlink($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$run=true;
			OC_Hook::emit( 'OC_Filesystem', 'delete', array( 'path' => $path, 'run' => &$run));
			if($run){
				$result=$storage->unlink(self::getInternalPath($path));
				OC_Hook::emit( 'OC_Filesystem', 'post_delete', array( 'path' => $path));
				return $result;
			}
		}
	}
	static public function rename($path1,$path2){
		if(self::canWrite($path1) and self::canWrite($path2)){
			$run=true;
			OC_Hook::emit( 'OC_Filesystem', 'rename', array( 'oldpath' => $path1 ,'newpath'=>$path2, 'run' => &$run));
			if($run){
				$mp1=self::getMountPoint($path1);
				$mp2=self::getMountPoint($path2);
				if($mp1==$mp2){
					if($storage=self::getStorage($path1)){
						$result=$storage->rename(self::getInternalPath($path1),self::getInternalPath($path2));
					}
				}elseif($storage1=self::getStorage($path1) and $storage2=self::getStorage($path2)){
					$tmpFile=$storage1->toTmpFile(self::getInternalPath($path1));
					$result=$storage2->fromTmpFile($tmpFile,self::getInternalPath($path2));
					$storage1->unlink(self::getInternalPath($path1));
				}
				OC_Hook::emit( 'OC_Filesystem', 'post_rename', array( 'oldpath' => $path1, 'newpath'=>$path2));
				return $result;
			}
		}
	}
	static public function copy($path1,$path2){
		if(self::canRead($path1) and self::canWrite($path2)){
			$run=true;
			OC_Hook::emit( 'OC_Filesystem', 'copy', array( 'oldpath' => $path1 ,'newpath'=>$path2, 'run' => &$run));
			$exists=self::file_exists($path2);
			if($run and !$exists){
				OC_Hook::emit( 'OC_Filesystem', 'create', array( 'path' => $path2, 'run' => &$run));
			}
			if($run){
				OC_Hook::emit( 'OC_Filesystem', 'write', array( 'path' => $path2, 'run' => &$run));
			}
			if($run){
				$mp1=self::getMountPoint($path1);
				$mp2=self::getMountPoint($path2);
				if($mp1==$mp2){
					if($storage=self::getStorage($path1)){
						$result=$storage->copy(self::getInternalPath($path1),self::getInternalPath($path2));
					}
				}elseif($storage1=self::getStorage($path1) and $storage2=self::getStorage($path2)){
					$tmpFile=$storage1->toTmpFile(self::getInternalPath($path1));
					$result=$storage2->fromTmpFile($tmpFile,self::getInternalPath($path2));
				}
				OC_Hook::emit( 'OC_Filesystem', 'post_copy', array( 'oldpath' => $path1 ,'newpath'=>$path2));
				if(!$exists){
					OC_Hook::emit( 'OC_Filesystem', 'post_create', array( 'path' => $path2));
				}
				OC_Hook::emit( 'OC_Filesystem', 'post_write', array( 'path' => $path2));
				return $result;
			}
		}
	}
	static public function fopen($path,$mode){
		$allowed=((strpos($path,'r')===false and strpos($path,'r+')!==false and self::canRead) or self::canWrite($path));
		if($allowed){
			if($storage=self::getStorage($path)){
				$run=true;
				$exists=self::file_exists($path);
				$write=false;
				switch($mode){
					case 'r':
						OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
						break;
					case 'r+':
					case 'w+':
					case 'x+':
					case 'a+':
						OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
						$write=true;
						break;
					case 'w':
					case 'x':
					case 'a':
						$write=true;
						break;
				}
				if($write){
					if(!$exists){
						OC_Hook::emit( 'OC_Filesystem', 'create', array( 'path' => $path));
					}
					if($run){
						OC_Hook::emit( 'OC_Filesystem', 'write', array( 'path' => $path, 'run' => &$run));
					}
				}
				if($run){
					$result=$storage->fopen(self::getInternalPath($path),$mode);
					if($write){
						if(!$exists){
							OC_Hook::emit( 'OC_Filesystem', 'post_create', array( 'path' => $path));
						}
						if($run){
							OC_Hook::emit( 'OC_Filesystem', 'post_write', array( 'path' => $path));
						}
					}
					return $result;
				}
			}
		}
	}
	static public function toTmpFile($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
			return $storage->toTmpFile(self::getInternalPath($path));
		}
	}
	static public function fromTmpFile($tmpFile,$path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$run=true;
			$exists=self::file_exists($path);
			if(!$exists){
				OC_Hook::emit( 'OC_Filesystem', 'create', array( 'path' => $path, 'run' => &$run));
			}
			if($run){
				OC_Hook::emit( 'OC_Filesystem', 'write', array( 'path' => $path, 'run' => &$run));
			}
			if($run){
				$result=$storage->fromTmpFile($tmpFile,self::getInternalPath($path));
				if(!$exists){
					OC_Hook::emit( 'OC_Filesystem', 'post_create', array( 'path' => $path));
				}
				OC_Hook::emit( 'OC_Filesystem', 'post_write', array( 'path' => $path));
				return $result;
			}
		}
	}
	static public function fromUploadedFile($tmpFile,$path){
		error_log('upload');
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$run=true;
			$exists=self::file_exists($path);
			if(!$exists){
				OC_Hook::emit( 'OC_Filesystem', 'create', array( 'path' => $path, 'run' => &$run));
			}
			if($run){
				OC_Hook::emit( 'OC_Filesystem', 'write', array( 'path' => $path, 'run' => &$run));
			}
			error_log('upload2');
			if($run){
				$result=$storage->fromUploadedFile($tmpFile,self::getInternalPath($path));
				if(!$exists){
					OC_Hook::emit( 'OC_Filesystem', 'post_create', array( 'path' => $path));
				}
				OC_Hook::emit( 'OC_Filesystem', 'post_write', array( 'path' => $path));
				return $result;
			}
		}
	}
	static public function getMimeType($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->getMimeType(self::getInternalPath($path));
		}
	}
	static public function delTree($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$run=true;
			OC_Hook::emit( 'OC_Filesystem', 'delete', array( 'path' => $path, 'run' => &$run));
			if($run){
				return $storage->delTree(self::getInternalPath($path));
			}
		}
	}
	static public function find($path){
		if($storage=self::getStorage($path)){
			$mp=self::getMountPoint($path);
			$return=$storage->find(self::getInternalPath($path));
			foreach($return as &$file){
				$file=$mp.$file;
			}
		}
		return $return;
	}
	static public function getTree($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			$mp=self::getMountPoint($path);
			$return=$storage->getTree(self::getInternalPath($path));
			foreach($return as &$file){
				if(substr($file,0,1)=='/'){
					$file=substr($file,1);
				}
				$file=$mp.$file;
				$file=substr($file,strlen(self::$fakeRoot));
				if($file === '' || $file === false){
					$file = '/';
				}
			}
			return $return;
		}
	}
	static public function hash($type,$path,$raw=false){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			OC_Hook::emit( 'OC_Filesystem', 'read', array( 'path' => $path));
			return $storage->hash($type,self::getInternalPath($path),$raw);
		}
	}
	
	static public function free_space($path='/'){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->free_space($path);
		}
	}
	
	static public function search($query){
		$files=array();
		$fakeRootLength=strlen(self::$fakeRoot);
		foreach(self::$storages as $mountpoint=>$storage){
			$results=$storage->search($query);
			if(is_array($results)){
				foreach($results as $result){
					$file=str_replace('//','/',$mountpoint.$result);
					$file=substr($file,$fakeRootLength);
					$files[]=$file;
				}
			}
		}
		return $files;
		
	}
}
