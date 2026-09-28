<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\Server as SabreServer;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin runs after requests and logs an error if a plugin is detected
 * to be doing too many SQL requests.
 */
class PropFindMonitorPlugin extends ServerPlugin {

	/**
	 * A Plugin can scan up to this amount of nodes without an error being
	 * reported.
	 */
	public const THRESHOLD_NODES = 50;

	/**
	 * A plugin can use up to this amount of queries per node.
	 */
	public const THRESHOLD_QUERY_FACTOR = 1;

	private SabreServer $server;

	public function initialize(SabreServer $server): void {
		$this->server = $server;
		$this->server->on('afterResponse', [$this, 'afterResponse']);
	}

	public function afterResponse(
		RequestInterface $request,
		ResponseInterface $response): void {
		if (!$this->server instanceof Server) {
			return;
		}

		$pluginQueries = $this->server->getPluginQueries();
		if (empty($pluginQueries)) {
			return;
		}

		$logger = $this->server->getLogger();
		foreach ($pluginQueries as $eventName => $eventQueries) {
			$maxDepth = max(0, ...array_keys($eventQueries));
			// entries at the top are usually not interesting
			unset($eventQueries[$maxDepth]);
			foreach ($eventQueries as $depth => $propFinds) {
				foreach ($propFinds as $pluginName => $propFind) {
					[
						'queries' => $queries,
						'nodes' => $nodes
					] = $propFind;
					if ($queries === 0 || $nodes > $queries || $nodes < self::THRESHOLD_NODES
						|| $queries < $nodes * self::THRESHOLD_QUERY_FACTOR) {
						continue;
					}
					$logger->error(
						'{name}:{event} scanned {scans} nodes with {count} queries in depth {depth}/{maxDepth}. This is bad for performance, please report to the plugin developer!',
						[
							'name' => $pluginName,
							'scans' => $nodes,
							'count' => $queries,
							'depth' => $depth,
							'maxDepth' => $maxDepth,
							'event' => $eventName,
						]
					);
				}
			}
		}
	}
}
