<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\DAV\Connector\Sabre;

use OCP\IConfig;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\ServerPlugin;

class MaintenancePlugin extends ServerPlugin {

	/** @var IConfig */
	private $config;

	/**
	 * Reference to main server object
	 *
	 * @var Server
	 */
	private $server;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config = null) {
		$this->config = $config;
		if (is_null($config)) {
			$this->config = \OC::$server->getConfig();
		}
	}


	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;
		$this->server->on('beforeMethod', array($this, 'checkMaintenanceMode'), 1);
	}

	/**
	 * This method is called before any HTTP method and returns http status code 503
	 * in case the system is in maintenance mode.
	 *
	 * @throws ServiceUnavailable
	 * @return bool
	 */
	public function checkMaintenanceMode() {
		if ($this->config->getSystemValue('singleuser', false)) {
			throw new ServiceUnavailable('System in single user mode.');
		}
		if ($this->config->getSystemValue('maintenance', false)) {
			throw new ServiceUnavailable('System in maintenance mode.');
		}
		if (\OC::checkUpgrade(false)) {
			throw new ServiceUnavailable('Upgrade needed');
		}

		return true;
	}
}
