<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OCP\AppFramework\Http;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ChecksumUpdatePlugin extends ServerPlugin {
	protected ?Server $server = null;

	public function initialize(Server $server) {
		$this->server = $server;
		$server->on('method:PATCH', [$this, 'httpPatch']);
	}

	public function getPluginName(): string {
		return 'checksumupdate';
	}

	/** @return string[] */
	public function getFeatures(): array {
		return ['nextcloud-checksum-update'];
	}

	public function httpPatch(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		$node = $this->server->tree->getNodeForPath($path);
		if ($node instanceof File) {
			$type = strtolower(
				(string)$request->getHeader('X-Recalculate-Hash')
			);

			$hash = $node->hash($type);
			if ($hash) {
				$checksum = strtoupper($type) . ':' . $hash;
				$node->setChecksum($checksum);
				$response->addHeader('OC-Checksum', $checksum);
				$response->setHeader('Content-Length', '0');
				$response->setStatus(Http::STATUS_NO_CONTENT);

				return false;
			}
		}
	}
}
