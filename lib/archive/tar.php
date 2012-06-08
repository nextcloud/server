<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once '3rdparty/Archive/Tar.php';

class OC_Archive_TAR extends OC_Archive{
	const PLAIN=0;
	const GZIP=1;
	const BZIP=2;

	private $fileList;
	
	/**
	 * @var Archive_Tar tar
	 */
	private $tar=null;
	private $path;

	function __construct($source){
		$types=array(null,'gz','bz');
		$this->path=$source;
		$this->tar=new Archive_Tar($source,$types[self::getTarType($source)]);
	}

	/**
	 * try to detect the type of tar compression
	 * @param string file
	 * @return str
	 */
	static public function getTarType($file){
		if(strpos($file,'.')){
			$extension=substr($file,strrpos($file,'.'));
			switch($extension){
				case 'gz':
				case 'tgz':
					return self::GZIP;
				case 'bz':
				case 'bz2':
					return self::BZIP;
				default:
					return self::PLAIN;
			}
		}else{
			return self::PLAIN;
		}
	}

	/**
	 * add an empty folder to the archive
	 * @param string path
	 * @return bool
	 */
	function addFolder($path){
		$tmpBase=get_temp_dir().'/';
		if(substr($path,-1,1)!='/'){
			$path.='/';
		}
		if($this->fileExists($path)){
			return false;
		}
		mkdir($tmpBase.$path);
		$result=$this->tar->addModify(array($tmpBase.$path),'',$tmpBase);
		rmdir($tmpBase.$path);
		$this->fileList=false;
		return $result;
	}
	/**
	 * add a file to the archive
	 * @param string path
	 * @param string source either a local file or string data
	 * @return bool
	 */
	function addFile($path,$source=''){
		if($this->fileExists($path)){
			$this->remove($path);
		}
		if(file_exists($source)){
			$header=array();
			$dummy='';
			$this->tar->_openAppend();
			$result=$this->tar->_addfile($source,$header,$dummy,$dummy,$path);
		}else{
			$result=$this->tar->addString($path,$source);
		}
		$this->fileList=false;
		return $result;
	}

	/**
	 * rename a file or folder in the archive
	 * @param string source
	 * @param string dest
	 * @return bool
	 */
	function rename($source,$dest){
		//no proper way to delete, rename entire archive, rename file and remake archive
		$tmp=OCP\Files::tmpFolder();
		$this->tar->extract($tmp);
		rename($tmp.$source,$tmp.$dest);
		$this->tar=null;
		unlink($this->path);
		$types=array(null,'gz','bz');
		$this->tar=new Archive_Tar($this->path,$types[self::getTarType($this->path)]);
		$this->tar->createModify(array($tmp),'',$tmp.'/');
		$this->fileList=false;
		return true;
	}

	private function getHeader($file){
		$headers=$this->tar->listContent();
		foreach($headers as $header){
			if($file==$header['filename'] or $file.'/'==$header['filename'] or '/'.$file.'/'==$header['filename'] or '/'.$file==$header['filename']){
				return $header;
			}
		}
		return null;
	}
	
	/**
	 * get the uncompressed size of a file in the archive
	 * @param string path
	 * @return int
	 */
	function filesize($path){
		$stat=$this->getHeader($path);
		return $stat['size'];
	}
	/**
	 * get the last modified time of a file in the archive
	 * @param string path
	 * @return int
	 */
	function mtime($path){
		$stat=$this->getHeader($path);
		return $stat['mtime'];
	}

