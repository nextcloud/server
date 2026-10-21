<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2012 entreCables S.L. All rights reserved
 * SPDX-FileContributor: Sergio Cambra
 * SPDX-License-Identifier: AGPL-3.0-only AND BSD-3-Clause
 */

namespace OCA\DAV\Connector\Sabre;

use Exception;
use OC\Files\View;
use OCA\DAV\Upload\UploadFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\StorageNotAvailableException;
use OCP\Util;
use RuntimeException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\IFile;
use Sabre\DAV\INode;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin enforces user storage quotas by preventing file operations that would
 * exceed the user’s allotted quota.
 *
 * @property-read View $view The Nextcloud file view for quota operations.
 */
final class QuotaPlugin extends ServerPlugin {
	private ?\Sabre\DAV\Server $server = null;

	public function __construct(
		private readonly View $view,
	) {
	}

	/**
	 * Initializes the quota plugin and subscribes to relevant Sabre\DAV server events.
	 *
	 * @link https://sabre.io/dav/writing-plugins/#events
	 */
	#[\Override]
	public function initialize(\Sabre\DAV\Server $server): void {
		$this->server = $server;

		// Register event handlers for quota checks on various file operations
		$server->on('beforeWriteContent', $this->beforeWriteContent(...), 10);
		$server->on('beforeCreateFile', $this->beforeCreateFile(...), 10);
		$server->on('method:MKCOL', $this->onCreateCollection(...), 30);
		$server->on('beforeMove', $this->beforeMove(...), 10);
		$server->on('beforeCopy', $this->beforeCopy(...), 10);
	}

	private function getServer(): \Sabre\DAV\Server {
		if (!$this->server instanceof \Sabre\DAV\Server) {
			throw new RuntimeException('DAV Server not initialized');
		}

		return $this->server;
	}

	/**
	 * Checks quota before creating a new file.
	 * For chunked uploads (with 'Destination' and 'OC-Total-Length'), checks quota for the destination folder.
	 * Otherwise, checks quota for the parent node plus the new filename.
	 *
	 * @param string $uri Target file URI (unused).
	 * @param resource $data The data to write (unused).
	 * @param INode $parent Parent Sabre node.
	 * @param bool $modified Whether the node is modified (unused).
	 * @return bool True if quota is sufficient, otherwise throws InsufficientStorage.
	 * @throws Forbidden
	 * @throws InsufficientStorage
	 * @throws Exception
	 */
	public function beforeCreateFile(string $uri, $data, INode $parent, bool $modified): bool {
		$request = $this->getServer()->httpRequest;

		// Check quota during chunked uploads
		$destinationUri = $request->getHeader('Destination');
		if ($parent instanceof UploadFolder && $destinationUri !== null) {
			$destinationPath = $this->getServer()->calculateUri($destinationUri);
			$quotaPath = $this->getPathForDestination($destinationPath);

			return $this->checkQuota($quotaPath, $this->getLength());
		}

		if (!$parent instanceof Node) {
			// No quota check for non-Node parents
			return true;
		}

		$filePath = $parent->getPath() . '/' . basename($uri);
		return $this->checkQuota($filePath, $this->getLength());
	}

	/**
	 * Checks quota before creating a new collection (directory) via MKCOL.
	 * Assumes a fixed size (4096 bytes) for quota check as MKCOL lacks a Content-Length header.
	 *
	 * @param RequestInterface $request The HTTP request for the MKCOL operation.
	 * @param ResponseInterface $response The HTTP response object.
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage or \Sabre\DAV\Exception\Forbidden (?).
	 * @throws InsufficientStorage
	 * @throws InvalidPathException
	 * @throws ServiceUnavailable
	 * @throws NotFound
	 * @throws Forbidden
	 */
	public function onCreateCollection(RequestInterface $request, ResponseInterface $response): bool {
		$destinationPath = $this->getServer()->calculateUri($request->getUrl());
		try {
			$collectionPath = $this->getPathForDestination($destinationPath);
		} catch (Exception) {
			// The parent might not be a node, so we can't check any quotas.
			return true;
		}

		// Default directory size for quota check since MKCOL doesn't specify one
		return $this->checkQuota($collectionPath, 4096, true);
	}

	/**
	 * Checks quota before writing content to a node.
	 *
	 * @param string $uri Target file URI (unused).
	 * @param INode $node Sabre node to which content will be written.
	 * @param resource $data Content data (unused).
	 * @param bool $modified Whether the node is modified (unused).
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage.
	 * @throws InsufficientStorage
	 * @throws InvalidPathException
	 * @throws ServiceUnavailable
	 */
	public function beforeWriteContent(string $uri, INode $node, $data, bool $modified): bool {
		if (!$node instanceof Node) {
			// No quota check for non-Node objects
			return true;
		}

		return $this->checkQuota($node->getPath(), (float)$this->getLength() - (float)$node->getSize());
	}

	/**
	 * Checks quota before moving a FutureFile node to a new destination.
	 *
	 * @param string $sourcePath Path to the source node.
	 * @param string $destinationPath Path where the node will be moved to.
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage.
	 * @throws InsufficientStorage
	 * @throws InvalidPathException
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 */
	public function beforeMove(string $sourcePath, string $destinationPath): bool {
		$sourceNode = $this->getServer()->tree->getNodeForPath($sourcePath);
		if (!$sourceNode instanceof IFile) {
			return true;
		}

		$quotaPath = $this->getPathForDestination($destinationPath);

		if ($this->getServer()->tree->nodeExists($destinationPath)) {
			$targetNode = $this->getServer()->tree->getNodeForPath($destinationPath);
			if ($targetNode instanceof IFile) {
				return $this->checkQuota($quotaPath, $sourceNode->getSize() - $targetNode->getSize());
			}
		}

		return $this->checkQuota($quotaPath, $sourceNode->getSize());
	}

