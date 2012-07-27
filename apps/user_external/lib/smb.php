<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_User_SMB extends OC_User_Backend{
	private $host;

	const smbclient='smbclient';
	const loginError='NT_STATUS_LOGON_FAILURE';
	
	public function __construct($host){
		$this->host=$host;
	}
	
	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password){
		$uidEscaped=escapeshellarg($uid);
		$password=escapeshellarg($password);
		$result=array();
		$command=self::smbclient.' //'.$this->host.'/dummy -U'.$uidEscaped.'%'.$password;
		$result=exec($command,$result);
		if(substr($result,-strlen(self::loginError))==self::loginError){
			return false;
		}else{
			return $uid;
		}
	}
	
	public function userExists($uid){
		return true;
	}
}