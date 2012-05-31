<?php

/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2011 Robin Appelman icewind1991gmailc.om
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

require_once('class.openid.v3.php');

/**
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 */
class OC_USER_OPENID extends OC_User_Backend {
	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword( $uid, $password ){
		// Get identity from user and redirect browser to OpenID Server
		$openid = new SimpleOpenID;
		$openid->SetIdentity($uid);
		$openid->SetTrustRoot('http://' . OCP\Util::getServerHost());
		if ($openid->GetOpenIDServer()){
			$openid->SetApprovedURL('http://' . OCP\Util::getServerHost() . OC::$WEBROOT);      // Send Response from OpenID server to this script
			$openid->Redirect();     // This will redirect user to OpenID Server
			exit;
		}else{
			return false;
		}
		exit;
	}
	
	/**
	 * find the user that can be authenticated with an openid identity
	 */
	public static function findUserForIdentity($identity){
		$query=OCP\DB::prepare('SELECT userid FROM *PREFIX*preferences WHERE appid=? AND configkey=? AND configvalue=?');
		$result=$query->execute(array('user_openid','identity',$identity))->fetchAll();
		if(count($result)>0){
			return $result[0]['userid'];
		}else{
			return false;
		}
	}
}



?>
