<?php

/**
* ownCloud - Provisioning API
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
* You should have received a copy of the GNU Lesser General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

class OC_Provisioning_API_Apps {
	
	public static function getApps($parameters){
		$filter = isset($_GET['filter']) ? $_GET['filter'] : false;
		if($filter){
			switch($filter){
				case 'enabled':
					return array('apps' => OC_App::getEnabledApps());
					break;
				case 'disabled':
					$apps = OC_App::getAllApps();
					$enabled = OC_App::getEnabledApps();
					return array('apps' => array_diff($apps, $enabled));
					break;
				default:
					// Invalid filter variable
					return 101;
					break;
			}
			
		} else {
			return array('apps' => OC_App::getAllApps());
		}
	}
	
	public static function getAppInfo($parameters){
		$app = $parameters['appid'];
		return OC_App::getAppInfo($app);
	}
	
	public static function enable($parameters){
		$app = $parameters['appid'];
		OC_App::enable($app);
		return 100;
	}
	
	public static function disable($parameters){
		$app = $parameters['appid'];
		OC_App::disable($app);
		return 100;
	}
	
}