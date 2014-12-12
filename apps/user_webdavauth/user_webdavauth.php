<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 *
 */

class OC_USER_WEBDAVAUTH extends OC_User_Backend implements \OCP\IUserBackend {
	protected $webdavauth_url;

	public function __construct() {
		$this->webdavauth_url = OC_Config::getValue( "user_webdavauth_url" );
	}

	public function deleteUser($uid) {
		// Can't delete user
		OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to delete users from web frontend using WebDAV user backend', 3);
		return false;
	}

	public function setPassword ( $uid, $password ) {
		// We can't change user password
		OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to change password for users from web frontend using WebDAV user backend', 3);
		return false;
	}

	public function checkPassword( $uid, $password ) {
		$arr = explode('://', $this->webdavauth_url, 2);
		if( ! isset($arr) OR count($arr) !== 2) {
			OC_Log::write('OC_USER_WEBDAVAUTH', 'Invalid Url: "'.$this->webdavauth_url.'" ', 3);
			return false;
		}
		list($webdavauth_protocol, $webdavauth_url_path) = $arr;
		$url= $webdavauth_protocol.'://'.urlencode($uid).':'.urlencode($password).'@'.$webdavauth_url_path;
		$headers = get_headers($url);
		if($headers==false) {
			OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to connect to WebDAV Url: "'.$webdavauth_protocol.'://'.$webdavauth_url_path.'" ', 3);
			return false;

		}
		$returncode= substr($headers[0], 9, 3);

		if(substr($returncode, 0, 1) === '2') {
			return $uid;
		} else {
			return false;
		}

	}

	/*
	* we don´t know if a user exists without the password. so we have to return true all the time
	*/
	public function userExists( $uid ){
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return false;
	}

	/*
	* we don´t know the users so all we can do it return an empty array here
	*/
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		$returnArray = array();

		return $returnArray;
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'WebDAV';
	}
}
