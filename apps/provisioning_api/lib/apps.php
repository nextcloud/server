<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Provisioning_API;

use \OC_OCS_Result;
use \OC_App;

class Apps {

	public static function getApps($parameters){
		$apps = OC_App::listAllApps();
		$list = array();
		foreach($apps as $app) {
			$list[] = $app['id'];
		}
		$filter = isset($_GET['filter']) ? $_GET['filter'] : false;
		if($filter){
			switch($filter){
				case 'enabled':
					return new OC_OCS_Result(array('apps' => \OC_App::getEnabledApps()));
					break;
				case 'disabled':
					$enabled = OC_App::getEnabledApps();
					return new OC_OCS_Result(array('apps' => array_diff($list, $enabled)));
					break;
				default:
					// Invalid filter variable
					return new OC_OCS_Result(null, 101);
					break;
			}

		} else {
			return new OC_OCS_Result(array('apps' => $list));
		}
	}

	public static function getAppInfo($parameters){
		$app = $parameters['appid'];
		$info = OC_App::getAppInfo($app);
		if(!is_null($info)) {
			return new OC_OCS_Result(OC_App::getAppInfo($app));
		} else {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_NOT_FOUND, 'The request app was not found');
		}
	}

	public static function enable($parameters){
		$app = $parameters['appid'];
		OC_App::enable($app);
		return new OC_OCS_Result(null, 100);
	}

	public static function disable($parameters){
		$app = $parameters['appid'];
		OC_App::disable($app);
		return new OC_OCS_Result(null, 100);
	}

}
