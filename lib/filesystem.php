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
	static private $mounts=array();
	static private $fakeRoot='';
	static private $storageTypes=array();

  /**
   * classname which used for hooks handling
   * used as signalclass in OC_Hooks::emit()
   */
  const CLASSNAME = 'OC_Filesystem';

  /**
   * signalname emited before file renaming
   * @param oldpath
   * @param newpath
   */
  const signal_rename = 'rename';

  /**
   * signal emited after file renaming
   * @param oldpath
   * @param newpath
   */
  const signal_post_rename = 'post_rename';
	
  /**
   * signal emited before file/dir creation
   * @param path
   * @param run changing this flag to false in hook handler will cancel event
   */
  const signal_create = 'create';

  /**
   * signal emited after file/dir creation
   * @param path
   * @param run changing this flag to false in hook handler will cancel event
   */
  const signal_post_create = 'post_create';

  /**
   * signal emits before file/dir copy
   * @param oldpath
   * @param newpath
   * @param run changing this flag to false in hook handler will cancel event
   */
  const signal_copy = 'copy';

  /**
   * signal emits after file/dir copy
   * @param oldpath
   * @param newpath
   */
  const signal_post_copy = 'post_copy';

  /**
   * signal emits before file/dir save
   * @param path
   * @param run changing this flag to false in hook handler will cancel event
   */
  const signal_write = 'write';

  /**
   * signal emits after file/dir save
   * @param path
   */
  const signal_post_write = 'post_write';
	
  /**
   * signal emits when reading file/dir
   * @param path
   */
  const signal_read = 'read';

  /**
   * signal emits when removing file/dir
   * @param path
   */
  const signal_delete = 'delete';

  /**
   * parameters definitions for signals
   */
  const signal_param_path = 'path';
  const signal_param_oldpath = 'oldpath';
  const signal_param_newpath = 'newpath';

  /**
   * run - changing this flag to false in hook handler will cancel event
   */
  const signal_param_run = 'run';

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
	static private function createStorage($type,$arguments){
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
	* mount an OC_Filestorage in our virtual filesystem
	* @param OC_Filestorage storage
	* @param string mountpoint
	*/
	static public function mount($type,$arguments,$mountpoint){
		if(substr($mountpoint,0,1)!=='/'){
			$mountpoint='/'.$mountpoint;
		}
		self::$mounts[$mountpoint]=array('type'=>$type,'arguments'=>$arguments);
	}

	/**
	 * create all storage backends mounted in the filesystem
	 */
	static private function mountAll(){
		foreach(self::$mounts as $mountPoint=>$mount){
			if(!isset(self::$storages[$mountPoint])){
				self::$storages[$mountPoint]=self::createStorage($mount['type'],$mount['arguments']);
			}
		}
	}
	
	/**
	* get the storage object for a path
	* @param string path
	* @return OC_Filestorage
	*/
	static public function getStorage($path){
		$mountpoint=self::getMountPoint($path);
		if($mountpoint){
			if(!isset(self::$storages[$mountpoint])){
				$mount=self::$mounts[$mountpoint];
				self::$storages[$mountpoint]=self::createStorage($mount['type'],$mount['arguments']);
			}
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
		foreach(self::$mounts as $mountpoint=>$storage){
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
		if(self::isValidPath($parent) and $storage=self::getStorage($path)){
			return $storage->getLocalFile(self::getInternalPath($path));
		}
	}
	
	/**
	 * check if the requested path is valid
	 * @param string path
	 * @return bool
	 */
	static public function isValidPath($path){
		if(substr($path,0,1)!=='/'){
			$path='/'.$path;
		}
		if(strstr($path,'/../') || strrchr($path, '/') === '/..' ){
			return false;
		}
		return true;
	}
	
	static public function mkdir($path){
		return self::basicOperation('mkdir',$path,array('create','write'));
	}
	static public function rmdir($path){
		return self::basicOperation('rmdir',$path,array('delete'));
	}
	static public function opendir($path){
		return self::basicOperation('opendir',$path,array('read'));
	}
	static public function is_dir($path){
		if($path=='/'){
			return true;
		}
		return self::basicOperation('is_dir',$path);
	}
	static public function is_file($path){
		if($path=='/'){
			return false;
		}
		return self::basicOperation('is_file',$path);
	}
	static public function stat($path){
		return self::basicOperation('stat',$path);
	}
	static public function filetype($path){
		return self::basicOperation('filetype',$path);
	}
	static public function filesize($path){
		return self::basicOperation('filesize',$path);
	}
	static public function readfile($path){
		return self::basicOperation('readfile',$path,array('read'));
	}
	static public function is_readable($path){
		return self::basicOperation('is_readable',$path);
	}
	static public function is_writeable($path){
		return self::basicOperation('is_writeable',$path);
	}
	static public function file_exists($path){
		if($path=='/'){
			return true;
		}
		return self::basicOperation('file_exists',$path);
	}
	static public function filectime($path){
		return self::basicOperation('filectime',$path);
	}
	static public function filemtime($path){
		return self::basicOperation('filemtime',$path);
	}
	static public function fileatime($path){
		return self::basicOperation('fileatime',$path);
	}
	static public function touch($path, $mtime=null){
		return self::basicOperation('touch',$path,array(),$mtime);
	}
	static public function file_get_contents($path){
		return self::basicOperation('file_get_contents',$path,array('read'));
	}
	static public function file_put_contents($path,$data){
		return self::basicOperation('file_put_contents',$path,array('create','write'),$data);
	}
	static public function unlink($path){
		return self::basicOperation('unlink',$path,array('delete'));
	}
	static public function rename($path1,$path2){
		if(OC_FileProxy::runPreProxies('rename',$path1,$path2) and self::is_writeable($path1) and self::isValidPath($path2)){
			$run=true;
      OC_Hook::emit( self::CLASSNAME, self::signal_rename, array( self::signal_param_oldpath => $path1 , self::signal_param_newpath=>$path2, self::signal_param_run => &$run));
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
        OC_Hook::emit( self::CLASSNAME, self::signal_post_rename, array( self::signal_param_oldpath => $path1, self::signal_param_newpath=>$path2));
				return $result;
			}
		}
	}
	static public function copy($path1,$path2){
		if(OC_FileProxy::runPreProxies('copy',$path1,$path2) and self::is_readable($path1) and self::isValidPath($path2)){
			$run=true;
      OC_Hook::emit( self::CLASSNAME, self::signal_copy, array( self::signal_param_oldpath => $path1 , self::signal_param_newpath=>$path2, self::signal_param_run => &$run));
			$exists=self::file_exists($path2);
			if($run and !$exists){
        OC_Hook::emit( self::CLASSNAME, self::signal_create, array( self::signal_param_path => $path2, self::signal_param_run => &$run));
			}
			if($run){
        OC_Hook::emit( self::CLASSNAME, self::signal_write, array( self::signal_param_path => $path2, self::signal_param_run => &$run));
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
        OC_Hook::emit( self::CLASSNAME, self::signal_post_copy, array( self::signal_param_oldpath => $path1 , self::signal_param_newpath=>$path2));
				if(!$exists){
          OC_Hook::emit( self::CLASSNAME, self::signal_post_create, array( self::signal_param_path => $path2));
				}
        OC_Hook::emit( self::CLASSNAME, self::signal_post_write, array( self::signal_param_path => $path2));
				return $result;
			}
		}
	}
	static public function fopen($path,$mode){
		$hooks=array();
		switch($mode){
			case 'r':
				$hooks[]='read';
				break;
			case 'r+':
			case 'w+':
			case 'x+':
			case 'a+':
				$hooks[]='read';
				$hooks[]='write';
				break;
			case 'w':
			case 'x':
			case 'a':
				$hooks[]='write';
				break;
			default:
				OC_Log::write('core','invalid mode ('.$mode.') for '.$path,OC_Log::ERROR);
		}
		
		return self::basicOperation('fopen',$path,$hooks,$mode);
	}
	static public function toTmpFile($path){
		if(OC_FileProxy::runPreProxies('toTmpFile',$path) and self::isValidPath($path) and $storage=self::getStorage($path)){
      OC_Hook::emit( self::CLASSNAME, self::signal_read, array( self::signal_param_path => $path));
			return $storage->toTmpFile(self::getInternalPath($path));
		}
	}
	static public function fromTmpFile($tmpFile,$path){
		if(OC_FileProxy::runPreProxies('copy',$tmpFile,$path) and self::isValidPath($path) and $storage=self::getStorage($path)){
			$run=true;
			$exists=self::file_exists($path);
			if(!$exists){
        OC_Hook::emit( self::CLASSNAME, self::signal_create, array( self::signal_param_path => $path, self::signal_param_run => &$run));
			}
			if($run){
        OC_Hook::emit( self::CLASSNAME, self::signal_write, array( self::signal_param_path => $path, self::signal_param_run => &$run));
			}
			if($run){
				$result=$storage->fromTmpFile($tmpFile,self::getInternalPath($path));
				if(!$exists){
          OC_Hook::emit( self::CLASSNAME, self::signal_post_create, array( self::signal_param_path => $path));
				}
        OC_Hook::emit( self::CLASSNAME, self::signal_post_write, array( self::signal_param_path => $path));
				return $result;
			}
		}
	}
	static public function fromUploadedFile($tmpFile,$path){
		if(OC_FileProxy::runPreProxies('fromUploadedFile',$tmpFile,$path) and self::isValidPath($path) and $storage=self::getStorage($path)){
			$run=true;
			$exists=self::file_exists($path);
			if(!$exists){
        OC_Hook::emit( self::CLASSNAME, self::signal_create, array( self::signal_param_path => $path, self::signal_param_run => &$run));
			}
			if($run){
        OC_Hook::emit( self::CLASSNAME, self::signal_write, array( self::signal_param_path => $path, self::signal_param_run => &$run));
			}
			if($run){
				$result=$storage->fromUploadedFile($tmpFile,self::getInternalPath($path));
				if(!$exists){
          OC_Hook::emit( self::CLASSNAME, self::signal_post_create, array( self::signal_param_path => $path));
				}
        OC_Hook::emit( self::CLASSNAME, self::signal_post_write, array( self::signal_param_path => $path));
				return $result;
			}
		}
	}
	static public function getMimeType($path){
		return self::basicOperation('getMimeType',$path);
	}
	static public function hash($type,$path){
		return self::basicOperation('hash',$path,array('read'));
	}
	
	static public function free_space($path='/'){
		return self::basicOperation('free_space',$path);
	}
	
	static public function search($query){
		self::mountAll();
		$files=array();
		$fakeRoot=self::$fakeRoot;
		$fakeRootLength=strlen($fakeRoot);
		foreach(self::$storages as $mountpoint=>$storage){
			$results=$storage->search($query);
			if(is_array($results)){
				foreach($results as $result){
					$file=str_replace('//','/',$mountpoint.$result);
					if(substr($file,0,$fakeRootLength)==$fakeRoot){
						$file=substr($file,$fakeRootLength);
						$files[]=$file;
					}
				}
			}
		}
		return $files;
		
	}
	
	static public function update_session_file_hash($sessionname,$sessionvalue){
		$_SESSION[$sessionname] = $sessionvalue;
	}

	/**
	 * abstraction for running most basic operations
	 * @param string $operation
	 * @param string #path
	 * @param array (optional) hooks
	 * @param mixed (optional) $extraParam
	 * @return mixed
	 */
	private static function basicOperation($operation,$path,$hooks=array(),$extraParam=null){
		if(OC_FileProxy::runPreProxies($operation,$path, $extraParam) and self::isValidPath($path) and $storage=self::getStorage($path)){
			$interalPath=self::getInternalPath($path);
			$run=true;
			foreach($hooks as $hook){
				if($hook!='read'){
          OC_Hook::emit( self::CLASSNAME, $hook, array( self::signal_param_path => $path, self::signal_param_run => &$run));
				}else{
          OC_Hook::emit( self::CLASSNAME, $hook, array( self::signal_param_path => $path));
				}
			}
			if($run){
				if($extraParam){
					$result=$storage->$operation($interalPath,$extraParam);
				}else{
					$result=$storage->$operation($interalPath);
				}
				$result=OC_FileProxy::runPostProxies($operation,$path,$result);
				foreach($hooks as $hook){
					if($hook!='read'){
            OC_Hook::emit( self::CLASSNAME, 'post_'.$hook, array( self::signal_param_path => $path));
					}
				}
				return $result;
			}
		}
		return null;
	}
}
