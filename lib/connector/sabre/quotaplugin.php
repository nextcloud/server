<?php

/**
 * This plugin check user quota and deny creating files when they exceeds the quota.
 *
 * @author Sergio Cambra
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
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
	 * is kept public to allow overwrite for unit testing
	 *
	 * @var \OC\Files\View
	 */
	public $fileView;

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

		$server->subscribeEvent('beforeWriteContent', array($this, 'checkQuota'), 10);
		$server->subscribeEvent('beforeCreateFile', array($this, 'checkQuota'), 10);
	}

	/**
	 * This method is called before any HTTP method and validates there is enough free space to store the file
	 *
	 * @param string $method
	 * @throws Sabre_DAV_Exception
	 * @return bool
	 */
	public function checkQuota($uri, $data = null) {
		$length = $this->getLength();
		if ($length) {
			if (substr($uri, 0, 1)!=='/') {
				$uri='/'.$uri;
			}
			list($parentUri, $newName) = Sabre_DAV_URLUtil::splitPath($uri);
			$freeSpace = $this->getFreeSpace($parentUri);
			if ($freeSpace !== \OC\Files\SPACE_UNKNOWN && $length > $freeSpace) {
				throw new Sabre_DAV_Exception_InsufficientStorage();
			}
		}
		return true;
	}

	public function getLength()
	{
		$req = $this->server->httpRequest;
		$length = $req->getHeader('X-Expected-Entity-Length');
		if (!$length) {
			$length = $req->getHeader('Content-Length');
		}

		$ocLength = $req->getHeader('OC-Total-Length');
		if ($length && $ocLength) {
			return max($length, $ocLength);
		}

		return $length;
	}

	/**
	 * @param $parentUri
	 * @return mixed
	 */
	public function getFreeSpace($parentUri)
	{
		if (is_null($this->fileView)) {
			// initialize fileView
			$this->fileView = \OC\Files\Filesystem::getView();
		}

		$freeSpace = $this->fileView->free_space($parentUri);
		return $freeSpace;
	}
}
