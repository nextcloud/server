<?php

/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL3
 */

class OC_Connector_Sabre_ExceptionLoggerPlugin extends Sabre_DAV_ServerPlugin
{
	private $appName;

	/**
	 * @param string $loggerAppName app name to use when logging
	 */
	public function __construct($loggerAppName = 'webdav') {
		$this->appName = $loggerAppName;
	}

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

		$server->subscribeEvent('exception', array($this, 'logException'), 10);
	}

	/**
	 * Log exception
	 *
	 * @internal param Exception $e exception
	 */
	public function logException($e) {
		$exceptionClass = get_class($e);
		if ($exceptionClass !== 'Sabre_DAV_Exception_NotAuthenticated') {
			\OCP\Util::logException($this->appName, $e);
		}
	}
}
