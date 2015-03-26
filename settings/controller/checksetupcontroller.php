<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;
use OC_Util;

/**
 * @package OC\Settings\Controller
 */
class CheckSetupController extends Controller {
	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;
	/** @var \OC_Util */
	private $util;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IClientService $clientService
	 * @param \OC_Util $util
	 */
	public function __construct($AppName,
								IRequest $request,
								IConfig $config,
								IClientService $clientService,
								\OC_Util $util) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->clientService = $clientService;
		$this->util = $util;
	}

	/**
	 * Checks if the ownCloud server can connect to the internet using HTTPS and HTTP
	 * @return bool
	 */
	private function isInternetConnectionWorking() {
		if ($this->config->getSystemValue('has_internet_connection', true) === false) {
			return false;
		}

		try {
			$client = $this->clientService->newClient();
			$client->get('https://www.owncloud.org/');
			$client->get('http://www.owncloud.org/');
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Checks whether a local memcache is installed or not
	 * @return bool
	 */
	private function isMemcacheConfigured() {
		return $this->config->getSystemValue('memcache.local', null) !== null;
	}

	/**
	 * @return DataResponse
	 */
	public function check() {
		return new DataResponse(
			[
				'serverHasInternetConnection' => $this->isInternetConnectionWorking(),
				'dataDirectoryProtected' => $this->util->isHtaccessWorking($this->config),
				'isMemcacheConfigured' => $this->isMemcacheConfigured(),
			]
		);
	}
}
