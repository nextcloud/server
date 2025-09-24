<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2012 entreCables S.L. All rights reserved
 * SPDX-License-Identifier: AGPL-3.0-only
 */

/*
 * @author Sergio Cambra
 * @copyright Copyright (C) 2012 entreCables S.L. All rights reserved.
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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
 * This plugin enforces user storage quotas by preventing file operations that would
 * exceed the user’s allotted quota.
 *
 * @property-read View $view The Nextcloud file view for quota operations.
 */
class QuotaPlugin extends \Sabre\DAV\ServerPlugin {
	/**
	 * The Sabre\DAV server instance (set during initialize()).
	 *
	 * @var \Sabre\DAV\Server|null
	 */
	private ?\Sabre\DAV\Server $server = null;

	/**
	 * QuotaPlugin constructor.
	 *
	 * @param View $view The Nextcloud Files View instance.
	 */
	public function __construct(
		private View $view,
	) {
	}

	/**
	 * Initializes the quota plugin and subscribes to relevant Sabre\DAV server events.
	 *
	 * @link https://sabre.io/dav/writing-plugins/#events
	 *
	 * @param \Sabre\DAV\Server $server The Sabre\DAV server instance.
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server): void {
		$this->server = $server;

		// Register event handlers for quota checks on various file operations
		$server->on('beforeWriteContent', [$this, 'beforeWriteContent'], 10);
		$server->on('beforeCreateFile', [$this, 'beforeCreateFile'], 10);
		$server->on('method:MKCOL', [$this, 'onCreateCollection'], 30);
		$server->on('beforeMove', [$this, 'beforeMove'], 10);
		$server->on('beforeCopy', [$this, 'beforeCopy'], 10);
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
	 */
	public function beforeCreateFile(string $uri, $data, INode $parent, bool $modified): bool {
		$request = $this->server->httpRequest;

		// Check quota during chunked uploads
		if ($parent instanceof UploadFolder && $request->getHeader('Destination')) {
			$totalLength = $request->getHeader('OC-Total-Length');
			$destinationUri = $request->getHeader('Destination');
			$destinationPath = $this->server->calculateUri($destinationUri);
			$quotaPath = $this->getPathForDestination($destinationPath);

			if ($quotaPath && is_numeric($totalLength)) {
				return $this->checkQuota($quotaPath, (int)$totalLength);
			}
			// If quota cannot be checked, allow by default
			// NOTE: We can still check during assembly.
			return true;
		}

		if (!$parent instanceof Node) {
			// No quota check for non-Node parents
			return true;
		}

		$filePath = $parent->getPath() . '/' . basename($uri);
		return $this->checkQuota($filePath);
	}

	/**
	 * Checks quota before creating a new collection (directory) via MKCOL.
	 * Assumes a fixed size (4096 bytes) for quota check as MKCOL lacks a Content-Length header.
	 *
	 * @param RequestInterface $request The HTTP request for the MKCOL operation.
	 * @param ResponseInterface $response The HTTP response object.
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage or \Sabre\DAV\Exception\Forbidden (?).
	 */
	public function onCreateCollection(RequestInterface $request, ResponseInterface $response): bool {
		try {
			$destinationPath = $this->server->calculateUri($request->getUrl());
			$collectionPath = $this->getPathForDestination($destinationPath);
		} catch (\Exception $e) {
			// Optionally log: error_log('Quota check failed during onCreateCollection: ' . $e->getMessage());
			return true; // Quota cannot be checked, allow by default
		}
		if ($collectionPath) {
			// Default directory size for quota check since MKCOL doesn't specify one
			return $this->checkQuota($collectionPath, 4096, true);
		}

		return true; // No path to check, allow by default
	}

	/**
	 * Checks quota before writing content to a node.
	 *
	 * @param string $uri Target file URI (unused).
	 * @param INode $node Sabre node to which content will be written.
	 * @param resource $data Content data (unused).
	 * @param bool $modified Whether the node is modified (unused).
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage.
	 */
	public function beforeWriteContent(string $uri, INode $node, $data, bool $modified): bool {
		if (!$node instanceof Node) {
			// No quota check for non-Node objects
			return true;
		}

		return $this->checkQuota($node->getPath());
	}

	/**
	 * Checks quota before moving a FutureFile node to a new destination.
	 *
	 * @param string $sourcePath Path to the source node.
	 * @param string $destinationPath Path where the node will be moved to.
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage.
	 */
	public function beforeMove(string $sourcePath, string $destinationPath): bool {
		$sourceNode = $this->server->tree->getNodeForPath($sourcePath);
		if (!$sourceNode instanceof FutureFile) {
			return true;
		}

		try {
			// The final path is not known yet, check quota on the parent of the destination
			$quotaPath = $this->getPathForDestination($destinationPath);
		} catch (\Exception $e) {
			// Optionally log: e.g. ('Quota check failed during beforeMove: ' . $e->getMessage());
			return true;
		}

		return $this->checkQuota($quotaPath, $sourceNode->getSize());
	}

