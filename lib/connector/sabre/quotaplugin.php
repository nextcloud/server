<?php

/**
 * This plugin check user quota and deny creating files when they exceeds the quota.
 *
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 * @author Sergio Cambra
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class OC_Connector_Sabre_QuotaPlugin extends Sabre_DAV_ServerPlugin {

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
		* This method should set up the requires event subscriptions.
		*
		* @param Sabre_DAV_Server $server
		* @return void
		*/
	public function initialize(Sabre_DAV_Server $server) {

			$this->server = $server;
			$this->server->subscribeEvent('beforeWriteContent', array($this, 'checkQuota'), 10);
			$this->server->subscribeEvent('beforeCreateFile', array($this, 'checkQuota'), 10);

	}

	/**
		* This method is called before any HTTP method and forces users to be authenticated
		*
		* @param string $method
		* @throws Sabre_DAV_Exception
		* @return bool
		*/
	public function checkQuota($uri, $data = null) {
		$expected = $this->server->httpRequest->getHeader('X-Expected-Entity-Length');
		$length = $expected ? $expected : $this->server->httpRequest->getHeader('Content-Length');
		if ($length) {
			if (substr($uri, 0, 1)!=='/') {
				$uri='/'.$uri;
			}
			list($parentUri, $newName) = Sabre_DAV_URLUtil::splitPath($uri);
			$freeSpace = \OC\Files\Filesystem::free_space($parentUri);
			if ($freeSpace !== \OC\Files\FREE_SPACE_UNKNOWN && $length > $freeSpace) {
				throw new Sabre_DAV_Exception_InsufficientStorage();
			}
		}
		return true;
	}
}
