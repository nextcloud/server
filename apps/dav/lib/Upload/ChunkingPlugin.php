<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCP\AppFramework\Http;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class ChunkingPlugin extends ServerPlugin {

	/** @var Server */
	private $server;
	/** @var FutureFile */
	private $sourceNode;

	/**
	 * @inheritdoc
	 */
	public function initialize(Server $server) {
		$server->on('beforeMove', [$this, 'beforeMove']);
		$this->server = $server;
	}

	/**
	 * @param string $sourcePath source path
	 * @param string $destination destination path
	 * @return bool|void
	 * @throws BadRequest
	 * @throws NotFound
	 */
	public function beforeMove($sourcePath, $destination) {
		$this->sourceNode = $this->server->tree->getNodeForPath($sourcePath);
		if (!$this->sourceNode instanceof FutureFile) {
			// skip handling as the source is not a chunked FutureFile
			return;
		}

		try {
			/** @var INode $destinationNode */
			$destinationNode = $this->server->tree->getNodeForPath($destination);
			if ($destinationNode instanceof Directory) {
				throw new BadRequest("The given destination $destination is a directory.");
			}
		} catch (NotFound $e) {
			// If the destination does not exist yet it's not a directory either ;)
		}

		$this->verifySize();
		return $this->performMove($sourcePath, $destination);
	}

	/**
	 * Move handler for future file.
	 *
	 * This overrides the default move behavior to prevent Sabre
	 * to delete the target file before moving. Because deleting would
	 * lose the file id and metadata.
	 *
	 * @param string $path source path
	 * @param string $destination destination path
	 * @return bool|void false to stop handling, void to skip this handler
	 */
	public function performMove($path, $destination) {
		$fileExists = $this->server->tree->nodeExists($destination);
		// do a move manually, skipping Sabre's default "delete" for existing nodes
		try {
			$this->server->tree->move($path, $destination);
		} catch (Forbidden $e) {
			$sourceNode = $this->server->tree->getNodeForPath($path);
			if ($sourceNode instanceof FutureFile) {
				$sourceNode->delete();
			}
			throw $e;
		}

		// trigger all default events (copied from CorePlugin::move)
		$this->server->emit('afterMove', [$path, $destination]);
		$this->server->emit('afterUnbind', [$path]);
		$this->server->emit('afterBind', [$destination]);

		$response = $this->server->httpResponse;
		$response->setHeader('Content-Length', '0');
		$response->setStatus($fileExists ? Http::STATUS_NO_CONTENT : Http::STATUS_CREATED);

		return false;
	}

	/**
	 * @throws BadRequest
	 */
	private function verifySize() {
		$expectedSize = $this->server->httpRequest->getHeader('OC-Total-Length');
		if ($expectedSize === null) {
			return;
		}
		$actualSize = $this->sourceNode->getSize();

		// casted to string because cast to float cause equality for non equal numbers
		// and integer has the problem of limited size on 32 bit systems
		if ((string)$expectedSize !== (string)$actualSize) {
			throw new BadRequest("Chunks on server do not sum up to $expectedSize but to $actualSize bytes");
		}
	}
}
