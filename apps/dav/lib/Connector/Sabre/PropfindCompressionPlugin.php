<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class PropfindCompressionPlugin extends ServerPlugin {

	/**
	 * Reference to main server object
	 *
	 * @var Server
	 */
	private $server;

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;
		$this->server->on('afterMethod:PROPFIND', [$this, 'compressResponse'], 100);
	}

	public function compressResponse(Request $request, Response $response) {
		$header = $request->getHeader('Accept-Encoding');

		if ($header === null) {
			return $response;
		}

		if (str_contains($header, 'gzip')) {
			$body = $response->getBody();
			if (is_string($body)) {
				$response->setHeader('Content-Encoding', 'gzip');
				$response->setBody(gzencode($body));
			}
		}

		return $response;
	}
}
