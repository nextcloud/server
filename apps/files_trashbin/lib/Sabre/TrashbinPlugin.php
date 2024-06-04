<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\IPreview;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class TrashbinPlugin extends ServerPlugin {
	public const TRASHBIN_FILENAME = '{http://nextcloud.org/ns}trashbin-filename';
	public const TRASHBIN_ORIGINAL_LOCATION = '{http://nextcloud.org/ns}trashbin-original-location';
	public const TRASHBIN_DELETION_TIME = '{http://nextcloud.org/ns}trashbin-deletion-time';
	public const TRASHBIN_TITLE = '{http://nextcloud.org/ns}trashbin-title';
	public const TRASHBIN_DELETED_BY_ID = '{http://nextcloud.org/ns}trashbin-deleted-by-id';
	public const TRASHBIN_DELETED_BY_DISPLAY_NAME = '{http://nextcloud.org/ns}trashbin-deleted-by-display-name';

	/** @var Server */
	private $server;

	/** @var IPreview */
	private $previewManager;

	public function __construct(
		IPreview $previewManager
	) {
		$this->previewManager = $previewManager;
	}

	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
		$this->server->on('afterMethod:GET', [$this,'httpGet']);
	}


	public function propFind(PropFind $propFind, INode $node) {
		if (!($node instanceof ITrash)) {
			return;
		}

		$propFind->handle(self::TRASHBIN_FILENAME, function () use ($node) {
			return $node->getFilename();
		});

		$propFind->handle(self::TRASHBIN_ORIGINAL_LOCATION, function () use ($node) {
			return $node->getOriginalLocation();
		});

		$propFind->handle(self::TRASHBIN_TITLE, function () use ($node) {
			return $node->getTitle();
		});

		$propFind->handle(self::TRASHBIN_DELETION_TIME, function () use ($node) {
			return $node->getDeletionTime();
		});

		$propFind->handle(self::TRASHBIN_DELETED_BY_ID, function () use ($node) {
			return $node->getDeletedBy()?->getUID();
		});

		$propFind->handle(self::TRASHBIN_DELETED_BY_DISPLAY_NAME, function () use ($node) {
			return $node->getDeletedBy()?->getDisplayName();
		});

		$propFind->handle(FilesPlugin::SIZE_PROPERTYNAME, function () use ($node) {
			return $node->getSize();
		});

		$propFind->handle(FilesPlugin::FILEID_PROPERTYNAME, function () use ($node) {
			return $node->getFileId();
		});

		$propFind->handle(FilesPlugin::PERMISSIONS_PROPERTYNAME, function () {
			return 'GD'; // read + delete
		});

		$propFind->handle(FilesPlugin::GETETAG_PROPERTYNAME, function () use ($node) {
			// add fake etag, it is only needed to identify the preview image
			return $node->getLastModified();
		});

		$propFind->handle(FilesPlugin::INTERNAL_FILEID_PROPERTYNAME, function () use ($node) {
			// add fake etag, it is only needed to identify the preview image
			return $node->getFileId();
		});

		$propFind->handle(FilesPlugin::HAS_PREVIEW_PROPERTYNAME, function () use ($node) {
			return $this->previewManager->isAvailable($node->getFileInfo());
		});

		$propFind->handle(FilesPlugin::MOUNT_TYPE_PROPERTYNAME, function () {
			return '';
		});
	}

	/**
	 * Set real filename on trashbin download
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response): void {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		if ($node instanceof ITrash) {
			$response->addHeader('Content-Disposition', 'attachment; filename="' . $node->getFilename() . '"');
		}
	}
}