	/**
	 * Checks quota before allowing a file copy operation.
	 *
	 * @param string $sourcePath Path to the source node.
	 * @param string $destinationPath Path where the node will be copied to.
	 * @return bool True if there is enough quota, otherwise throws InsufficientStorage.
	 */
	public function beforeCopy(string $sourcePath, string $destinationPath): bool {
		$sourceNode = $this->server->tree->getNodeForPath($sourcePath);
		if (!$sourceNode instanceof Node) {
			return true;
		}

		try {
			$quotaPath = $this->getPathForDestination($destinationPath);
		} catch (\Exception $e) {
			// Optionally log: e.g. ('Quota check failed during beforeCopy: ' . $e->getMessage());
			return true;
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
	 * @throws \Exception If the destination or parent node is not a valid Node.
	 */
	private function getPathForDestination(string $destinationPath): string {
		// If the node exists, return its actual path
		if ($this->server->tree->nodeExists($destinationPath)) {
			$destinationNode = $this->server->tree->getNodeForPath($destinationPath);
			if (!$destinationNode instanceof Node) {
				throw new \Exception("Destination node at '$destinationPath' is not a valid Node instance.");
			}
			return $destinationNode->getPath();
		}

		// Otherwise, use the parent directory's path
		$parent = dirname($destinationPath);
		$parent = ($parent === '.') ? '' : $parent;

		$parentNode = $this->server->tree->getNodeForPath($parent);
		if (!$parentNode instanceof Node) {
			throw new \Exception("Parent node at '$parent' is not a valid Node instance.");
		}

		return $parentNode->getPath();
	}

	/**
	 * Validates there is enough free space to store the file at the given path.
	 *
	 * Called before relevant HTTP DAV events (when there is an associated View).
	 * @see initialize() for specific events we're registered for.
	 *
	 * @param string $path Path relative to the user's home.
	 * @param int|float|null $length Size to check for, or null to auto-detect.
	 * @param bool $isDir Whether the target is a directory.
	 * @throws InsufficientStorage
	 * @return bool True if there is enough space, otherwise throws.
	 */
	private function checkQuota(string $path, $length = null, bool $isDir = false): bool {
		// Auto-detect length if not provided
		if ($length === null) {
			$length = $this->getLength();
		}
		if (empty($length)) {
			return true; // No length to check, assume okay
		}

		$normalizedPath = str_replace('//', '/', $path);
		$freeSpace = $this->getFreeSpace($normalizedPath);

		// Explicitly handle unknown/invalid free space
		if ($freeSpace === false || $freeSpace < 0) {
			// You might log here; currently allows the operation
			return true;
		}

		if ($length > $freeSpace) {
			$msg = $isDir
				? "Insufficient space in $normalizedPath. $freeSpace available. Cannot create directory"
				: "Insufficient space in $normalizedPath, $length required, $freeSpace available";
			throw new InsufficientStorage($msg);
		}

		return true;
	}

	/**
	 * Returns the largest valid content length found in any of the following HTTP headers:
	 * - X-Expected-Entity-Length
	 * - Content-Length
	 * - OC-Total-Length
	 *
	 * Only numeric values are considered. If none of the headers contain a valid numeric value,
	 * returns null.
	 *
	 * @return int|null The largest valid content length, or null if none is found.
	 */
	private function getLength(): ?int {
		$request = $this->server->httpRequest;

		// Get headers as strings
		$expectedLength = $request->getHeader('X-Expected-Entity-Length');
		$contentLength = $request->getHeader('Content-Length');
		$ocTotalLength = $request->getHeader('OC-Total-Length');

		// Filter out non-numeric values, cast to int
		$lengths = array_filter([
			is_numeric($expectedLength) ? (int)$expectedLength : null,
			is_numeric($contentLength) ? (int)$contentLength : null,
			is_numeric($ocTotalLength) ? (int)$ocTotalLength : null,
		], fn ($v) => $v !== null);

		// Return the largest valid length, or null if none
		return !empty($lengths) ? max($lengths) : null;
	}

	/**
	 * Returns the available free space for the given URI.
	 *
	 * TODO: `false` can probably be dropped here, if not now when free_space is cleaned up.
	 *
	 * @param string $uri The resource URI whose free space is being queried.
	 * @return int|float|false The amount of free space in bytes,
	 * @throws ServiceUnavailable If the underlying storage is not available.
	 */
	private function getFreeSpace(string $uri): int|float|false {
		try {
			return $this->view->free_space(ltrim($uri, '/'));
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($e->getMessage());
		}
	}
}
