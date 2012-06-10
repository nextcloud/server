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
	private static $mounted=array();
	private static $enableAutomount=true;
	private static $rootView;
	
	private function stripPath($path){//files should never start with /
		if(!$path || $path[0]=='/'){
			$path=substr($path,1);
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
			if($this->is_dir($path)){
				$stat=array('size'=>0);
				$stat['mtime']=filemtime($this->path);
			}else{
				$stat=array();
				$stat['mtime']=$this->archive->mtime($path);
				$stat['size']=$this->archive->filesize($path);
			}
		}
		$stat['ctime']=$ctime;
		return $stat;
	}
	public function filetype($path){
		$path=$this->stripPath($path);
		if($path==''){
			return 'dir';
		}
		if(substr($path,-1)=='/'){
			return $this->archive->fileExists($path)?'dir':'file';
		}else{
			return $this->archive->fileExists($path.'/')?'dir':'file';
		}
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
		return $this->archive->fileExists($path);
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
			$tmpFile=OCP\Files::tmpFile();
			$this->archive->extractFile($path,$tmpFile);
			$this->archive->addfile($path,$tmpFile);
		}else{
			return false;//not supported
		}
	}

	/**
	 * automount paths from file hooks
	 * @param aray params
	 */
	public static function autoMount($params){
		if(!self::$enableAutomount){
			return;
		}
		$path=$params['path'];
		if(!self::$rootView){
			self::$rootView=new OC_FilesystemView('');
		}
		self::$enableAutomount=false;//prevent recursion
		$supported=array('zip','tar.gz','tar.bz2','tgz');
		foreach($supported as $type){
			$ext='.'.$type.'/';
			if(($pos=strpos(strtolower($path),$ext))!==false){
				$archive=substr($path,0,$pos+strlen($ext)-1);
				if(self::$rootView->file_exists($archive) and  array_search($archive,self::$mounted)===false){
					$localArchive=self::$rootView->getLocalFile($archive);
					OC_Filesystem::mount('OC_Filestorage_Archive',array('archive'=>$localArchive),$archive.'/');
					self::$mounted[]=$archive;
				}
			}
		}
		self::$enableAutomount=true;
	}

	public function rename($path1,$path2){
		return $this->archive->rename($path1,$path2);
	}
}
