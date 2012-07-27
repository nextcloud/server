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

class OC_FilesystemView {
	private $fakeRoot='';
	private $internal_path_cache=array();
	private $storage_cache=array();

	public function __construct($root){
		$this->fakeRoot=$root;
	}

	public function getAbsolutePath($path){
		if(!$path){
			$path='/';
		}
		if($path[0]!=='/'){
			$path='/'.$path;
		}
		return $this->fakeRoot.$path;
	}


	/**
	* change the root to a fake toor
	* @param  string  fakeRoot
	* @return bool
	*/
	public function chroot($fakeRoot){
		if(!$fakeRoot==''){
			if($fakeRoot[0]!=='/'){
				$fakeRoot='/'.$fakeRoot;
			}
		}
		$this->fakeRoot=$fakeRoot;
	}

	/**
	 * get the fake root
	 * @return string
	 */
	public function getRoot(){
		return $this->fakeRoot;
	}

	/**
	* get the part of the path relative to the mountpoint of the storage it's stored in
	* @param  string  path
	* @return bool
	*/
	public function getInternalPath($path){
		if (!isset($this->internal_path_cache[$path])) {
			$this->internal_path_cache[$path] = OC_Filesystem::getInternalPath($this->getAbsolutePath($path));
		}
		return $this->internal_path_cache[$path];
	}

	/**
	 * get path relative to the root of the view
	 * @param string path
	 * @return string
	 */
	public function getRelativePath($path){
		if($this->fakeRoot==''){
			return $path;
		}
		if(strpos($path,$this->fakeRoot)!==0){
			return null;
		}else{
			return substr($path,strlen($this->fakeRoot));
		}
	}
	
	/**
	* get the storage object for a path
	* @param string path
	* @return OC_Filestorage
	*/
	public function getStorage($path){
		if (!isset($this->storage_cache[$path])) {
			$this->storage_cache[$path] = OC_Filesystem::getStorage($this->getAbsolutePath($path));
		}
		return $this->storage_cache[$path];
	}

	/**
	* get the mountpoint of the storage object for a path
	( note: because a storage is not always mounted inside the fakeroot, the returned mountpoint is relative to the absolute root of the filesystem and doesn't take the chroot into account
	*
	* @param string path
	* @return string
	*/
	public function getMountPoint($path){
		return OC_Filesystem::getMountPoint($this->getAbsolutePath($path));
	}

	/**
	* return the path to a local version of the file
	* we need this because we can't know if a file is stored local or not from outside the filestorage and for some purposes a local file is needed
	* @param string path
	* @return string
	*/
	public function getLocalFile($path){
		$parent=substr($path,0,strrpos($path,'/'));
		if(OC_Filesystem::isValidPath($parent) and $storage=$this->getStorage($path)){
			return $storage->getLocalFile($this->getInternalPath($path));
		}
	}

