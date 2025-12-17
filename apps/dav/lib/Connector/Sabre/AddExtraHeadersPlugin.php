<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Adds the "OC-OwnerId" and "OC-Permissions" after PUT requests so that
 * clients don't need to do a propfind after uploading a file to decide what
 * to display.
 */
class AddExtraHeadersPlugin extends \Sabre\DAV\ServerPlugin {

	private ?Server $server = null;

	public function __construct(
		private LoggerInterface $logger,
		private bool $isPublic = false,
	) {
	}

	public function initialize(Server $server): void {
		$this->server = $server;

		$server->on('afterMethod:PUT', $this->afterPut(...));
	}

	private function afterPut(RequestInterface $request, ResponseInterface $response): void {
		if ($this->server === null) {
			return;
		}

		$node = null;
		try {
			$node = $this->server->tree->getNodeForPath($request->getPath());
		} catch (NotFound) {
			$this->logger->error("Cannot set extra headers for non-existing file '{$request->getPath()}'");
			return;
		}

		if (!$node instanceof Node) {
			$nodeType = get_debug_type($node);
			$this->logger->error("Cannot set extra headers for node of type {$nodeType} for file '{$request->getPath()}'");
			return;
		}

		if (!$this->isPublic) {
			$ownerId = $node->getOwner()?->getUID();
			if ($ownerId !== null) {
				$response->setHeader('X-NC-OwnerId', $ownerId);
			}
		}

		$permissions = $this->isPublic ? $node->getPublicDavPermissions()
			: $node->getDavPermissions();

		$response->setHeader('X-NC-Permissions', $permissions);
	}
}
