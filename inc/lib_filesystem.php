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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/


/**
 * Class for abstraction of filesystem functions
 * This class won't call any filesystem functions for itself but but will pass them to the correct OC_FILESTORAGE object
 * this class should also handle all the file premission related stuff
 */
class OC_FILESYSTEM{
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
	* create a new storage of a specific type
	* @param  string  type
	* @param  array  arguments
	* @return OC_FILESTORAGE
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
		if($fakeRoot[0]!=='/'){
			$fakeRoot='/'.$fakeRoot;
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
		if(strstr($path,'/../')){
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
		if(strstr($path,'/../')){
			return false;
		}
		return true;//dummy untill premissions are correctly implemented, also the correcty value because for now users are locked in their seperate data dir and can read/write everything in there
	}
	
	/**
	* mount an OC_FILESTORAGE in our virtual filesystem
	* @param OC_FILESTORAGE storage
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
	* @return OC_FILESTORAGE
	*/
	static private function getStorage($path){
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
	static private function getMountPoint($path){
		if(!$path){
			$path='/';
		}
		if(substr($path,0,1)!=='/'){
			$path='/'.$path;
		}
		$path=self::$fakeRoot.$path;
		$foundMountPoint='';
		foreach(self::$storages as $mountpoint=>$storage){
			if($mountpoint==$path){
				return $mountpoint;
			}
			if(strpos($path,$mountpoint)===0 and strlen($mountpoint)>strlen($foundMountPoint)){
				$foundMountPoint=$mountpoint;
			}
		}
		return $foundMountPoint;
	}
	
	static public function mkdir($path){
		$parent=substr($path,0,strrpos($path,'/'));
		if(self::canWrite($parent) and $storage=self::getStorage($path)){
			return $storage->mkdir(self::getInternalPath($path));
		}
	}
	static public function rmdir($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->rmdir(self::getInternalPath($path));
		}
	}
	static public function opendir($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
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
			return $storage->file_get_contents(self::getInternalPath($path));
		}
	}
	static public function file_put_contents($path,$data){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			$this->notifyObservers($path,OC_FILEACTION_WRITE | OC_FILEACTION_CREATE);
			return $storage->file_put_contents(self::getInternalPath($path));
		}
	}
	static public function unlink($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->unlink(self::getInternalPath($path));
		}
	}
	static public function rename($path1,$path2){
		if(self::canWrite($path1) and self::canWrite($path2)){
			$mp1=self::getMountPoint($path1);
			$mp2=self::getMountPoint($path2);
			if($mp1==$mp2){
				if($storage=self::getStorage($path1)){
					return $storage->rename(self::getInternalPath($path1),self::getInternalPath($path2));
				}
			}elseif($storage1=self::getStorage($path1) and $storage2=self::getStorage($path2)){
				$tmpFile=$storage1->toTmpFile(self::getInternalPath($path1));
				$result=$storage2->fromTmpFile(self::getInternalPath($path2));
				$storage1->unlink(self::getInternalPath($path1));
				return $result;
			}
		}
	}
	static public function copy($path1,$path2){
		if(self::canRead($path1) and self::canWrite($path2)){
			$mp1=self::getMountPoint($path1);
			$mp2=self::getMountPoint($path2);
			if($mp1==$mp2){
				if($storage=self::getStorage($path1)){
					return $storage->copy(self::getInternalPath($path1),self::getInternalPath($path2));
				}
			}elseif($storage1=self::getStorage($path1) and $storage2=self::getStorage($path2)){
				$tmpFile=$storage1->toTmpFile(self::getInternalPath($path1));
				return $storage2->fromTmpFile(self::getInternalPath($path2));
			}
		}
	}
	static public function fopen($path,$mode){
		$allowed=((strpos($path,'r')===false and strpos($path,'r+')!==false and self::canRead) or self::canWrite($path));
		if($allowed){
			if($storage=self::getStorage($path)){
				return $storage->fopen(self::getInternalPath($path),$mode);
			}
		}
	}
	static public function toTmpFile($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->toTmpFile(self::getInternalPath($path));
		}
	}
	static public function fromTmpFile($tmpFile,$path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->fromTmpFile($tmpFile,self::getInternalPath($path));
		}
	}
	static public function getMimeType($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->getMimeType(self::getInternalPath($path));
		}
	}
	static public function delTree($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->delTree(self::getInternalPath($path));
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
			}
			return $return;
		}
	}
}
?>
