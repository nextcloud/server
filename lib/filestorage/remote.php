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

class OC_Filestorage_Remote extends OC_Filestorage{
	private $url;
	private $username;
	private $password;
	private $remote=false;
	private $statCache;
	private $statCacheDir=false;
	private $changed=array();
	
	private function cacheDir($dir){
		if($this->statCacheDir!=$dir or $this->statCacheDir===false){
			$this->statCache=$this->remote->getFiles($dir);
			$keys=array_keys($this->statCache);
			$this->statCacheDir=$dir;
		}
	}
	
	public function __construct($arguments){
		$this->url=$arguments['url'];
		$this->username=$arguments['username'];
		$this->password=$arguments['password'];
	}
	private function connect(){
		if($this->remote===false){
			$this->remote=OC_Connect::connect($this->url,$this->username,$this->password);
		}
	}
	public function mkdir($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$return=$this->remote->newFile($parent,$name,'dir');
		if($return){
			$this->notifyObservers($path,OC_FILEACTION_CREATE);
		}
		return $return;
	}
	public function rmdir($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$return=$this->remote->delete($parent,$name);
		if($return){
			$this->notifyObservers($path,OC_FILEACTION_DELETE);
		}
		return $return;
	}
	public function opendir($path){
		$this->connect();
		$this->cacheDir($path);
		$dirs=array_keys($this->statCache);
		$id=uniqid();
		global $FAKEDIRS;
		$FAKEDIRS[$id]=$dirs;
		if($return=opendir("fakedir://$id")){
			$this->notifyObservers($path,OC_FILEACTION_READ);
		}
		return $return;
	}
	public function is_dir($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($path);
		if($path=='' or $path=='/'){
			return true;
		}
		if(!isset($this->statCache[$name])){
			return false;
		}
		return ($this->statCache[$name]['type'=='dir']);
	}
	public function is_file($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return ($this->statCache[$name]['type'!='dir']);
	}
	public function stat($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return $false;
		}
		return $this->statCache[$name];
	}
	public function filetype($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['type'];
	}
	public function filesize($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return $false;
		}
		return $this->statCache[$name]['size'];
	}
	public function is_readable($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['readable'];
	}
	public function is_writeable($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['writeable'];
	}
	public function file_exists($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		return isset($this->statCache[$name]);
	}
	public function readfile($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$file=$this->remote->getFile($parent,$name);
		readfile($file);
		unlink($file);
	}
	public function filectime($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['ctime'];
	}
	public function filemtime($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['mtime'];
	}
	public function fileatime($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['atime'];
	}
	public function file_get_contents($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$file=$this->remote->getFile($parent,$name);
		file_get_contents($file);
		unlink($file);
	}
	public function file_put_contents($path,$data){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$file=$this->remote->getFile($parent,$name);
		$file=tempnam(get_temp_dir(),'oc_');
		file_put_contents($file,$data);
		if($return=$this->remote->sendTmpFile($file,$parent,$name)){
			$this->notifyObservers($path,OC_FILEACTION_WRITE);
		}
	}
	public function unlink($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		if($return=$this->remote->delete($paren,$name)){
			$this->notifyObservers($path,OC_FILEACTION_DELETE);
		}
		return $return;
	}
	public function rename($path1,$path2){
		$this->connect();
		$parent1=dirname($path1);
		$name1=substr($path1,strlen($parent1)+1);
		$parent2=dirname($path2);
		$name2=substr($path2,strlen($parent2)+1);
		if($return=$this->remote->move($parent1,$name1,$parent2,$name2)){
			$this->notifyObservers($path1.'->'.$path2,OC_FILEACTION_RENAME);
		}
		return $return;
	}
	public function copy($path1,$path2){
		$this->connect();
		$parent1=dirname($path1);
		$name1=substr($path1,strlen($parent1)+1);
		$parent2=dirname($path2);
		$name2=substr($path2,strlen($parent2)+1);
		if($return=$this->copy->rename($parent1,$name1,$parent2,$name2)){
			$this->notifyObservers($path1.'->'.$path2,OC_FILEACTION_RENAME);
		}
		return $return;
	}
	public function fopen($path,$mode){
		$this->connect();
		$changed=false;
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		$file=$this->remote->getFile($parent,$name);
		if($return=fopen($file,$mode)){
			switch($mode){
				case 'r':
					$this->notifyObservers($path,OC_FILEACTION_READ);
					break;
				case 'r+':
				case 'w+':
				case 'x+':
				case 'a+':
					$this->notifyObservers($path,OC_FILEACTION_READ | OC_FILEACTION_WRITE);
					$this->changed[]=array('dir'=>$parent,'file'=>$name,'tmp'=>$file);
					break;
				case 'w':
				case 'x':
				case 'a':
					$this->notifyObservers($path,OC_FILEACTION_WRITE);
					$this->changed[]=array('dir'=>$parent,'file'=>$name,'tmp'=>$file);
					break;
			}
		}
		return $return;
	}
	
	public function getMimeType($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		if(substr($name,0,1)=='/'){
			$name=substr($name,1);
		}
		$this->cacheDir($parent);
		if(!isset($this->statCache[$name])){
			return false;
		}
		return $this->statCache[$name]['mime'];
	}
	
	public function toTmpFile($path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		if(substr($name,0,1)=='/'){
			$name=substr($name,1);
		}
		$filename=$this->remote->getFile($parent,$name);
		if($filename){
			$this->notifyObservers($path,OC_FILEACTION_READ);
			return $filename;
		}else{
			return false;
		}
	}
	
	public function fromTmpFile($tmpFile,$path){
		$this->connect();
		$parent=dirname($path);
		$name=substr($path,strlen($parent)+1);
		if($this->remote->sendTmpFile($tmpFile,$parent,$name)){
			$this->notifyObservers($path,OC_FILEACTION_CREATE);
			return true;
		}else{
			return false;
		}
	}
	
	public function delTree($dir) {
		$this->connect();
		$parent=dirname($dir);
		$name=substr($dir,strlen($parent)+1);
		$return=$this->remote->delete($parent,$name);
		if($return=rmdir($dir)){
			$this->notifyObservers($dir,OC_FILEACTION_DELETE);
		}
		return $return;
	}
	
	public function find($path){
		return $this->getTree($path);
	}
	
	public function getTree($dir) {
		$this->connect();
		if($return=$this->remote->getTree($dir)){
			$this->notifyObservers($dir,OC_FILEACTION_READ);
		}
		return $return;
	}
	
	public function __destruct(){
		foreach($this->changed as $changed){
			$this->remote->sendTmpFile($changed['tmp'],$changed['dir'],$changed['file']);
		}
	}
}
