<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('php-cloudfiles/cloudfiles.php');

class OC_FileStorage_SWIFT extends OC_Filestorage_Common{
	private $host;
	private $root;
	private $user;
	private $token;
	private $secure;
	/**
	 * @var CF_Authentication auth
	 */
	private $auth;
	/**
	 * @var CF_Connection conn
	 */
	private $conn;
	/**
	 * @var CF_Container rootContainer
	 */
	private $rootContainer;

	private static $tempFiles=array();
	private $objects=array();
	private $containers=array();

	const SUBCONTAINER_FILE='.subcontainers';

	/**
	 * translate directory path to container name
	 * @param string path
	 * @return string
	 */
	private function getContainerName($path){
		$path=trim($this->root.$path,'/');
		return str_replace('/','\\',$path);
	}

	/**
	 * get container by path
	 * @param string path
	 * @return CF_Container
	 */
	private function getContainer($path){
		if($path=='' or $path=='/'){
			return $this->rootContainer;
		}
		if(isset($this->containers[$path])){
			return $this->containers[$path];
		}
		try{
			$container=$this->conn->get_container($this->getContainerName($path));
			$this->containers[$path]=$container;
			return $container;
		}catch(NoSuchContainerException $e){
			return null;
		}
	}

	/**
	 * create container
	 * @param string path
	 * @return CF_Container
	 */
	private function createContainer($path){
		if($path=='' or $path=='/'){
			return $this->conn->create_container($this->getContainerName($path));
		}
		$parent=dirname($path);
		if($parent=='' or $parent=='/'){
			$parentContainer=$this->rootContainer;
		}else{
			if(!$this->containerExists($parent)){
				$parentContainer=$this->createContainer($parent);
			}else{
				$parentContainer=$this->getContainer($parent);
			}
		}
		$this->addSubContainer($parentContainer,basename($path));
		return $this->conn->create_container($this->getContainerName($path));
	}

	/**
	 * get object by path
	 * @param string path
	 * @return CF_Object
	 */
	private function getObject($path){
		if(isset($this->objects[$path])){
			return $this->objects[$path];
		}
		$container=$this->getContainer(dirname($path));
		if(is_null($container)){
			return null;
		}else{
			try{
				$obj=$container->get_object(basename($path));
				$this->objects[$path]=$obj;
				return $obj;
			}catch(NoSuchObjectException $e){
				return null;
			}
		}
	}

	/**
	 * get the names of all objects in a container
	 * @param CF_Container
	 * @return array
	 */
	private function getObjects($container){
		if(is_null($container)){
			return array();
		}else{
			$files=$container->get_objects();
			foreach($files as &$file){
				$file=$file->name;
			}
			return $files;
		}
	}

	/**
	 * create object
	 * @param string path
	 * @return CF_Object
	 */
	private function createObject($path){
		$container=$this->getContainer(dirname($path));
		if(!is_null($container)){
			$container=$this->createContainer($path);
		}
		return $container->create_object(basename($path));
	}

	/**
	 * check if an object exists
	 * @param string
	 * @return bool
	 */
	private function objectExists($path){
		return !is_null($this->getObject($path));
	}

	/**
	 * check if container for path exists
	 * @param string path
	 * @return bool
	 */
	private function containerExists($path){
		return !is_null($this->getContainer($path));
	}

	/**
	 * get the list of emulated sub containers
	 * @param CF_Container container
	 * @return array
	 */
	private function getSubContainers($container){
		$tmpFile=OCP\Files::tmpFile();
		$obj=$this->getSubContainerFile($container);
		try{
			$obj->save_to_filename($tmpFile);
		}catch(Exception $e){
			return array();
		}
		$obj->save_to_filename($tmpFile);
		$containers=file($tmpFile);
		unlink($tmpFile);
		foreach($containers as &$sub){
			$sub=trim($sub);
		}
		return $containers;
	}

