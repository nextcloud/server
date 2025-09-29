<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Upload;

use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use function Sabre\Uri\split as uriSplit;

/**
 * Class that allows automatically creating non-existing collections on file
 * upload.
 *
 * Since this functionality is not WebDAV compliant, it needs a special
 * header to be activated.
 */
class UploadAutoMkcolPlugin extends ServerPlugin {

	private Server $server;

	public function initialize(Server $server): void {
		$server->on('beforeMethod:PUT', [$this, 'beforeMethod']);
		$this->server = $server;
	}

	/**
	 * @throws NotFound a node  expected to exist cannot be found
	 */
	public function beforeMethod(RequestInterface $request, ResponseInterface $response): bool {
		if ($request->getHeader('X-NC-WebDAV-Auto-Mkcol') !== '1') {
			return true;
		}

		[$path,] = uriSplit($request->getPath());

		if ($this->server->tree->nodeExists($path)) {
			return true;
		}

		$parts = explode('/', trim($path, '/'));
		$rootPath = array_shift($parts);
		$node = $this->server->tree->getNodeForPath('/' . $rootPath);

		if (!($node instanceof ICollection)) {
			// the root node is not a collection, let SabreDAV handle it
			return true;
		}

		foreach ($parts as $part) {
			if (!$node->childExists($part)) {
				$node->createDirectory($part);
			}

			$node = $node->getChild($part);
		}

		return true;
	}
}
