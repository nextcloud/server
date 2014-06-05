<?php

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL3
 */

class OC_Connector_Sabre_MaintenancePlugin extends \Sabre\DAV\ServerPlugin
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
		$this->server->subscribeEvent('beforeMethod', array($this, 'checkMaintenanceMode'), 10);
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
		if (OC_Config::getValue('maintenance', false)) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable();
		}
		if (OC::checkUpgrade(false)) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable('Upgrade needed');
		}

		return true;
	}
}
