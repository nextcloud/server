<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('smb4php/smb.php');

class OC_FileStorage_SMB extends OC_FileStorage_StreamWrapper{
	private $password;
	private $user;
	private $host;
	private $root;
	private $share;

	private static $tempFiles=array();

	public function __construct($params){
		$this->host=$params['host'];
		$this->user=$params['user'];
		$this->password=$params['password'];
		$this->share=$params['share'];
		$this->root=isset($params['root'])?$params['root']:'/';
		if(substr($this->root,-1,1)!='/'){
			$this->root.='/';
		}
		if(substr($this->root,0,1)!='/'){
			$this->root='/'.$this->root;
		}
		if(substr($this->share,0,1)!='/'){
			$this->share='/'.$this->share;
		}
		if(substr($this->share,-1,1)=='/'){
			$this->share=substr($this->share,0,-1);
		}

		//create the root folder if necesary
		if(!$this->is_dir('')){
			$this->mkdir('');
		}
	}

	public function constructUrl($path){
		if(substr($path,-1)=='/'){
			$path=substr($path,0,-1);
		}
		return 'smb://'.$this->user.':'.$this->password.'@'.$this->host.$this->share.$this->root.$path;
	}

	public function stat($path){
		if(!$path and $this->root=='/'){//mtime doesn't work for shares
			$mtime=$this->shareMTime();
			$stat=stat($this->constructUrl($path));
			$stat['mtime']=$mtime;
			return $stat;
		}else{
			return stat($this->constructUrl($path));
		}
	}

	public function filetype($path){
		return (bool)@$this->opendir($path);//using opendir causes the same amount of requests and caches the content of the folder in one go
	}

	/**
	 * check if a file or folder has been updated since $time
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path,$time){
		if(!$path and $this->root=='/'){
			//mtime doesn't work for shares, but giving the nature of the backend, doing a full update is still just fast enough
			return true;
		}else{
			$actualTime=$this->filemtime($path);
			return $actualTime>$time;
		}
	}

	/**
	 * get the best guess for the modification time of the share
	 */
	private function shareMTime(){
		$dh=$this->opendir('');
		$lastCtime=0;
		while($file=readdir($dh)){
			if($file!='.' and $file!='..'){
				$ctime=$this->filemtime($file);
				if($ctime>$lastCtime){
					$lastCtime=$ctime;
				}
			}
		}
		return $lastCtime;
	}
}
