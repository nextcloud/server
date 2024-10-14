<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Files\Sharing;

use OCP\Files\FileInfo;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Verify that the public link share is valid
 */
class PublicLinkCheckPlugin extends ServerPlugin {
	/**
	 * @var FileInfo
	 */
	private $fileInfo;

	/**
	 * @param FileInfo $fileInfo
	 */
	public function setFileInfo($fileInfo) {
		$this->fileInfo = $fileInfo;
	}

	/**
	 * This initializes the plugin.
	 *
	 * @param \Sabre\DAV\Server $server Sabre server
	 *
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('beforeMethod:*', [$this, 'beforeMethod']);
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		// verify that the owner didn't have their share permissions revoked
		if ($this->fileInfo && !$this->fileInfo->isShareable()) {
			throw new NotFound();
		}
	}
}
