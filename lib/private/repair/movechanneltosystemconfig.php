<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OCP\IConfig;

/**
 * Class MoveChannelToSystemConfig moves the defined OC_Channel in the app config
 * to the system config to be compatible with the Nextcloud updater.
 *
 * @package OC\Repair
 */
class MoveChannelToSystemConfig extends BasicEmitter implements \OC\RepairStep {
	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName() {
		return 'Moves the stored release channel to the config file';
	}

	public function run() {
		$channel = $this->config->getAppValue('core', 'OC_Channel', '');
		if($channel !== '') {
			$this->config->setSystemValue('updater.release.channel', $channel);
			$this->config->deleteAppValue('core', 'OC_Channel');
		}
	}
}
