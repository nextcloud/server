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
*/

/**
 * Privde a common interface to all different storage options
 */
class OC_FILESTORAGE{
	private $observers=array();
	/**
	* add an observer to the list
	* @param  OC_FILEOBERSER  observer
	*/
	public function addObserver($observer){
		$this->observers[]=$observer;
	}
	/**
	* notify the observers about an action
	* @param  int action    a combination of OC_FILEACTION_WRITE and OC_FILEACTION_READ
	* @param string path    relative path of the file
	*/
	public function notifyObservers($path,$action){
		foreach($this->observers as $observer){
			if($observer->mask & $action){
				$observer->notify($path,$action,$this);
			}
		}
	}
	
	public function __construct($parameters){}
	public function mkdir($path){}
	public function rmdir($path){}
	public function opendir($path){}
	public function is_dir($path){}
	public function is_file($path){}
	public function stat($path){}
	public function filetype($path){}
	public function filesize($path){}
	public function is_readable($path){}
	public function is_writeable($path){}
	public function file_exists($path){}
	public function readfile($path){}
	public function filectime($path){}
	public function filemtime($path){}
	public function fileatime($path){}
	public function file_get_contents($path){}
	public function file_put_contents($path,$data){}
	public function unlink($path){}
	public function rename($path1,$path2){}
	public function copy($path1,$path2){}
	public function fopen($path,$mode){}
	public function toTmpFile($path){}//copy the file to a temporary file, used for cross-storage file actions
	public function fromTmpFile($tmpPath,$path){}//copy a file from a temporary file, used for cross-storage file actions
	public function getMimeType($path){}
	public function delTree($path){}
	public function find($path){}
	public function getTree($path){}
}

/**
 * for local filestore, we only have to map the paths
 */
class OC_FILESTORAGE_LOCAL extends OC_FILESTORAGE{
	private $datadir;
	public function __construct($arguments){
		$this->datadir=$arguments['datadir'];
		if(substr($this->datadir,-1)!=='/'){
			$this->datadir.='/';
		}
	}
	public function mkdir($path){
		if($return=mkdir($this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_CREATE);
		}
		return $return;
	}
	public function rmdir($path){
		if($return=rmdir($this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_DELETE);
		}
		return $return;
	}
	public function opendir($path){
		if($return=opendir($this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_READ);
		}
		return $return;
	}
	public function is_dir($path){
		return (is_dir($this->datadir.$path) or substr($path,-1)=='/');
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
		if($return=readfile($this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_READ);
		}
		return $return;
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
		if($return=file_get_contents($this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_READ);
		}
		return $return;
	}
	public function file_put_contents($path,$data){
		if($return=file_put_contents($this->datadir.$path,$data)){
			$this->notifyObservers($path,OC_FILEACTION_WRITE);
		}
	}
	public function unlink($path){
		if($return=unlink($this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_DELETE);
		}
		return $return;
	}
	public function rename($path1,$path2){
		if($return=rename($this->datadir.$path1,$this->datadir.$path2)){
			$this->notifyObservers($path1.'->'.$path2,OC_FILEACTION_RENAME);
		}
		return $return;
	}
	public function copy($path1,$path2){
		if($this->is_dir($path2)){
			if(!$this->file_exists($path2)){
				$this->mkdir($path2);
			}
			$source=substr($path1,strrpos($path1,'/')+1);
			$path2.=$source;
// 			sleep(30);
		}else{
			error_log('isfile');
		}
		error_log("copy $path1 to {$this->datadir}$path2");
		if($return=copy($this->datadir.$path1,$this->datadir.$path2)){
			error_log('success');
			$this->notifyObservers($path2,OC_FILEACTION_CREATE);
		}
		return $return;
	}
	public function fopen($path,$mode){
		if($return=fopen($this->datadir.$path,$mode)){
			switch($mode){
				case 'r':
					$this->notifyObservers($path,OC_FILEACTION_READ);
					break;
				case 'r+':
				case 'w+':
				case 'x+':
				case 'a+':
					$this->notifyObservers($path,OC_FILEACTION_READ | OC_FILEACTION_WRITE);
					break;
				case 'w':
				case 'x':
				case 'a':
					$this->notifyObservers($path,OC_FILEACTION_WRITE);
					break;
			}
		}
		return $return;
	}
	
	public function getMimeType($fspath){
		if (@is_dir($this->datadir.$fspath)) {
			// directories are easy
			return "httpd/unix-directory"; 
		}elseif (function_exists('finfo_open') and function_exists('finfo_file') and $finfo=finfo_open(FILEINFO_MIME)){
			$mimeType =strtolower(finfo_file($finfo,$this->datadir.$fspath));
			$mimeType=substr($mimeType,0,strpos($mimeType,';'));
			finfo_close($finfo);
			return $mimeType;
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
		if(copy($this->datadir.$path,$filename)){
			$this->notifyObservers($path,OC_FILEACTION_READ);
			return $filename;
		}else{
			return false;
		}
	}
	
	public function fromTmpFile($tmpFile,$path){
		if(rename($tmpFile,$this->datadir.$path)){
			$this->notifyObservers($path,OC_FILEACTION_CREATE);
			return true;
		}else{
			return false;
		}
	}
	
	public function delTree($dir) {
		$dirRelative=$dir;
		$dir=$this->datadir.$dir;
		if (!file_exists($dir)) return true; 
		if (!is_dir($dir) || is_link($dir)) return unlink($dir); 
		foreach (scandir($dir) as $item) { 
			if ($item == '.' || $item == '..') continue; 
			if(is_file($dir.'/'.$item)){
				if(unlink($dir.'/'.$item)){
					$this->notifyObservers($dir.'/'.$item,OC_FILEACTION_DELETE);
				}
			}elseif(is_dir($dir.'/'.$item)){
				if (!$this->delTree($dirRelative. "/" . $item)){ 
					return false; 
				};
			}
		}
		if($return=rmdir($dir)){
			$this->notifyObservers($dir,OC_FILEACTION_DELETE);
		}
		return $return;
	}
	
	public function find($path){
		$return=System::find($this->datadir.$path);
		foreach($return as &$file){
			$file=str_replace($file,$this->datadir,'');
		}
		return $return;
	}
	
	public function getTree($dir) {
		if(substr($dir,-1,1)=='/'){
			$dir=substr($dir,0,-1);
		}
		$tree=array();
		$tree[]=$dir;
		$dirRelative=$dir;
		$dir=$this->datadir.$dir;
		if (!file_exists($dir)) return true; 
		foreach (scandir($dir) as $item) { 
			if ($item == '.' || $item == '..') continue; 
			if(is_file($dir.'/'.$item)){
				$tree[]=$dirRelative.'/'.$item;
			}elseif(is_dir($dir.'/'.$item)){
				if ($subTree=$this->getTree($dirRelative. "/" . $item)){
					$tree=array_merge($tree,$subTree);
				}
			}
		}
		return $tree;
	}
}
?>