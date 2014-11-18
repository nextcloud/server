<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

use OCP\App\IManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ServerPlugin;

/**
 * Plugin to check if an app is enabled for the current user
 */
class AppEnabledPlugin extends ServerPlugin {

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var string
	 */
	private $app;

	/**
	 * @var \OCP\App\IManager
	 */
	private $appManager;

	/**
	 * @param string $app
	 * @param \OCP\App\IManager $appManager
	 */
	public function __construct($app, IManager $appManager) {
		$this->app = $app;
		$this->appManager = $appManager;
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
		$this->server->subscribeEvent('beforeMethod', array($this, 'checkAppEnabled'), 30);
	}

	/**
	 * This method is called before any HTTP after auth and checks if the user has access to the app
	 *
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @return bool
	 */
	public function checkAppEnabled() {
		if (!$this->appManager->isEnabledForUser($this->app)) {
			throw new Forbidden();
		}
	}
}
