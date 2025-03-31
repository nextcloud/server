<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2012 entreCables S.L. All rights reserved
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OC\Files\View;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFolder;
use OCP\Files\StorageNotAvailableException;
use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\INode;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin check user quota and deny creating files when they exceeds the quota.
 *
 * @author Sergio Cambra
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class QuotaPlugin extends \Sabre\DAV\ServerPlugin {
	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @param View $view
	 */
	public function __construct(
		private $view,
	) {
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
		$server->on('method:MKCOL', [$this, 'onCreateCollection'], 30);
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
	 * Check quota before creating directory
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool
	 * @throws InsufficientStorage
	 * @throws \Sabre\DAV\Exception\Forbidden
	 */
	public function onCreateCollection(RequestInterface $request, ResponseInterface $response): bool {
		try {
			$destinationPath = $this->server->calculateUri($request->getUrl());
			$quotaPath = $this->getPathForDestination($destinationPath);
		} catch (\Exception $e) {
			return true;
		}
		if ($quotaPath) {
			// MKCOL does not have a Content-Length header, so we can use
			// a fixed value for the quota check.
			return $this->checkQuota($quotaPath, 4096, true);
		}

		return true;
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
	public function checkQuota(string $path, $length = null, $isDir = false) {
		if ($length === null) {
			$length = $this->getLength();
		}

		if ($length) {
			[$parentPath, $newName] = \Sabre\Uri\split($path);
			if (is_null($parentPath)) {
				$parentPath = '';
			}
			$req = $this->server->httpRequest;

			// Strip any duplicate slashes
			$path = str_replace('//', '/', $path);

			$freeSpace = $this->getFreeSpace($path);
			if ($freeSpace >= 0 && $length > $freeSpace) {
				if ($isDir) {
					throw new InsufficientStorage("Insufficient space in $path. $freeSpace available. Cannot create directory");
				}

				throw new InsufficientStorage("Insufficient space in $path, $length required, $freeSpace available");
			}
		}

		return true;
	}

	public function getLength() {
		$req = $this->server->httpRequest;
		$length = $req->getHeader('X-Expected-Entity-Length');
		if (!is_numeric($length)) {
			$length = $req->getHeader('Content-Length');
			$length = is_numeric($length) ? $length : null;
		}

		$ocLength = $req->getHeader('OC-Total-Length');
		if (!is_numeric($ocLength)) {
			return $length;
		}
		if (!is_numeric($length)) {
			return $ocLength;
		}
		return max($length, $ocLength);
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
