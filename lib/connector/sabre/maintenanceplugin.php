<?php

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL3
 */

require 'ServiceUnavailable.php';

class OC_Connector_Sabre_MaintenancePlugin extends Sabre_DAV_ServerPlugin
{

	/**
	 * Reference to main server object
	 *
	 * @var Sabre_DAV_Server
	 */
	private $server;

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre_DAV_Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Sabre_DAV_Server $server
	 * @return void
	 */
	public function initialize(Sabre_DAV_Server $server) {

		$this->server = $server;
		$this->server->subscribeEvent('beforeMethod', array($this, 'checkMaintenanceMode'), 10);
	}

	/**
	 * This method is called before any HTTP method and returns http status code 503
	 * in case the system is in maintenance mode.
	 *
	 * @throws Sabre_DAV_Exception_ServiceUnavailable
	 * @internal param string $method
	 * @return bool
	 */
	public function checkMaintenanceMode() {
		if (OC_Config::getValue('maintenance', false)) {
			throw new Sabre_DAV_Exception_ServiceUnavailable();
		}

		return true;
	}
}
