<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\Forbidden as DAVForbiddenException;
use OCP\AppFramework\Http;
use OCP\Files\ForbiddenException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
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

	/**
	 * @throws BadRequest if the requested hash algorithm is not supported
	 * @throws DAVForbiddenException if the user may not read and update the node
	 * @throws Exception if the checksum could not be calculated
	 */
	public function httpPatch(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		$node = $this->server->tree->getNodeForPath($path);
		if (!$node instanceof File) {
			return;
		}

		$type = strtolower(
			(string)$request->getHeader('X-Recalculate-Hash')
		);

		// Without the header this is not a checksum update, leave the request to other plugins
		if ($type === '') {
			return;
		}

		if (!in_array($type, hash_algos(), true)) {
			throw new BadRequest('Unsupported hash algorithm "' . $type . '"');
		}

		// Recalculating reads the whole file content and persists the result,
		// so it requires both read and write access to the node.
		$info = $node->getFileInfo();
		if (!$info->isReadable() || !$info->isUpdateable()) {
			throw new DAVForbiddenException('You are not allowed to recalculate the checksum of this file', false);
		}

		try {
			$hash = $node->hash($type);
			if ($hash === false) {
				throw new Exception('Could not calculate the ' . $type . ' checksum of "' . $path . '"');
			}

			$checksum = strtoupper($type) . ':' . $hash;
			$node->setChecksum($checksum);

			$response->addHeader('OC-Checksum', $checksum);
			$response->setHeader('Content-Length', '0');
			$response->setStatus(Http::STATUS_NO_CONTENT);
			return false;
		} catch (ForbiddenException $e) {
			throw new DAVForbiddenException($e->getMessage(), $e->getRetry(), $e);
		}
	}
}
