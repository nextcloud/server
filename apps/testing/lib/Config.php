<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Testing;

use OCP\IConfig;
use OCP\IRequest;

class Config {

	/** @var IConfig */
	private $config;

	/** @var IRequest */
	private $request;

	/**
	 * @param IConfig $config
	 * @param IRequest $request
	 */
	public function __construct(IConfig $config, IRequest $request) {
		$this->config = $config;
		$this->request = $request;
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function setAppValue($parameters) {
		$app = $parameters['appid'];
		$configKey = $parameters['configkey'];

		$value = $this->request->getParam('value');
		$this->config->setAppValue($app, $configKey, $value);

		return new \OC_OCS_Result();
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function deleteAppValue($parameters) {
		$app = $parameters['appid'];
		$configKey = $parameters['configkey'];

		$this->config->deleteAppValue($app, $configKey);

		return new \OC_OCS_Result();
	}
}
