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

	private static $tempFiles=array();

	public function __construct($params){
		$this->host=$params['host'];
		$this->user=$params['user'];
		$this->password=$params['password'];
		$this->root=isset($params['root'])?$params['root']:'/';

		//create the root folder if necesary
		$this->mkdir('');
	}

	public function constructUrl($path){
		if(substr($path,-1)=='/'){
			$path=substr($path,0,-1);
		}
		return 'smb://'.$this->user.':'.$this->password.'@'.$this->host.$this->root.$path;
		
	}
}