	/**
	 * get the files in a folder
	 * @param path
	 * @return array
	 */
	function getFolder($path){
		$files=$this->getFiles();
		$folderContent=array();
		$pathLength=strlen($path);
		foreach($files as $file){
			if($file[0]=='/'){
				$file=substr($file,1);
			}
			if(substr($file,0,$pathLength)==$path and $file!=$path){
				$result=substr($file,$pathLength);
				if($pos=strpos($result,'/')){
					$result=substr($result,0,$pos+1);
				}
				if(array_search($result,$folderContent)===false){
					$folderContent[]=$result;
				}
			}
		}
		return $folderContent;
	}
	/**
	 *get all files in the archive
	 * @return array
	 */
	function getFiles(){
		if($this->fileList){
			return $this->fileList;
		}
		$headers=$this->tar->listContent();
		$files=array();
		foreach($headers as $header){
			$files[]=$header['filename'];
		}
		$this->fileList=$files;
		return $files;
	}
	/**
	 * get the content of a file
	 * @param string path
	 * @return string
	 */
	function getFile($path){
		return $this->tar->extractInString($path);
	}
	/**
	 * extract a single file from the archive
	 * @param string path
	 * @param string dest
	 * @return bool
	 */
	function extractFile($path,$dest){
		$tmp=OCP\Files::tmpFolder();
		if(!$this->fileExists($path)){
			return false;
		}
		if($this->fileExists('/'.$path)){
			$success=$this->tar->extractList(array('/'.$path),$tmp);
		}else{
			$success=$this->tar->extractList(array($path),$tmp);
		}
		if($success){
			rename($tmp.$path,$dest);
		}
		OCP\Files::rmdirr($tmp);
		return $success;
	}
	/**
	 * extract the archive
	 * @param string path
	 * @param string dest
	 * @return bool
	 */
	function extract($dest){
		return $this->tar->extract($dest);
	}
	/**
	 * check if a file or folder exists in the archive
	 * @param string path
	 * @return bool
	 */
	function fileExists($path){
		$files=$this->getFiles();
		if((array_search($path,$files)!==false) or (array_search($path.'/',$files)!==false)){
			return true;
		}else{
			$folderPath=$path;
			if(substr($folderPath,-1,1)!='/'){
				$folderPath.='/';
			}
			$pathLength=strlen($folderPath);
			foreach($files as $file){
				if(strlen($file)>$pathLength and substr($file,0,$pathLength)==$folderPath){
					return true;
				}
			}
		}
		if($path[0]!='/'){//not all programs agree on the use of a leading /
			return $this->fileExists('/'.$path);
		}else{
			return false;
		}
	}
	
	/**
	 * remove a file or folder from the archive
	 * @param string path
	 * @return bool
	 */
	function remove($path){
		if(!$this->fileExists($path)){
			return false;
		}
		$this->fileList=false;
		//no proper way to delete, extract entire archive, delete file and remake archive
		$tmp=OCP\Files::tmpFolder();
		$this->tar->extract($tmp);
		OCP\Files::rmdirr($tmp.$path);
		$this->tar=null;
		unlink($this->path);
		$this->reopen();
		$this->tar->createModify(array($tmp),'',$tmp);
		return true;
	}
	/**
	 * get a file handler
	 * @param string path
	 * @param string mode
	 * @return resource
	 */
	function getStream($path,$mode){
		if(strrpos($path,'.')!==false){
			$ext=substr($path,strrpos($path,'.'));
		}else{
			$ext='';
		}
		$tmpFile=OCP\Files::tmpFile($ext);
		if($this->fileExists($path)){
			$this->extractFile($path,$tmpFile);
		}elseif($mode=='r' or $mode=='rb'){
			return false;
		}
		if($mode=='r' or $mode=='rb'){
			return fopen($tmpFile,$mode);
		}else{
			OC_CloseStreamWrapper::$callBacks[$tmpFile]=array($this,'writeBack');
			self::$tempFiles[$tmpFile]=$path;
			return fopen('close://'.$tmpFile,$mode);
		}
	}

	private static $tempFiles=array();
	/**
	 * write back temporary files
	 */
	function writeBack($tmpFile){
		if(isset(self::$tempFiles[$tmpFile])){
			$this->addFile(self::$tempFiles[$tmpFile],$tmpFile);
			unlink($tmpFile);
		}
	}

	/**
	 * reopen the archive to ensure everything is written
	 */
	private function reopen(){
		if($this->tar){
			$this->tar->_close();
			$this->tar=null;
		}
		$types=array(null,'gz','bz');
		$this->tar=new Archive_Tar($this->path,$types[self::getTarType($this->path)]);
	}
}