	/**
	 * add an emulated sub container
	 * @param CF_Container container
	 * @param string name
	 * @return bool
	 */
	private function addSubContainer($container,$name){
		if(!$name){
			return false;
		}
		$tmpFile=OCP\Files::tmpFile();
		$obj=$this->getSubContainerFile($container);
		try{
			$obj->save_to_filename($tmpFile);
			$containers=file($tmpFile);
			foreach($containers as &$sub){
				$sub=trim($sub);
			}
			if(array_search($name,$containers)!==false){
				unlink($tmpFile);
				return false;
			}else{
				$fh=fopen($tmpFile,'a');
				fwrite($fh,$name."\n");
			}
		}catch(Exception $e){
			$containers=array();
			file_put_contents($tmpFile,$name."\n");
		}

		$obj->load_from_filename($tmpFile);
		unlink($tmpFile);
		return true;
	}

	/**
	 * remove an emulated sub container
	 * @param CF_Container container
	 * @param string name
	 * @return bool
	 */
	private function removeSubContainer($container,$name){
		if(!$name){
			return false;
		}
		$tmpFile=OCP\Files::tmpFile();
		$obj=$this->getSubContainerFile($container);
		try{
			$obj->save_to_filename($tmpFile);
			$containers=file($tmpFile);
		}catch(Exception $e){
			return false;
		}
		foreach($containers as &$sub){
			$sub=trim($sub);
		}
		$i=array_search($name,$containers);
		if($i===false){
			unlink($tmpFile);
			return false;
		}else{
			unset($containers[$i]);
			file_put_contents($tmpFile,implode("\n",$containers)."\n");
		}

		$obj->load_from_filename($tmpFile);
		unlink($tmpFile);
		return true;
	}

	/**
	 * ensure a subcontainer file exists and return it's object
	 * @param CF_Container container
	 * @return CF_Object
	 */
	private function getSubContainerFile($container){
		try{
			return $container->get_object(self::SUBCONTAINER_FILE);
		}catch(NoSuchObjectException $e){
			return $container->create_object(self::SUBCONTAINER_FILE);
		}
	}

	public function __construct($params){
		$this->token=$params['token'];
		$this->host=$params['host'];
		$this->user=$params['user'];
		$this->root=isset($params['root'])?$params['root']:'/';
		$this->secure=isset($params['secure'])?(bool)$params['secure']:true;
		if(!$this->root || $this->root[0]!='/'){
			$this->root='/'.$this->root;
		}
		$this->auth = new CF_Authentication($this->user, $this->token, null, $this->host);
		$this->auth->authenticate();
		
		$this->conn = new CF_Connection($this->auth);

		if(!$this->containerExists($this->root)){
			$this->rootContainer=$this->createContainer('/');
		}else{
			$this->rootContainer=$this->getContainer('/');
		}
	}


	public function mkdir($path){
		if($this->containerExists($path)){
			return false;
		}else{
			$this->createContainer($path);
			return true;
		}
	}

	public function rmdir($path){
		if(!$this->containerExists($path)){
			return false;
		}else{
			$this->emptyContainer($path);
			if($path!='' and $path!='/'){
				$parentContainer=$this->getContainer(dirname($path));
				$this->removeSubContainer($parentContainer,basename($path));
			}
			
			$this->conn->delete_container($this->getContainerName($path));
			unset($this->containers[$path]);
			return true;
		}
	}

	private function emptyContainer($path){
		$container=$this->getContainer($path);
		if(is_null($container)){
			return;
		}
		$subContainers=$this->getSubContainers($container);
		foreach($subContainers as $sub){
			if($sub){
				$this->emptyContainer($path.'/'.$sub);
				$this->conn->delete_container($this->getContainerName($path.'/'.$sub));
				unset($this->containers[$path.'/'.$sub]);
			}
		}

		$objects=$this->getObjects($container);
		foreach($objects as $object){
			$container->delete_object($object);
			unset($this->objects[$path.'/'.$object]);
		}
	}

	public function opendir($path){
		$container=$this->getContainer($path);
		$files=$this->getObjects($container);
		$i=array_search(self::SUBCONTAINER_FILE,$files);
		if($i!==false){
			unset($files[$i]);
		}
		$subContainers=$this->getSubContainers($container);
		$files=array_merge($files,$subContainers);
		$id=$this->getContainerName($path);
		OC_FakeDirStream::$dirs[$id]=$files;
		return opendir('fakedir://'.$id);
	}

	public function filetype($path){
		if($this->containerExists($path)){
			return 'dir';
		}else{
			return 'file';
		}
	}

