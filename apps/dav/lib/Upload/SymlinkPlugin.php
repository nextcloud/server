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

use OC\Files\SymlinkManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SymlinkPlugin extends ServerPlugin {
	/** @var Server */
	private $server;
	/** @var SymlinkManager */
	private $symlinkManager;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(LoggerInterface $logger) {
		$this->symlinkManager = new SymlinkManager();
		$this->logger = $logger;
	}

	/**
	 * @inheritdoc
	 */
	public function initialize(Server $server) {
		$server->on('method:PUT', [$this, 'httpPut']);
		$server->on('method:DELETE', [$this, 'httpDelete']);
		$server->on('afterMove', [$this, 'afterMove']);

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
			return false; // this request was handled already
		} elseif ($this->server->tree->nodeExists($request->getPath())) {
			$node = $this->server->tree->getNodeForPath($request->getPath());
			if (!$node instanceof \OCA\DAV\Connector\Sabre\File) {
				// cannot check if file was symlink before - let's hope it's not
				$this->logger->warning('Unable to check if there was a symlink
					before at the same location');
				return true;
			}
			// if the newly uploaded file is not a symlink,
			// but there was a symlink at the same path before
			if ($this->symlinkManager->isSymlink($node->getFileInfo())) {
				$this->symlinkManager->deleteSymlink($node->getFileInfo());
			}
		}
		return true; // continue handling this request
	}

	public function httpDelete(RequestInterface $request, ResponseInterface $response): bool {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		if (!$node instanceof \OCA\DAV\Connector\Sabre\Node) {
			return true;
		}
		$info = $node->getFileInfo();
		if ($this->symlinkManager->isSymlink($info)) {
			if (!$this->symlinkManager->deleteSymlink($info)) {
				$symlinkName = $info->getName();
				throw new \Sabre\DAV\Exception\NotFound("Unable to delete symlink '$symlinkName'!");
			}
		}
		// always propagate to trigger deletion of regular file representing symlink in filesystem
		return true;
	}

	public function afterMove(string $source, string $destination) {
		// source node does not exist anymore, thus use still existing parent
		$sourceParentNode = dirname($source);
		$sourceParentNode = $this->server->tree->getNodeForPath($sourceParentNode);
		if (!$sourceParentNode instanceof \OCA\DAV\Connector\Sabre\Node) {
			throw new \Sabre\DAV\Exception\NotImplemented('Unable to check if moved file is a symlink!');
		}
		$destinationNode = $this->server->tree->getNodeForPath($destination);
		if (!$destinationNode instanceof \OCA\DAV\Connector\Sabre\Node) {
			throw new \Sabre\DAV\Exception\NotImplemented('Unable to set symlink information on move destination!');
		}

		$sourceInfo = new \OC\Files\FileInfo(
			$source,
			$sourceParentNode->getFileInfo()->getStorage(),
			$sourceParentNode->getInternalPath() . '/' . basename($source),
			[],
			$sourceParentNode->getFileInfo()->getMountPoint());
		$destinationInfo = $destinationNode->getFileInfo();

		if ($this->symlinkManager->isSymlink($sourceInfo)) {
			$this->symlinkManager->deleteSymlink($sourceInfo);
			$this->symlinkManager->storeSymlink($destinationInfo);
		} elseif ($this->symlinkManager->isSymlink($destinationInfo)) {
			// source was not a symlink, but destination was a symlink before
			$this->symlinkManager->deleteSymlink($destinationInfo);
		}
	}
}
