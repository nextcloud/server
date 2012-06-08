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
}