	public function is_readable($path){
		return true;
	}

	public function is_writable($path){
		return true;
	}

	public function file_exists($path){
		if($this->is_dir($path)){
			return true;
		}else{
			return $this->objectExists($path);
		}
	}

	public function file_get_contents($path){
		$obj=$this->getObject($path);
		if(is_null($obj)){
			return false;
		}
		return $obj->read();
	}

	public function file_put_contents($path,$content){
		$obj=$this->getObject($path);
		if(is_null($obj)){
			$container=$this->getContainer(dirname($path));
			if(is_null($container)){
				return false;
			}
			$obj=$container->create_object(basename($path));
		}
		$this->resetMTime($obj);
		return $obj->write($content);
	}

	public function unlink($path){
		if($this->objectExists($path)){
			$container=$this->getContainer(dirname($path));
			$container->delete_object(basename($path));
			unset($this->objects[$path]);
		}else{
			return false;
		}
	}

	public function fopen($path,$mode){
		$obj=$this->getObject($path);
		if(is_null($obj)){
			return false;
		}
		switch($mode){
			case 'r':
			case 'rb':
				$fp = fopen('php://temp', 'r+');
				$obj->stream($fp);
				
				rewind($fp);
				return $fp;
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				$tmpFile=$this->getTmpFile($path);
				OC_CloseStreamWrapper::$callBacks[$tmpFile]=array($this,'writeBack');
				self::$tempFiles[$tmpFile]=$path;
				return fopen('close://'.$tmpFile,$mode);
		}
	}

	public function writeBack($tmpFile){
		if(isset(self::$tempFiles[$tmpFile])){
			$this->fromTmpFile($tmpFile,self::$tempFiles[$tmpFile]);
			unlink($tmpFile);
		}
	}

	public function free_space($path){
		return 0;
	}

	public function touch($path,$mtime=null){
		$obj=$this->getObject($path);
		if(is_null($obj)){
			return false;
		}
		if(is_null($mtime)){
			$mtime=time();
		}
		
		//emulate setting mtime with metadata
		$obj->metadata['Mtime']=$mtime;
		$obj->sync_metadata();
	}

	public function rename($path1,$path2){
		$sourceContainer=$this->getContainer(dirname($path1));
		$targetContainer=$this->getContainer(dirname($path2));
		$result=$sourceContainer->move_object_to(basename($path1),$targetContainer,basename($path2));
		unset($this->objects[$path1]);
		if($result){
			$targetObj=$this->getObject($path2);
			$this->resetMTime($targetObj);
		}
		return $result;
	}

	public function copy($path1,$path2){
		$sourceContainer=$this->getContainer(dirname($path1));
		$targetContainer=$this->getContainer(dirname($path2));
		$result=$sourceContainer->copy_object_to(basename($path1),$targetContainer,basename($path2));
		if($result){
			$targetObj=$this->getObject($path2);
			$this->resetMTime($targetObj);
		}
		return $result;
	}

	public function stat($path){
		$obj=$this->getObject($path);
		if(is_null($obj)){
			return false;
		}

		if(isset($obj->metadata['Mtime']) and $obj->metadata['Mtime']>-1){
			$mtime=$obj->metadata['Mtime'];
		}else{
			$mtime=strtotime($obj->last_modified);
		}
		return array(
			'mtime'=>$mtime,
			'size'=>$obj->content_length,
			'ctime'=>-1,
		);
	}

	private function getTmpFile($path){
		$obj=$this->getObject($path);
		if(!is_null($obj)){
			$tmpFile=OCP\Files::tmpFile();
			$obj->save_to_filename($tmpFile);
			return $tmpFile;
		}else{
			return false;
		}
	}

	private function fromTmpFile($tmpFile,$path){
		$obj=$this->getObject($path);
		if(is_null($obj)){
			$obj=$this->createObject($path);
		}
		$obj->load_from_filename($tmpFile);
		$this->resetMTime($obj);
	}

	/**
	 * remove custom mtime metadata
	 * @param CF_Object obj
	 */
	private function resetMTime($obj){
		if(isset($obj->metadata['Mtime'])){
			$obj->metadata['Mtime']=-1;
			$obj->sync_metadata();
		}
	}
}
