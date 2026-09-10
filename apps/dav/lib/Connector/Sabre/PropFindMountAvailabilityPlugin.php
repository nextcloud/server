<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

/**
 * Checks that a Files collection can be listed before Sabre starts streaming a PROPFIND response.
 */
class PropFindMountAvailabilityPlugin extends ServerPlugin {
	private Server $server;

	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		$this->server->on('method:PROPFIND', [$this, 'preflight'], 10);
	}

	public function preflight(RequestInterface $request): void {
		$depth = $this->server->getHTTPDepth(1);
		if ($depth === 0) {
			return;
		}

		$node = $this->server->tree->getNodeForPath($request->getPath());
		if ($node instanceof Directory) {
			$this->preflightDirectory($node, $depth === Server::DEPTH_INFINITY);
		}
	}

	private function preflightDirectory(Directory $directory, bool $recursive): void {
		$children = $directory->getChildrenStrict();
		if (!$recursive) {
			return;
		}

		foreach ($children as $child) {
			if ($child instanceof Directory) {
				$this->preflightDirectory($child, true);
			}
		}
	}
}
