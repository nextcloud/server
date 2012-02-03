<?php
/**
 * ownCloud
 *
 * @author Tom Needham
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


/**
 * provides an interface to all search providers
 */
class OC_Migrate{
	static public $providers=array();
	
	/**
	 * register a new migration provider
	 * @param OC_Migrate_Provider $provider
	 */
	public static function registerProvider($provider){
		self::$providers[]=$provider;
	}
	
	/**
	 * export app data for a user
	 * @param string userid
	 * @return string xml of app data
	 */
	public static function export($uid){
		$xml = '';
		foreach(self::$providers as $provider){
			$xml .= '<app>';
			$xml .= self::appInfoXML($provider->$appid);
			$xml .= $provider->export($uid));
			$xml .= '</app>';
		}
		return $xml;
	}
	
	/**
	* generates the app info xml
	* @param string appid
	* @return string xml app info
	*/
	public static function appInfoXML($appid){
		$info = OC_App::getAppInfo($appid);
		$xml = '<appinfo>';
		$zml .= 'INFO HERE';
		$xml .= '</appinfo>';
		return $xml;	
	}
}
