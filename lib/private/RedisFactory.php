<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC;

use Predis\Client;

class RedisFactory {
	/** @var  Client */
	private $instance;

	/** @var  SystemConfig */
	private $systemConfig;

	/**
	 * RedisFactory constructor.
	 *
	 * @param SystemConfig $systemConfig
	 */
	public function __construct(SystemConfig $systemConfig) {
		$this->systemConfig = $systemConfig;
	}

	private function createInstance() {
		// TODO allow configuring a RedisArray, see https://github.com/nrk/predis#aggregate-connections
		$config = $this->systemConfig->getValue('redis', array());
		if (!isset($config['timeout'])) {
			$config['timeout'] = 0.0; // unlimited
		}

		if (isset($config['dbindex'])) {
			$config['database'] = $config['dbindex'];
		}

		$this->instance = new Client($config);
	}

	public function getInstance() {
		if (!$this->instance instanceof Client) {
			$this->createInstance();
		}
		return $this->instance;
	}
}
