<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
 *
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

use OC\OCSClient;
use \OC_App;

class Apps {
	/** @var \OCP\App\IAppManager */
	private $appManager;
	/** @var OCSClient */
	private $ocsClient;

	/**
	 * @param \OCP\App\IAppManager $appManager
	 */
	public function __construct(\OCP\App\IAppManager $appManager,
								OCSClient $ocsClient) {
		$this->appManager = $appManager;
		$this->ocsClient = $ocsClient;
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function getApps($parameters) {
		$apps = OC_App::listAllApps(false, true, $this->ocsClient);
		$list = [];
		foreach($apps as $app) {
			$list[] = $app['id'];
		}
		$filter = isset($_GET['filter']) ? $_GET['filter'] : false;
		if($filter){
			switch($filter){
				case 'enabled':
					return new \OC\OCS\Result(array('apps' => \OC_App::getEnabledApps()));
					break;
				case 'disabled':
					$enabled = OC_App::getEnabledApps();
					return new \OC\OCS\Result(array('apps' => array_diff($list, $enabled)));
					break;
				default:
					// Invalid filter variable
					return new \OC\OCS\Result(null, 101);
					break;
			}

		} else {
			return new \OC\OCS\Result(array('apps' => $list));
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function getAppInfo($parameters) {
		$app = $parameters['appid'];
		$info = \OCP\App::getAppInfo($app);
		if(!is_null($info)) {
			return new \OC\OCS\Result(OC_App::getAppInfo($app));
		} else {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_NOT_FOUND, 'The request app was not found');
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function enable($parameters) {
		$app = $parameters['appid'];
		$this->appManager->enableApp($app);
		return new \OC\OCS\Result(null, 100);
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function disable($parameters) {
		$app = $parameters['appid'];
		$this->appManager->disableApp($app);
		return new \OC\OCS\Result(null, 100);
	}

}
