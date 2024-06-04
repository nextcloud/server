<?php

/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\DAV;

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File as DavFile;
use OCA\Files_Versions\Sabre\VersionFile;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

/**
 * Sabre plugin for restricting file share receiver download:
 */
class ViewOnlyPlugin extends ServerPlugin {
	private ?Server $server = null;
	private ?Folder $userFolder;

	public function __construct(
		?Folder $userFolder,
	) {
		$this->userFolder = $userFolder;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 */
	public function initialize(Server $server): void {
		$this->server = $server;
		//priority 90 to make sure the plugin is called before
		//Sabre\DAV\CorePlugin::httpGet
		$this->server->on('method:GET', [$this, 'checkViewOnly'], 90);
		$this->server->on('method:COPY', [$this, 'checkViewOnly'], 90);
	}

	/**
	 * Disallow download via DAV Api in case file being received share
	 * and having special permission
	 *
	 * @throws Forbidden
	 * @throws NotFoundException
	 */
	public function checkViewOnly(RequestInterface $request): bool {
		$path = $request->getPath();

		try {
			assert($this->server !== null);
			$davNode = $this->server->tree->getNodeForPath($path);
			if ($davNode instanceof DavFile) {
				// Restrict view-only to nodes which are shared
				$node = $davNode->getNode();
			} elseif ($davNode instanceof VersionFile) {
				$node = $davNode->getVersion()->getSourceFile();
				$currentUserId = $this->userFolder?->getOwner()?->getUID();
				// The version source file is relative to the owner storage.
				// But we need the node from the current user perspective.
				if ($node->getOwner()->getUID() !== $currentUserId) {
					$nodes = $this->userFolder->getById($node->getId());
					$node = array_pop($nodes);
					if (!$node) {
						throw new NotFoundException("Version file not accessible by current user");
					}
				}
			} else {
				return true;
			}

			$storage = $node->getStorage();

			if (!$storage->instanceOfStorage(\OCA\Files_Sharing\SharedStorage::class)) {
				return true;
			}
			// Extract extra permissions
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			$share = $storage->getShare();

			$attributes = $share->getAttributes();
			if ($attributes === null) {
				return true;
			}

			// Check if read-only and on whether permission can download is both set and disabled.
			$canDownload = $attributes->getAttribute('permissions', 'download');
			if ($canDownload !== null && !$canDownload) {
				throw new Forbidden('Access to this shared resource has been denied because its download permission is disabled.');
			}
		} catch (NotFound $e) {
			// File not found
		}

		return true;
	}
}
