<?php
/**
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author scambra <sergio@entrecables.com>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
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

/**
 * This plugin check user quota and deny creating files when they exceeds the quota.
 *
 * @author Sergio Cambra
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class QuotaPlugin extends \Sabre\DAV\ServerPlugin {

	/**
	 * @var \OC\Files\View
	 */
	private $view;

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @param \OC\Files\View $view
	 */
	public function __construct($view) {
		$this->view = $view;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the requires event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {

		$this->server = $server;

		$server->on('beforeWriteContent', array($this, 'checkQuota'), 10);
		$server->on('beforeCreateFile', array($this, 'checkQuota'), 10);
	}

	/**
	 * This method is called before any HTTP method and validates there is enough free space to store the file
	 *
	 * @param string $uri
	 * @param null $data
	 * @throws \Sabre\DAV\Exception\InsufficientStorage
	 * @return bool
	 */
	public function checkQuota($uri, $data = null) {
		$length = $this->getLength();
		if ($length) {
			if (substr($uri, 0, 1) !== '/') {
				$uri = '/' . $uri;
			}
			list($parentUri, $newName) = \Sabre\HTTP\URLUtil::splitPath($uri);
			if(is_null($parentUri)) {
				$parentUri = '';
			}
			$req = $this->server->httpRequest;
			if ($req->getHeader('OC-Chunked')) {
				$info = \OC_FileChunking::decodeName($newName);
				$chunkHandler = new \OC_FileChunking($info);
				// subtract the already uploaded size to see whether
				// there is still enough space for the remaining chunks
				$length -= $chunkHandler->getCurrentSize();
			}
			$freeSpace = $this->getFreeSpace($parentUri);
			if ($freeSpace !== \OCP\Files\FileInfo::SPACE_UNKNOWN && $length > $freeSpace) {
				if (isset($chunkHandler)) {
					$chunkHandler->cleanup();
				}
				throw new \Sabre\DAV\Exception\InsufficientStorage();
			}
		}
		return true;
	}

	public function getLength() {
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
	 * @param string $parentUri
	 * @return mixed
	 */
	public function getFreeSpace($parentUri) {
		try {
			$freeSpace = $this->view->free_space($parentUri);
			return $freeSpace;
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			throw new \Sabre\DAV\Exception\ServiceUnavailable($e->getMessage());
		}
	}
}
