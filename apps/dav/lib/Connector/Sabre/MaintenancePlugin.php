<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Exception\ServerMaintenanceMode;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Util;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\ServerPlugin;

class MaintenancePlugin extends ServerPlugin {

	/** @var IConfig */
	private $config;

	/** @var \OCP\IL10N */
	private $l10n;

	/**
	 * Reference to main server object
	 *
	 * @var Server
	 */
	private $server;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config, IL10N $l10n) {
		$this->config = $config;
		$this->l10n = \OC::$server->getL10N('dav');
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
		$this->server->on('beforeMethod:*', [$this, 'checkMaintenanceMode'], 1);
	}

	/**
	 * This method is called before any HTTP method and returns http status code 503
	 * in case the system is in maintenance mode.
	 *
	 * @throws ServiceUnavailable
	 * @return bool
	 */
	public function checkMaintenanceMode() {
		if ($this->config->getSystemValueBool('maintenance')) {
			throw new ServerMaintenanceMode($this->l10n->t('System is in maintenance mode.'));
		}
		if (Util::needUpgrade()) {
			throw new ServerMaintenanceMode($this->l10n->t('Upgrade needed'));
		}

		return true;
	}
}
