<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_FileStorage_FTP extends OC_FileStorage_StreamWrapper{
	private $password;
	private $user;
	private $host;
	private $secure;
	private $root;

	private static $tempFiles=array();
	
	public function __construct($params){
		$this->host=$params['host'];
		$this->user=$params['user'];
		$this->password=$params['password'];
		$this->secure=isset($params['secure'])?(bool)$params['secure']:false;
		$this->root=isset($params['root'])?$params['root']:'/';
		if(!$this->root || $this->root[0]!='/'){
			$this->root='/'.$this->root;
		}
		//create the root folder if necesary
		if (!$this->is_dir('')) {
			$this->mkdir('');
		}
	}

	/**
	 * construct the ftp url
	 * @param string path
	 * @return string
	 */
	public function constructUrl($path){
		$url='ftp';
		if($this->secure){
			$url.='s';
		}
		$url.='://'.$this->user.':'.$this->password.'@'.$this->host.$this->root.$path;
		return $url;
	}
	public function fopen($path,$mode){
		switch($mode){
			case 'r':
			case 'rb':
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
				//these are supported by the wrapper
				$context = stream_context_create(array('ftp' => array('overwrite' => true)));
				return fopen($this->constructUrl($path),$mode,false,$context);
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				//emulate these
				if(strrpos($path,'.')!==false){
					$ext=substr($path,strrpos($path,'.'));
				}else{
					$ext='';
				}
				$tmpFile=OCP\Files::tmpFile($ext);
				OC_CloseStreamWrapper::$callBacks[$tmpFile]=array($this,'writeBack');
				if($this->file_exists($path)){
					$this->getFile($path,$tmpFile);
				}
				self::$tempFiles[$tmpFile]=$path;
				return fopen('close://'.$tmpFile,$mode);
		}
	}

	public function writeBack($tmpFile){
		if(isset(self::$tempFiles[$tmpFile])){
			$this->uploadFile($tmpFile,self::$tempFiles[$tmpFile]);
			unlink($tmpFile);
		}
	}
}
