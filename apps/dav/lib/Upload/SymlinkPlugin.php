<?php

declare(strict_types=1);
/*
 * @copyright Copyright (c) 2023 Tamino Bauknecht <dev@tb6.eu>
 *
 * @author Tamino Bauknecht <dev@tb6.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Upload;

use OCP\Files\SymlinkManager;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SymlinkPlugin extends ServerPlugin {
	/** @var Server */
	private $server;
	/** @var SymlinkManager */
	private $symlinkManager;

	public function __construct() {
		$this->symlinkManager = new SymlinkManager();
	}

	/**
	 * @inheritdoc
	 */
	public function initialize(Server $server) {
		$server->on('method:PUT', [$this, 'httpPut']);
		$server->on('method:DELETE', [$this, 'httpDelete']);

		$this->server = $server;
	}

	public function httpPut(RequestInterface $request, ResponseInterface $response): bool {
		if ($request->hasHeader('OC-File-Type') && $request->getHeader('OC-File-Type') == 1) {
			$symlinkPath = $request->getPath();
			$symlinkName = basename($symlinkPath);
			$symlinkTarget = $request->getBodyAsString();
			$parentPath = dirname($symlinkPath);
			$parentNode = $this->server->tree->getNodeForPath($parentPath);
			if (!$parentNode instanceof \Sabre\DAV\ICollection) {
				throw new \Sabre\DAV\Exception\Forbidden("Directory does not allow creation of files - failed to upload '$symlinkName'");
			}
			$etag = $parentNode->createFile($symlinkName);
			$symlinkNode = $parentNode->getChild($symlinkName);
			if (!$symlinkNode instanceof \OCA\DAV\Connector\Sabre\File) {
				throw new \Sabre\DAV\Exception\NotFound("Failed to get newly created file '$symlinkName'");
			}
			$symlinkNode->put($symlinkTarget);
			$this->symlinkManager->storeSymlink($symlinkNode->getFileInfo());

			$response->setHeader("OC-ETag", $etag);
			$response->setStatus(201);
			return false;
		}
		return true;
	}

	public function httpDelete(RequestInterface $request, ResponseInterface $response): bool {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath(dirname($path));
		if (!$node instanceof \OCA\DAV\Connector\Sabre\File) {
			return true;
		}
		$info = $node->getFileInfo();
		if ($this->symlinkManager->isSymlink($info)) {
			$this->symlinkManager->deleteSymlink($info);
		}
		// always propagate to trigger deletion of regular file representing symlink in filesystem
		return true;
	}
}
