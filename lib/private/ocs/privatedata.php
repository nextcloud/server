<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @author Tom Needham
* @copyright 2012 Frank Karlitschek frank@owncloud.org
* @copyright 2012 Tom Needham tom@owncloud.com
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

class OC_OCS_Privatedata {

	public static function get($parameters) {
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		$result = OC_OCS::getData($user, $app, $key);
		$xml = array();
		foreach($result as $i=>$log) {
			$xml[$i]['key']=$log['key'];
			$xml[$i]['app']=$log['app'];
			$xml[$i]['value']=$log['value'];
		}
		return new OC_OCS_Result($xml);
		//TODO: replace 'privatedata' with 'attribute' once a new libattice has been released that works with it
	}

	public static function set($parameters) {
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		$value = OC_OCS::readData('post', 'value', 'text');
		if(OC_Preferences::setValue($user, $app, $key, $value)) {
			return new OC_OCS_Result(null, 100);
		}
	}

	public static function delete($parameters) {
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		if($key==="" or $app==="") {
			return new OC_OCS_Result(null, 101); //key and app are NOT optional here
		}
		if(OC_Preferences::deleteKey($user, $app, $key)) {
			return new OC_OCS_Result(null, 100);
		}
	}
}