	/**
	 * Checks quota before allowing a file copy operation.
	 *
	 * @param string $sourcePath Path to the source node.
	 * @param string $destinationPath Path where the node will be copied to.
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage.
	 * @throws InsufficientStorage
	 * @throws InvalidPathException
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 */
	public function beforeCopy(string $sourcePath, string $destinationPath): bool {
		$sourceNode = $this->getServer()->tree->getNodeForPath($sourcePath);
		if (!$sourceNode instanceof Node) {
			return true;
		}

		$quotaPath = $this->getPathForDestination($destinationPath);

		if ($this->getServer()->tree->nodeExists($destinationPath)) {
			$targetNode = $this->getServer()->tree->getNodeForPath($destinationPath);
			if ($targetNode instanceof IFile) {
				return $this->checkQuota($quotaPath, (float)$sourceNode->getSize() - (float)$targetNode->getSize());
			}
		}

		return $this->checkQuota($quotaPath, $sourceNode->getSize());
	}

	/**
	 * Resolves the path for quota checking, given a destination path.
	 *
	 * If the destination node exists, returns its internal path.
	 * If it does not exist, returns the internal path of its parent node.
	 * Throws an exception if the relevant node is not a valid Node instance.
	 *
	 * @param string $destinationPath Destination path within the virtual file tree.
	 * @return string Internal path to use for quota checking.
	 * @throws NotFound
	 */
	private function getPathForDestination(string $destinationPath): string {
		// If the node exists, return its actual path
		if ($this->getServer()->tree->nodeExists($destinationPath)) {
			$destinationNode = $this->getServer()->tree->getNodeForPath($destinationPath);
			if (!$destinationNode instanceof Node) {
				throw new RuntimeException("Destination node at '" . $destinationPath . "' is not a valid Node instance.");
			}

			return $destinationNode->getPath();
		}

		// Otherwise, use the parent directory's path
		$parent = dirname($destinationPath);
		$parent = ($parent === '.') ? '' : $parent;

		$parentNode = $this->getServer()->tree->getNodeForPath($parent);
		if (!$parentNode instanceof Node) {
			throw new RuntimeException("Parent node at '" . $parent . "' is not a valid Node instance.");
		}

		return $parentNode->getPath();
	}

	/**
	 * Validates there is enough free space to store the file at the given path.
	 *
	 * Called before relevant HTTP DAV events (when there is an associated View).
	 *
	 * @see initialize() for specific events we're registered for.
	 *
	 * @internal
	 * @param string $path Path relative to the user's home.
	 * @param int|float $addedSize Added size to check for.
	 * @param bool $isDir Whether the target is a directory.
	 * @return bool True if there is enough space, otherwise throws.
	 * @throws InsufficientStorage
	 * @throws ServiceUnavailable
	 * @throws InvalidPathException
	 */
	public function checkQuota(string $path, int|float $addedSize, bool $isDir = false): bool {
		if ($addedSize <= 0) {
			return true;
		}

		$normalizedPath = str_replace('//', '/', $path);
		$freeSpace = $this->getFreeSpace($normalizedPath);

		// Explicitly handle unknown/invalid free space
		if ($freeSpace === false || $freeSpace < 0) {
			// You might log here; currently allows the operation
			return true;
		}

		if ($addedSize > $freeSpace) {
			$msg = $isDir
				? "Insufficient space in $normalizedPath. Cannot create directory"
				: "Insufficient space in $normalizedPath";
			throw new InsufficientStorage($msg);
		}

		return true;
	}

	/**
	 * Returns the largest valid content length found in any of the following HTTP headers:
	 * - OC-Total-Length
	 * - X-Expected-Entity-Length
	 * - Content-Length
	 *
	 * Only numeric values are considered. If none of the headers contain a valid numeric value,
	 * returns zero.
	 *
	 * @internal
	 */
	public function getLength(): int|float {
		$request = $this->getServer()->httpRequest;

		$length = 0;
		foreach (['OC-Total-Length', 'X-Expected-Entity-Length', 'Content-Length'] as $header) {
			$value = $request->getHeader($header);
			if (is_numeric($value)) {
				$length = max($length, Util::numericToNumber($value));
			}
		}

		return $length;
	}

	/**
	 * Returns the available free space for the given URI.
	 *
	 * TODO: `false` can probably be dropped here, if not now when free_space is cleaned up.
	 *
	 * @param string $uri The resource URI whose free space is being queried.
	 * @return int|float|false The amount of free space in bytes,
	 * @throws ServiceUnavailable If the underlying storage is not available.
	 * @throws InvalidPathException
	 */
	private function getFreeSpace(string $uri): int|float|false {
		try {
			return $this->view->free_space(ltrim($uri, '/'));
		} catch (StorageNotAvailableException $storageNotAvailableException) {
			throw new ServiceUnavailable($storageNotAvailableException->getMessage(), $storageNotAvailableException->getCode(), $storageNotAvailableException);
		}
	}
}
