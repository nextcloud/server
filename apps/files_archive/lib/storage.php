<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Filestorage_Archive extends OC_Filestorage_Common{
	/**
	 * underlying local storage used for missing functions
	 * @var OC_Archive
	 */
	private $archive;
	private $path;
	
	private function stripPath($path){//files should never start with /
		if(substr($path,0,1)=='/'){
			return substr($path,1);
		}
		return $path;
	}
	
	public function __construct($params){
		$this->archive=OC_Archive::open($params['archive']);
		$this->path=$params['archive'];
	}

	public function mkdir($path){
		$path=$this->stripPath($path);
		return $this->archive->addFolder($path);
	}
	public function rmdir($path){
		$path=$this->stripPath($path);
		return $this->archive->remove($path.'/');
	}
	public function opendir($path){
		$path=$this->stripPath($path);
		$content=$this->archive->getFolder($path);
		foreach($content as &$file){
			if(substr($file,-1)=='/'){
				$file=substr($file,0,-1);
			}
		}
		$id=md5($this->path.$path);
		OC_FakeDirStream::$dirs[$id]=$content;
		return opendir('fakedir://'.$id);
	}
	public function stat($path){
		$ctime=filectime($this->path);
		$path=$this->stripPath($path);
		if($path==''){
			$stat=stat($this->path);
		}else{
			$stat=array();
			$stat['mtime']=$this->archive->mtime($path);
			$stat['size']=$this->archive->filesize($path);
		}
		$stat['ctime']=$ctime;
		return $stat;
	}
	public function filetype($path){
		$path=$this->stripPath($path);
		if($path==''){
			return 'dir';
		}
		return $this->archive->fileExists($path.'/')?'dir':'file';
	}
	public function is_readable($path){
		return is_readable($this->path);
	}
	public function is_writable($path){
		return is_writable($this->path);
	}
	public function file_exists($path){
		$path=$this->stripPath($path);
		if($path==''){
			return file_exists($this->path);
		}
		return $this->archive->fileExists($path) or $this->archive->fileExists($path.'/');
	}
	public function unlink($path){
		$path=$this->stripPath($path);
		return $this->archive->remove($path);
	}
	public function fopen($path,$mode){
		$path=$this->stripPath($path);
		return $this->archive->getStream($path,$mode);
	}
	public function free_space($path){
		return 0;
	}
	public function touch($path, $mtime=null){
		if(is_null($mtime)){
			$tmpFile=OC_Helper::tmpFile();
			$this->archive->extractFile($path,$tmpFile);
			$this->archive->addfile($path,$tmpFile);
		}else{
			return false;//not supported
		}
	}
}
