<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski GapczynskiM@gmail.com
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
*/

abstract class OC_Filestorage_Common extends OC_Filestorage {

	public function __construct($parameters){}
// 	abstract public function mkdir($path);
// 	abstract public function rmdir($path);
// 	abstract public function opendir($path);
	public function is_dir($path){
		return $this->filetype($path)=='dir';
	}
	public function is_file($path){
		return $this->filetype($path)=='file';
	}
// 	abstract public function stat($path);
// 	abstract public function filetype($path);
	public function filesize($path) {
		if($this->is_dir($path)){
			return 0;//by definition
		}else{
			$stat = $this->stat($path);
			return $stat['size'];
		}
	}
// 	abstract public function is_readable($path);
// 	abstract public function is_writable($path);
// 	abstract public function file_exists($path);
	public function filectime($path) {
		$stat = $this->stat($path);
		return $stat['ctime'];
	}
	public function filemtime($path) {
		$stat = $this->stat($path);
		return $stat['mtime'];
	}
	public function fileatime($path) {
		$stat = $this->stat($path);
		return $stat['atime'];
	}
	public function file_get_contents($path) {
		$handle = $this->fopen($path, "r");
		if(!$handle){
			return false;
		}
		$size=$this->filesize($path);
		if($size==0){
			return '';
		}
		return fread($handle, $size);
	}
	public function file_put_contents($path,$data) {
		$handle = $this->fopen($path, "w");
		return fwrite($handle, $data);
	}
// 	abstract public function unlink($path);
	public function rename($path1,$path2){
		if($this->copy($path1,$path2)){
			return $this->unlink($path1);
		}else{
			return false;
		}
	}
	public function copy($path1,$path2) {
		$source=$this->fopen($path1,'r');
		$target=$this->fopen($path2,'w');
		$count=OC_Helper::streamCopy($source,$target);
		return $count>0;
	}
// 	abstract public function fopen($path,$mode);
	public function getMimeType($path){
		if(!$this->file_exists($path)){
			return false;
		}
		if($this->is_dir($path)){
			return 'httpd/unix-directory';
		}
		$source=$this->fopen($path,'r');
		if(!$source){
			return false;
		}
		$head=fread($source,8192);//8kb should suffice to determine a mimetype
		if($pos=strrpos($path,'.')){
			$extension=substr($path,$pos);
		}else{
			$extension='';
		}
		$tmpFile=OC_Helper::tmpFile($extension);
		file_put_contents($tmpFile,$head);
		$mime=OC_Helper::getMimeType($tmpFile);
		unlink($tmpFile);
		return $mime;
	}
	public function hash($type,$path,$raw = false){
		$tmpFile=$this->getLocalFile();
		$hash=hash($type,$tmpFile,$raw);
		unlink($tmpFile);
		return $hash;
	}
// 	abstract public function free_space($path);
	public function search($query){
		return $this->searchInDir($query);
	}
	public function getLocalFile($path){
		return $this->toTmpFile($path);
	}
	private function toTmpFile($path){//no longer in the storage api, still usefull here
		$source=$this->fopen($path,'r');
		if(!$source){
			return false;
		}
		if($pos=strrpos($path,'.')){
			$extension=substr($path,$pos);
		}else{
			$extension='';
		}
		$tmpFile=OC_Helper::tmpFile($extension);
		$target=fopen($tmpFile,'w');
		$count=OC_Helper::streamCopy($source,$target);
		return $tmpFile;
	}
// 	abstract public function touch($path, $mtime=null);

	protected function searchInDir($query,$dir=''){
		$files=array();
		$dh=$this->opendir($dir);
		if($dh){
			while($item=readdir($dh)){
				if ($item == '.' || $item == '..') continue;
				if(strstr(strtolower($item),strtolower($query))!==false){
					$files[]=$dir.'/'.$item;
				}
				if($this->is_dir($dir.'/'.$item)){
					$files=array_merge($files,$this->searchInDir($query,$dir.'/'.$item));
				}
			}
		}
		return $files;
	}
}
