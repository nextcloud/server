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
	/**
	* check if the current users has the right premissions to read a file
	* @param  string  path
	* @return bool
	*/
	static private function canRead(){
		return true;//dummy untill premissions are correctly implemented, also the correcty value because for now users are locked in their seperate data dir and can read/write everything in there
	}
	/**
	* check if the current users has the right premissions to write a file
	* @param  string  path
	* @return bool
	*/
	static private function canWrite(){
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
		self::$storages[$mountpoint]=$storage;
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
			return $storage->mkdir(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function rmdir($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->rmdir(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function opendir($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->opendir(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function is_dir($path){
		if($path=='/'){
			return true;
		}
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->is_dir(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function is_file($path){
		if($path=='/'){
			return false;
		}
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->is_file(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function stat($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->stat(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function filetype($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->filetype(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function filesize($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->filesize(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function readfile($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->readfile(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function is_readable($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->is_readable(substr($path,strlen(self::getMountPoint($path))));
		}
		return false;
	}
	static public function is_writeable($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->is_writeable(substr($path,strlen(self::getMountPoint($path))));
		}
		return false;
	}
	static public function file_exists($path){
		if($path=='/'){
			return true;
		}
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->file_exists(substr($path,strlen(self::getMountPoint($path))));
		}
		return false;
	}
	static public function filectime($path){
		if($storage=self::getStorage($path)){
			return $storage->filectime(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function filemtime($path){
		if($storage=self::getStorage($path)){
			return $storage->filemtime(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function fileatime($path){
		if($storage=self::getStorage($path)){
			return $storage->fileatime(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function file_get_contents($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->file_get_contents(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function file_put_contents($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->file_put_contents(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function unlink($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->unlink(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function rename($path1,$path2){
		if(self::canWrite($path1) and self::canWrite($path2)){
			$mp1=self::getMountPoint($path1);
			$mp2=self::getMountPoint($path2);
			if($mp1==$mp2){
				if($storage=self::getStorage($path1)){
					return $storage->rename(substr($path1,strlen($mp1)),substr($path2,strlen($mp2)));
				}
			}elseif($storage1=self::getStorage($path1) and $storage2=self::getStorage($path2)){
				$tmpFile=$storage1->toTmpFile(substr($path1,strlen($mp1)));
				$result=$storage2->fromTmpFile($tmpFile,substr($path2,strlen($mp2)));
				$storage1->unlink(substr($path1,strlen($mp1)));
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
					return $storage->copy(substr($path1,strlen($mp1)),substr($path2,strlen($mp2)));
				}
			}elseif($storage1=self::getStorage($path1) and $storage2=self::getStorage($path2)){
				$tmpFile=$storage1->toTmpFile(substr($path1,strlen($mp1)));
				return $storage2->fromTmpFile($tmpFile,substr($path2,strlen($mp2)));
			}
		}
	}
	static public function fopen($path,$mode){
		$allowed=((strpos($path,'r')===false and strpos($path,'r+')!==false and self::canRead) or self::canWrite($path));
		if($allowed){
			if($storage=self::getStorage($path)){
				return $storage->fopen(substr($path,strlen(self::getMountPoint($path))),$mode);
			}
		}
	}
	static public function toTmpFile($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->toTmpFile(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function fromTmpFile($tmpFile,$path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->fromTmpFile($tmpFile,substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function getMimeType($path){
		if(self::canRead($path) and $storage=self::getStorage($path)){
			return $storage->getMimeType(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function delTree($path){
		if(self::canWrite($path) and $storage=self::getStorage($path)){
			return $storage->delTree(substr($path,strlen(self::getMountPoint($path))));
		}
	}
	static public function find($path){
		if($storage=self::getStorage($path)){
			return $storage->find(substr($path,strlen(self::getMountPoint($path))));
		}
	}
}

/**
 * Privde a common interface to all different storage options
 */
interface OC_FILESTORAGE{
	public function __construct($parameters);
	public function mkdir($path);
	public function rmdir($path);
	public function opendir($path);
	public function is_dir($path);
	public function is_file($path);
	public function stat($path);
	public function filetype($path);
	public function filesize($path);
	public function is_readable($path);
	public function is_writeable($path);
	public function file_exists($path);
	public function readfile($path);
	public function filectime($path);
	public function filemtime($path);
	public function fileatime($path);
	public function file_get_contents($path);
	public function file_put_contents($path);
	public function unlink($path);
	public function rename($path1,$path2);
	public function copy($path1,$path2);
	public function fopen($path,$mode);
	public function toTmpFile($path);//copy the file to a temporary file, used for cross-storage file actions
	public function fromTmpFile($tmpPath,$path);//copy a file from a temporary file, used for cross-storage file actions
	public function getMimeType($path);
	public function delTree($path);
	public function find($path);
}

/**
 * for local filestore, we only have to map the paths
 */
class OC_FILESTORAGE_LOCAL implements OC_FILESTORAGE{
	private $datadir;
	public function __construct($arguments){
		$this->datadir=$arguments['datadir'];
		if(substr($this->datadir,-1)!=='/'){
			$this->datadir.='/';
		}
	}
	public function mkdir($path){
		return mkdir($this->datadir.$path);
	}
	public function rmdir($path){
		return rmdir($this->datadir.$path);
	}
	public function opendir($path){
		return opendir($this->datadir.$path);
	}
	public function is_dir($path){
		return is_dir($this->datadir.$path);
	}
	public function is_file($path){
		return is_file($this->datadir.$path);
	}
	public function stat($path){
		return stat($this->datadir.$path);
	}
	public function filetype($path){
		return filetype($this->datadir.$path);
	}
	public function filesize($path){
		return filesize($this->datadir.$path);
	}
	public function is_readable($path){
		return is_readable($this->datadir.$path);
	}
	public function is_writeable($path){
		return is_writeable($this->datadir.$path);
	}
	public function file_exists($path){
		return file_exists($this->datadir.$path);
	}
	public function readfile($path){
		return readfile($this->datadir.$path);
	}
	public function filectime($path){
		return filectime($this->datadir.$path);
	}
	public function filemtime($path){
		return filemtime($this->datadir.$path);
	}
	public function fileatime($path){
		return fileatime($this->datadir.$path);
	}
	public function file_get_contents($path){
		return file_get_contents($this->datadir.$path);
	}
	public function file_put_contents($path){
		return file_put_contents($this->datadir.$path);
	}
	public function unlink($path){
		return unlink($this->datadir.$path);
	}
	public function rename($path1,$path2){
		return rename($this->datadir.$path1,$this->datadir.$path2);
	}
	public function copy($path1,$path2){
		return copy($this->datadir.$path1,$this->datadir.$path2);
	}
	public function fopen($path,$mode){
		return fopen($this->datadir.$path,$mode);
	}
	
	public function getMimeType($fspath){
		if (@is_dir($this->datadir.$fspath)) {
			// directories are easy
			return "httpd/unix-directory"; 
		} else if (function_exists("mime_content_type")) {
			// use mime magic extension if available
			$mime_type = mime_content_type($this->datadir.$fspath);
		} else if (self::canExecute("file")) {
			// it looks like we have a 'file' command, 
			// lets see it it does have mime support
			$fp = popen("file -i '$fspath' 2>/dev/null", "r");
			$reply = fgets($fp);
			pclose($fp);
			
			// popen will not return an error if the binary was not found
			// and find may not have mime support using "-i"
			// so we test the format of the returned string 
			
			// the reply begins with the requested filename
			if (!strncmp($reply, "$fspath: ", strlen($fspath)+2)) {                     
				$reply = substr($reply, strlen($fspath)+2);
				// followed by the mime type (maybe including options)
				if (preg_match('/^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*/', $reply, $matches)) {
					$mime_type = $matches[0];
				}
			}
		} 
		if (empty($mime_type)) {
			// Fallback solution: try to guess the type by the file extension
			// TODO: add more ...
			switch (strtolower(strrchr(basename($fspath), "."))) {
			case ".html":
				$mime_type = "text/html";
				break;
			case ".txt":
				$mime_type = "text/plain";
				break;
			case ".css":
				$mime_type = "text/css";
				break;
			case ".gif":
				$mime_type = "image/gif";
				break;
			case ".jpg":
				$mime_type = "image/jpeg";
				break;
			case ".jpeg":
				$mime_type = "image/jpeg";
				break;
			case ".png":
				$mime_type = "image/png";
				break;
			default: 
				$mime_type = "application/octet-stream";
				break;
			}
		}
		
		return $mime_type;
	}
	
	/**
	* detect if a given program is found in the search PATH
	*
	* helper function used by _mimetype() to detect if the 
	* external 'file' utility is available
	*
	* @param  string  program name
	* @param  string  optional search path, defaults to $PATH
	* @return bool    true if executable program found in path
	*/
	private function canExecute($name, $path = false) 
	{
		// path defaults to PATH from environment if not set
		if ($path === false) {
			$path = getenv("PATH");
		}
		
		// check method depends on operating system
		if (!strncmp(PHP_OS, "WIN", 3)) {
			// on Windows an appropriate COM or EXE file needs to exist
			$exts = array(".exe", ".com");
			$check_fn = "file_exists";
		} else { 
			// anywhere else we look for an executable file of that name
			$exts = array("");
			$check_fn = "is_executable";
		}
		
		// now check the directories in the path for the program
		foreach (explode(PATH_SEPARATOR, $path) as $dir) {
			// skip invalid path entries
			if (!file_exists($dir)) continue;
			if (!is_dir($dir)) continue;

			// and now look for the file
			foreach ($exts as $ext) {
				if ($check_fn("$dir/$name".$ext)) return true;
			}
		}

		return false;
	}
	
	public function toTmpFile($path){
		$tmpFolder=sys_get_temp_dir();
		$filename=tempnam($tmpFolder,'OC_TEMP_FILE_'.substr($path,strrpos($path,'.')));
		copy($this->datadir.$path,$filename);
		return $filename;
	}
	
	public function fromTmpFile($tmpFile,$path){
		copy($tmpFile,$this->datadir.$path);
		unlink($tmpFile);
	}
	
	public function delTree($dir) {
		$dirRelative=$dir;
		$dir=$this->datadir.$dir;
		if (!file_exists($dir)) return true; 
		if (!is_dir($dir) || is_link($dir)) return unlink($dir); 
		foreach (scandir($dir) as $item) { 
			if ($item == '.' || $item == '..') continue; 
			if(is_file($dir.'/'.$item)){
				unlink($dir.'/'.$item);
			}elseif(is_dir($dir.'/'.$item)){
				if (!$this->delTree($dirRelative. "/" . $item)){ 
					return false; 
				};
			}
		} 
		return rmdir($dir); 
	}
	
	public function find($path){
		return System::find($this->datadir.$path);
	}
}
?>
