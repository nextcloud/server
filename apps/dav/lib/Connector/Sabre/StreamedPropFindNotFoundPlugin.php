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
 * With Server::$streamMultiStatus enabled, CorePlugin::httpPropFind() commits
 * the 207 status and starts streaming the body before resolving the
 * requested node, so a missing node throws too late to still return a 404.
 *
 * Resolving the node here first, before the status is set, fixes that. The
 * tree caches the result.
 */
class StreamedPropFindNotFoundPlugin extends ServerPlugin {
	private Server $server;

	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		// Higher priorities than the default handling
		$this->server->on('method:PROPFIND', $this->ensureNodeExists(...), 10);
	}

	public function ensureNodeExists(RequestInterface $request): bool {
		$this->server->tree->getNodeForPath($request->getPath());
		return true;
	}
}
