<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * This plugin asks other plugins to preload data for a collection, so that
 * subsequent PROPFIND handlers for children do not query the DB on a per-node
 * basis.
 */
class PropFindPreloadNotifyPlugin extends ServerPlugin {

	private Server $server;

	public function initialize(Server $server): void {
		$this->server = $server;
		$this->server->on('propFind', [$this, 'collectionPreloadNotifier' ], 1);
	}

	/**
	 * Uses the server instance to emit a `preloadCollection` event to signal
	 * to interested plugins that a collection can be preloaded.
	 *
	 * NOTE: this can be emitted several times, so ideally every plugin
	 * should cache what they need and check if a cache exists before
	 * re-fetching.
	 */
	public function collectionPreloadNotifier(PropFind $propFind, INode $node): bool {
		if (!$this->shouldPreload($propFind, $node)) {
			return true;
		}

		return $this->server->emit('preloadCollection', [$propFind, $node]);
	}

	private function shouldPreload(
		PropFind $propFind,
		INode $node,
	): bool {
		$depth = $propFind->getDepth();
		return $node instanceof ICollection
			&& ($depth === Server::DEPTH_INFINITY || $depth > 0);
	}
}
