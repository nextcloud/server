<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
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
namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OCP\IConfig;

class SharePropagation extends BasicEmitter implements \OC\RepairStep {
	/** @var  IConfig */
	private $config;

	/**
	 * SharePropagation constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName() {
		return 'Remove old share propagation app entries';
	}

	public function run() {
		$keys = $this->config->getAppKeys('files_sharing');

		foreach ($keys as $key) {
			if (is_numeric($key)) {
				$this->config->deleteAppValue('files_sharing', $key);
			}
		}
	}
}
