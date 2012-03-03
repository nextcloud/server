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
	static private $providers=array();
	
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
		
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		
		OC_Log::write('user_migrate','App data export started for user: '.$uid,OC_Log::INFO);
		
		foreach(self::$providers as $provider){
			
			OC_Log::write('user_migrate','Getting app data for app:'.$provider->appid,OC_Log::INFO);
			$app = $doc->createElement('app');
			$app = $doc->appendChild($app);
			$app->setAttribute('id',$provider->appid);
			// Append app info
			$appinfo = $doc->importNode( self::appInfoXML( $provider->appid )->documentElement, true );
			$app->appendChild( $appinfo );
			
			$appdata = $doc->createElement('appdata');
			$appdata = $app->appendChild($appdata);
			// Add the app data
			$appdatanode = $doc->importNode( $provider->export($uid)->documentElement, true );
			$appdata->appendChild( $appdatanode );

		}

		return $doc->saveXML();
	}
	
	/**
	* generates the app info xml
	* @param string appid
	* @return string xml app info
	*/
	public static function appInfoXML($appid){
		
		$info = OC_App::getAppInfo($appid);
		
		$doc = new DOMDocument();
		$appinfo = $doc->createElement('appinfo');
		$appinfo = $doc->appendChild($appinfo);
		$version = $doc->createElement('version');
		$appinfo->appendChild($version);
		$versionval = $doc->createTextNode($info['version']);
		$version->appendChild($versionval);
		
		return $doc;	
	}
}
