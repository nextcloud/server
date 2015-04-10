<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Connector\Sabre;

class MaintenancePlugin extends \Sabre\DAV\ServerPlugin
{

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

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
		$this->server->on('beforeMethod', array($this, 'checkMaintenanceMode'), 10);
	}

	/**
	 * This method is called before any HTTP method and returns http status code 503
	 * in case the system is in maintenance mode.
	 *
	 * @throws \Sabre\DAV\Exception\ServiceUnavailable
	 * @internal param string $method
	 * @return bool
	 */
	public function checkMaintenanceMode() {
		if (\OC::$server->getSystemConfig()->getValue('singleuser', false)) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable();
		}
		if (\OC_Config::getValue('maintenance', false)) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable();
		}
		if (\OC::checkUpgrade(false)) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('Upgrade needed');
		}

		return true;
	}
}
