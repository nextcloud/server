<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Copies the "Etag" header to "OC-Etag" after any request.
 * This is a workaround for setups that automatically strip
 * or mangle Etag headers.
 */
class CopyEtagHeaderPlugin extends \Sabre\DAV\ServerPlugin {
	private ?Server $server = null;

	/**
	 * This initializes the plugin.
	 *
	 * @param \Sabre\DAV\Server $server Sabre server
	 *
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;

		$server->on('afterMethod:*', [$this, 'afterMethod']);
		$server->on('afterMove', [$this, 'afterMove']);
	}

	/**
	 * After method, copy the "Etag" header to "OC-Etag" header.
	 *
	 * @param RequestInterface $request request
	 * @param ResponseInterface $response response
	 */
	public function afterMethod(RequestInterface $request, ResponseInterface $response) {
		$eTag = $response->getHeader('Etag');
		if (!empty($eTag)) {
			$response->setHeader('OC-ETag', $eTag);
		}
	}

	/**
	 * Called after a node is moved.
	 *
	 * This allows the backend to move all the associated properties.
	 *
	 * @param string $source
	 * @param string $destination
	 * @return void
	 */
	public function afterMove($source, $destination) {
		try {
			$node = $this->server->tree->getNodeForPath($destination);
		} catch (NotFound $e) {
			// Don't care
			return;
		}

		if ($node instanceof File) {
			$eTag = $node->getETag();
			$this->server->httpResponse->setHeader('OC-ETag', $eTag);
			$this->server->httpResponse->setHeader('ETag', $eTag);
		}
	}
}
