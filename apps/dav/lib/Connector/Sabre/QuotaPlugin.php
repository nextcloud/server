<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author scambra <sergio@entrecables.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFolder;
use OCP\Files\StorageNotAvailableException;
use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\INode;

/**
 * This plugin check user quota and deny creating files when they exceeds the quota.
 *
 * @author Sergio Cambra
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class QuotaPlugin extends \Sabre\DAV\ServerPlugin {
	/** @var \OC\Files\View */
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

		$server->on('beforeWriteContent', [$this, 'beforeWriteContent'], 10);
		$server->on('beforeCreateFile', [$this, 'beforeCreateFile'], 10);
		$server->on('beforeMove', [$this, 'beforeMove'], 10);
		$server->on('beforeCopy', [$this, 'beforeCopy'], 10);
	}

	/**
	 * Check quota before creating file
	 *
	 * @param string $uri target file URI
	 * @param resource $data data
	 * @param INode $parent Sabre Node
	 * @param bool $modified modified
	 */
	public function beforeCreateFile($uri, $data, INode $parent, $modified) {
		$request = $this->server->httpRequest;
		if ($parent instanceof UploadFolder && $request->getHeader('Destination')) {
			// If chunked upload and Total-Length header is set, use that
			// value for quota check. This allows us to also check quota while
			// uploading chunks and not only when the file is assembled.
			$length = $request->getHeader('OC-Total-Length');
			$destinationPath = $this->server->calculateUri($request->getHeader('Destination'));
			$quotaPath = $this->getPathForDestination($destinationPath);
			if ($quotaPath && is_numeric($length)) {
				return $this->checkQuota($quotaPath, (int)$length);
			}
		}

		if (!$parent instanceof Node) {
			return;
		}

		return $this->checkQuota($parent->getPath() . '/' . basename($uri));
	}

	/**
	 * Check quota before writing content
	 *
	 * @param string $uri target file URI
	 * @param INode $node Sabre Node
	 * @param resource $data data
	 * @param bool $modified modified
	 */
	public function beforeWriteContent($uri, INode $node, $data, $modified) {
		if (!$node instanceof Node) {
			return;
		}

		return $this->checkQuota($node->getPath());
	}

	/**
	 * Check if we're moving a FutureFile in which case we need to check
	 * the quota on the target destination.
	 */
	public function beforeMove(string $sourcePath, string $destinationPath): bool {
		$sourceNode = $this->server->tree->getNodeForPath($sourcePath);
		if (!$sourceNode instanceof FutureFile) {
			return true;
		}

		try {
			// The final path is not known yet, we check the quota on the parent
			$path = $this->getPathForDestination($destinationPath);
		} catch (\Exception $e) {
			return true;
		}

		return $this->checkQuota($path, $sourceNode->getSize());
	}

	/**
	 * Check quota on the target destination before a copy.
	 */
	public function beforeCopy(string $sourcePath, string $destinationPath): bool {
		$sourceNode = $this->server->tree->getNodeForPath($sourcePath);
		if (!$sourceNode instanceof Node) {
			return true;
		}

		try {
			$path = $this->getPathForDestination($destinationPath);
		} catch (\Exception $e) {
			return true;
		}

		return $this->checkQuota($path, $sourceNode->getSize());
	}

	private function getPathForDestination(string $destinationPath): string {
		// get target node for proper path conversion
		if ($this->server->tree->nodeExists($destinationPath)) {
			$destinationNode = $this->server->tree->getNodeForPath($destinationPath);
			if (!$destinationNode instanceof Node) {
				throw new \Exception('Invalid destination node');
			}
			return $destinationNode->getPath();
		}

		$parent = dirname($destinationPath);
		if ($parent === '.') {
			$parent = '';
		}

		$parentNode = $this->server->tree->getNodeForPath($parent);
		if (!$parentNode instanceof Node) {
			throw new \Exception('Invalid destination node');
		}

		return $parentNode->getPath();
	}


	/**
	 * This method is called before any HTTP method and validates there is enough free space to store the file
	 *
	 * @param string $path relative to the users home
	 * @param int|float|null $length
	 * @throws InsufficientStorage
	 * @return bool
	 */
	public function checkQuota(string $path, $length = null) {
		if ($length === null) {
			$length = $this->getLength();
		}

		if ($length) {
			[$parentPath, $newName] = \Sabre\Uri\split($path);
			if (is_null($parentPath)) {
				$parentPath = '';
			}
			$req = $this->server->httpRequest;

			// If LEGACY chunked upload
			if ($req->getHeader('OC-Chunked')) {
				$info = \OC_FileChunking::decodeName($newName);
				$chunkHandler = $this->getFileChunking($info);
				// subtract the already uploaded size to see whether
				// there is still enough space for the remaining chunks
				$length -= $chunkHandler->getCurrentSize();
				// use target file name for free space check in case of shared files
				$path = rtrim($parentPath, '/') . '/' . $info['name'];
			}

			// Strip any duplicate slashes
			$path = str_replace('//', '/', $path);

			$freeSpace = $this->getFreeSpace($path);
			if ($freeSpace >= 0 && $length > $freeSpace) {
				// If LEGACY chunked upload, clean up
				if (isset($chunkHandler)) {
					$chunkHandler->cleanup();
				}
				throw new InsufficientStorage("Insufficient space in $path, $length required, $freeSpace available");
			}
		}

		return true;
	}

	public function getFileChunking($info) {
		// FIXME: need a factory for better mocking support
		return new \OC_FileChunking($info);
	}

	public function getLength() {
		$req = $this->server->httpRequest;
		$length = $req->getHeader('X-Expected-Entity-Length');
		if (!is_numeric($length)) {
			$length = $req->getHeader('Content-Length');
			$length = is_numeric($length) ? $length : null;
		}

		$ocLength = $req->getHeader('OC-Total-Length');
		if (is_numeric($length) && is_numeric($ocLength)) {
			return max($length, $ocLength);
		}

		return $length;
	}

	/**
	 * @param string $uri
	 * @return mixed
	 * @throws ServiceUnavailable
	 */
	public function getFreeSpace($uri) {
		try {
			$freeSpace = $this->view->free_space(ltrim($uri, '/'));
			return $freeSpace;
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($e->getMessage());
		}
	}
}