	/**
	 * following functions are equivilent to their php buildin equivilents for arguments/return values.
	 */
	public function mkdir($path){
		return $this->basicOperation('mkdir',$path,array('create','write'));
	}
	public function rmdir($path){
		return $this->basicOperation('rmdir',$path,array('delete'));
	}
	public function opendir($path){
		return $this->basicOperation('opendir',$path,array('read'));
	}
	public function is_dir($path){
		if($path=='/'){
			return true;
		}
		return $this->basicOperation('is_dir',$path);
	}
	public function is_file($path){
		if($path=='/'){
			return false;
		}
		return $this->basicOperation('is_file',$path);
	}
	public function stat($path){
		return $this->basicOperation('stat',$path);
	}
	public function filetype($path){
		return $this->basicOperation('filetype',$path);
	}
	public function filesize($path){
		return $this->basicOperation('filesize',$path);
	}
	public function readfile($path){
		@ob_end_clean();
		$handle=$this->fopen($path,'rb');
		if ($handle) {
			$chunkSize = 8192;// 8 MB chunks
			while (!feof($handle)) {
				echo fread($handle, $chunkSize);
				flush();
			}
			$size=$this->filesize($path);
			return $size;
		}
		return false;
	}
	public function is_readable($path){
		return $this->basicOperation('is_readable',$path);
	}
	public function is_writable($path){
		return $this->basicOperation('is_writable',$path);
	}
	public function file_exists($path){
		if($path=='/'){
			return true;
		}
		return $this->basicOperation('file_exists',$path);
	}
	public function filectime($path){
		return $this->basicOperation('filectime',$path);
	}
	public function filemtime($path){
		return $this->basicOperation('filemtime',$path);
	}
	public function touch($path, $mtime=null){
		return $this->basicOperation('touch', $path, array('write'), $mtime);
	}
	public function file_get_contents($path){
		return $this->basicOperation('file_get_contents',$path,array('read'));
	}
	public function file_put_contents($path, $data) {
		if(is_resource($data)) {//not having to deal with streams in file_put_contents makes life easier
			$absolutePath = $this->getAbsolutePath($path);
			if (OC_FileProxy::runPreProxies('file_put_contents', $absolutePath, $data) && OC_Filesystem::isValidPath($path)) {
				$path = $this->getRelativePath($absolutePath);
				$exists = $this->file_exists($path);
				$run = true;
				if(!$exists) {
					OC_Hook::emit(
						OC_Filesystem::CLASSNAME,
						OC_Filesystem::signal_create,
						array(
							OC_Filesystem::signal_param_path => $path,
							OC_Filesystem::signal_param_run => &$run
						)
					);
				}
				OC_Hook::emit(
					OC_Filesystem::CLASSNAME,
					OC_Filesystem::signal_write,
					array(
						OC_Filesystem::signal_param_path => $path,
						OC_Filesystem::signal_param_run => &$run
					)
				);
				if(!$run) {
					return false;
				}
				$target=$this->fopen($path, 'w');
				if($target) {
					$count=OC_Helper::streamCopy($data, $target);
					fclose($target);
					fclose($data);
					if(!$exists) {
						OC_Hook::emit(
							OC_Filesystem::CLASSNAME,
							OC_Filesystem::signal_post_create,
							array( OC_Filesystem::signal_param_path => $path)
						);
					}
					OC_Hook::emit(
						OC_Filesystem::CLASSNAME,
						OC_Filesystem::signal_post_write,
						array( OC_Filesystem::signal_param_path => $path)
					);
					OC_FileProxy::runPostProxies('file_put_contents', $absolutePath, $count);
					return $count > 0;
				}else{
					return false;
				}
			}
		}else{
			return $this->basicOperation('file_put_contents',$path,array('create','write'),$data);
		}
	}
	public function unlink($path){
		return $this->basicOperation('unlink',$path,array('delete'));
	}
	public function rename($path1,$path2){
		$absolutePath1=$this->getAbsolutePath($path1);
		$absolutePath2=$this->getAbsolutePath($path2);
		if(OC_FileProxy::runPreProxies('rename',$absolutePath1,$absolutePath2) and OC_Filesystem::isValidPath($path2)){
			$path1=$this->getRelativePath($absolutePath1);
			$path2=$this->getRelativePath($absolutePath2);
			if($path1==null or $path2==null){
				return false;
			}
			$run=true;
			OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_rename, array( OC_Filesystem::signal_param_oldpath => $path1 , OC_Filesystem::signal_param_newpath=>$path2, OC_Filesystem::signal_param_run => &$run));
			if($run){
				$mp1=$this->getMountPoint($path1);
				$mp2=$this->getMountPoint($path2);
				if($mp1==$mp2){
					if($storage=$this->getStorage($path1)){
						$result=$storage->rename($this->getInternalPath($path1),$this->getInternalPath($path2));
					}
				}else{
					$source=$this->fopen($path1,'r');
					$target=$this->fopen($path2,'w');
					$count=OC_Helper::streamCopy($data,$target);
					$storage1=$this->getStorage($path1);
					$storage1->unlink($this->getInternalPath($path1));
					$result=$count>0;
				}
				OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_rename, array( OC_Filesystem::signal_param_oldpath => $path1, OC_Filesystem::signal_param_newpath=>$path2));
				return $result;
			}
		}
	}
	public function copy($path1,$path2){
		$absolutePath1=$this->getAbsolutePath($path1);
		$absolutePath2=$this->getAbsolutePath($path2);
		if(OC_FileProxy::runPreProxies('copy',$absolutePath1,$absolutePath2) and OC_Filesystem::isValidPath($path2)){
			$path1=$this->getRelativePath($absolutePath1);
			$path2=$this->getRelativePath($absolutePath2);
			if($path1==null or $path2==null){
				return false;
			}
			$run=true;
			OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_copy, array( OC_Filesystem::signal_param_oldpath => $path1 , OC_Filesystem::signal_param_newpath=>$path2, OC_Filesystem::signal_param_run => &$run));
			$exists=$this->file_exists($path2);
			if($run and !$exists){
				OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_create, array( OC_Filesystem::signal_param_path => $path2, OC_Filesystem::signal_param_run => &$run));
			}
			if($run){
				OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_write, array( OC_Filesystem::signal_param_path => $path2, OC_Filesystem::signal_param_run => &$run));
			}
			if($run){
				$mp1=$this->getMountPoint($path1);
				$mp2=$this->getMountPoint($path2);
				if($mp1==$mp2){
					if($storage=$this->getStorage($path1)){
						$result=$storage->copy($this->getInternalPath($path1),$this->getInternalPath($path2));
					}
				}else{
					$source=$this->fopen($path1,'r');
					$target=$this->fopen($path2,'w');
					$count=OC_Helper::streamCopy($data,$target);
				}
        OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_copy, array( OC_Filesystem::signal_param_oldpath => $path1 , OC_Filesystem::signal_param_newpath=>$path2));
				if(!$exists){
          OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_create, array( OC_Filesystem::signal_param_path => $path2));
				}
        OC_Hook::emit( OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_write, array( OC_Filesystem::signal_param_path => $path2));
				return $result;
			}
		}
	}
	public function fopen($path,$mode){
		$hooks=array();
		switch($mode){
			case 'r':
			case 'rb':
				$hooks[]='read';
				break;
			case 'r+':
			case 'rb+':
			case 'w+':
			case 'wb+':
			case 'x+':
			case 'xb+':
			case 'a+':
			case 'ab+':
				$hooks[]='read';
				$hooks[]='write';
				break;
			case 'w':
			case 'wb':
			case 'x':
			case 'xb':
			case 'a':
			case 'ab':
				$hooks[]='write';
				break;
			default:
				OC_Log::write('core','invalid mode ('.$mode.') for '.$path,OC_Log::ERROR);
		}

		return $this->basicOperation('fopen',$path,$hooks,$mode);
	}
	public function toTmpFile($path){
		if(OC_Filesystem::isValidPath($path)){
			$source=$this->fopen($path,'r');
			if($source){
				$extension='';
				$extOffset=strpos($path,'.');
				if($extOffset !== false) {
					$extension=substr($path,strrpos($path,'.'));
				}
				$tmpFile=OC_Helper::tmpFile($extension);
				file_put_contents($tmpFile,$source);
				return $tmpFile;
			}
		}
	}
	public function fromTmpFile($tmpFile,$path){
		if(OC_Filesystem::isValidPath($path)){
			if(!$tmpFile){
				debug_print_backtrace();
			}
			$source=fopen($tmpFile,'r');
			if($source){
				$this->file_put_contents($path,$source);
				unlink($tmpFile);
				return true;
			}else{
			}
		}else{
			return false;
		}
	}

	public function getMimeType($path){
		return $this->basicOperation('getMimeType',$path);
	}
	public function hash($type, $path, $raw = false) {
		$absolutePath = $this->getAbsolutePath($path);
		if (OC_FileProxy::runPreProxies('hash', $absolutePath) && OC_Filesystem::isValidPath($path)) {
			$path = $this->getRelativePath($absolutePath);
			if ($path == null) {
				return false;
			}
			if (OC_Filesystem::$loaded && $this->fakeRoot == OC_Filesystem::getRoot()) {
				OC_Hook::emit(
					OC_Filesystem::CLASSNAME,
					OC_Filesystem::signal_read,
					array( OC_Filesystem::signal_param_path => $path)
				);
			}
			if ($storage = $this->getStorage($path)) {
				$result = $storage->hash($type, $this->getInternalPath($path), $raw);
				$result = OC_FileProxy::runPostProxies('hash', $absolutePath, $result);
				return $result;
			}
		}
		return null;
	}

	public function free_space($path='/'){
		return $this->basicOperation('free_space',$path);
	}

	/**
	 * abstraction for running most basic operations
	 * @param string $operation
	 * @param string #path
	 * @param array (optional) hooks
	 * @param mixed (optional) $extraParam
	 * @return mixed
	 */
	private function basicOperation($operation,$path,$hooks=array(),$extraParam=null){
		$absolutePath=$this->getAbsolutePath($path);
		if(OC_FileProxy::runPreProxies($operation,$absolutePath, $extraParam) and OC_Filesystem::isValidPath($path)){
			$path=$this->getRelativePath($absolutePath);
			if($path==null){
				return false;
			}
			$internalPath=$this->getInternalPath($path);
			$run=true;
			if(OC_Filesystem::$loaded and $this->fakeRoot==OC_Filesystem::getRoot()){
				foreach($hooks as $hook){
					if($hook!='read'){
						OC_Hook::emit( OC_Filesystem::CLASSNAME, $hook, array( OC_Filesystem::signal_param_path => $path, OC_Filesystem::signal_param_run => &$run));
					}else{
						OC_Hook::emit( OC_Filesystem::CLASSNAME, $hook, array( OC_Filesystem::signal_param_path => $path));
					}
				}
			}
			if($run and $storage=$this->getStorage($path)){
				if(!is_null($extraParam)){
					$result=$storage->$operation($internalPath,$extraParam);
				}else{
					$result=$storage->$operation($internalPath);
				}
				$result=OC_FileProxy::runPostProxies($operation,$this->getAbsolutePath($path),$result);
				if(OC_Filesystem::$loaded and $this->fakeRoot==OC_Filesystem::getRoot()){
					if($operation!='fopen'){//no post hooks for fopen, the file stream is still open
						foreach($hooks as $hook){
							if($hook!='read'){
								OC_Hook::emit( OC_Filesystem::CLASSNAME, 'post_'.$hook, array( OC_Filesystem::signal_param_path => $path));
							}
						}
					}
				}
				return $result;
			}
		}
		return null;
	}
}
