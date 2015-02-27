<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2012 Robin Appelman icewind@owncloud.com
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

/**
 * user backend using http auth requests
 */
class OC_User_HTTP extends OC_User_Backend implements \OCP\IUserBackend {
	/**
	 * split http://user@host/path into a user and url part
	 * @param string $url
	 * @return array
	 */
	private function parseUrl($url) {
		$parts=parse_url($url);
		$url=$parts['scheme'].'://'.$parts['host'];
		if(isset($parts['port'])) {
			$url.=':'.$parts['port'];
		}
		$url.=$parts['path'];
		if(isset($parts['query'])) {
			$url.='?'.$parts['query'];
		}
		return array($parts['user'], $url);

	}

	/**
	 * check if an url is a valid login
	 * @param string $url
	 * @return boolean
	 */
	private function matchUrl($url) {
		return ! is_null(parse_url($url, PHP_URL_USER));
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		if(!$this->matchUrl($uid)) {
			return false;
		}
		list($user, $url)=$this->parseUrl($uid);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);
		curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);

		curl_exec($ch);

		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if($status === 200) {
			return $uid;
		}

		return false;
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		return $this->matchUrl($uid);
	}

	/**
	* get the user's home directory
	* @param string $uid the username
	* @return string|false
	*/
	public function getHome($uid) {
		if($this->userExists($uid)) {
			return OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" ) . '/' . $uid;
		}else{
			return false;
		}
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'HTTP';
	}
}
