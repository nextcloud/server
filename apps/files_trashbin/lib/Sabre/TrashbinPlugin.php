<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OC\Files\FileInfo;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCP\IPreview;
use Psr\Log\LoggerInterface;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

/**
 * SabreDAV server plugin for managing Nextcloud's trashbin features.
 *
 * Handles events and properties related to deleted files, such as restoration, quota checks, and
 * custom responses for trashbin resources over WebDAV.
 */
class TrashbinPlugin extends ServerPlugin {
	public const TRASHBIN_FILENAME = '{http://nextcloud.org/ns}trashbin-filename';
	public const TRASHBIN_ORIGINAL_LOCATION = '{http://nextcloud.org/ns}trashbin-original-location';
	public const TRASHBIN_DELETION_TIME = '{http://nextcloud.org/ns}trashbin-deletion-time';
	public const TRASHBIN_TITLE = '{http://nextcloud.org/ns}trashbin-title';
	public const TRASHBIN_DELETED_BY_ID = '{http://nextcloud.org/ns}trashbin-deleted-by-id';
	public const TRASHBIN_DELETED_BY_DISPLAY_NAME = '{http://nextcloud.org/ns}trashbin-deleted-by-display-name';
	public const TRASHBIN_BACKEND = '{http://nextcloud.org/ns}trashbin-backend';
	public const TRASHBIN_RESTORE_SPACE_SAFETY_MARGIN = 65536; // 64 KiB

	private Server $server;

	public function __construct(
		private readonly IPreview $previewManager,
		private readonly View $view,
	) {
	}

	public function initialize(Server $server): void {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
		$this->server->on('afterMethod:GET', [$this,'httpGet']);
		$this->server->on('beforeMove', [$this, 'beforeMove']);
	}


	public function propFind(PropFind $propFind, INode $node): void {
		// Only act on trashbin nodes
		if (!($node instanceof ITrash)) {
			return;
		}

		// Trashbin specific properties
		$propFind->handle(self::TRASHBIN_FILENAME, fn () => $node->getFilename());
		$propFind->handle(self::TRASHBIN_ORIGINAL_LOCATION, fn () => $node->getOriginalLocation());
		$propFind->handle(self::TRASHBIN_TITLE, fn () => $node->getTitle());
		$propFind->handle(self::TRASHBIN_DELETION_TIME, fn () => $node->getDeletionTime());
		$propFind->handle(self::TRASHBIN_DELETED_BY_ID, fn () => $node->getDeletedBy()?->getUID());
		$propFind->handle(self::TRASHBIN_DELETED_BY_DISPLAY_NAME, fn () => $node->getDeletedBy()?->getDisplayName());
		$propFind->handle(
			self::TRASHBIN_BACKEND,
			function () use ($node) {
				$fileInfo = $node->getFileInfo();
				if (!($fileInfo instanceof ITrashItem)) {
					return '';
				}
				return $fileInfo->getTrashBackend()::class;
			}
		);
		// Properties mapped from FilesPlugin (most are returned as from the trashbin item itself)
		$propFind->handle(
			FilesPlugin::DISPLAYNAME_PROPERTYNAME,
			fn () => $node->getFilename() // original filename of the ITrash node before deletion
		);
		$propFind->handle(
			FilesPlugin::SIZE_PROPERTYNAME,
			fn () => $node->getSize()
		);
		$propFind->handle(
			// User-facing file ID: lets WebDAV clients identify this trashed item in the filesystem view.
			FilesPlugin::FILEID_PROPERTYNAME,
			fn () => $node->getFileId()
		);
		$propFind->handle(
			FilesPlugin::PERMISSIONS_PROPERTYNAME,
			fn () => 'GD' // Permissions: 'G' = read, 'D' = delete
		);
		$propFind->handle(
			FilesPlugin::HAS_PREVIEW_PROPERTYNAME,
			fn () => $this->previewManager->isAvailable($node->getFileInfo()) ? 'true' : 'false'
		);
		$propFind->handle(
			FilesPlugin::GETETAG_PROPERTYNAME,
			fn () => $node->getLastModified() // Etag based on last modified time of deleted item
		);
		$propFind->handle(
			// Instance-scoped internal file ID: uniquely references this trashbin entry within Nextcloud's storage backend.
			FilesPlugin::INTERNAL_FILEID_PROPERTYNAME,
			fn () => $node->getFileId() // if storage backends diverge in future can be swapped transparently.
		);
		$propFind->handle(
			// Storage mount type (e.g., personal, groupfolder, or external storage)
			FilesPlugin::MOUNT_TYPE_PROPERTYNAME,
			fn () => '' // Trashbin items don't have a mount type currently
		);
	}

	/**
	 * Suggest the original filename to the browser for the download.
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response): void {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);

		if (!($node instanceof ITrash)) {
			return;
		}

		$response->addHeader(
			'Content-Disposition',
			'attachment; filename="' . $node->getFilename() . '"' // TODO: Confirm `filename` value is ASCII; add `filename*=UTF-8` support w/ encoding
		);
	}

	/**
	 * Checks if there is enough available storage space to restore a file, to the destination path, from the trashbin.
	 *
	 * This method is called before moving a file out of the trashbin. It returns true if the user
	 * has sufficient quota to restore the file, or if the quota is unlimited or cannot be determined.
	 *
	 * @param string $sourcePath The path to the file in the trashbin.
	 * @param string $destinationPath The path where the file will be restored.
	 * @return bool True if restore is allowed, false otherwise.
	 */
	public function beforeMove(string $sourcePath, string $destinationPath): bool {
		$logger = \OCP\Server::get(LoggerInterface::class);

		try {
			$node = $this->server->tree->getNodeForPath($sourcePath);

			if (!($node instanceof ITrash)) {
				return true;
			}

			[$destinationParentPath, ] = Uri\split($destinationPath);
			$destinationParentNode = $this->server->tree->getNodeForPath($destinationParentPath);

			if (!($destinationParentNode instanceof RestoreFolder)) {
				return true;
			}

		} catch (\Sabre\DAV\Exception $e) {
			$logger->error('Failed to move trashbin file', [
				'app' => 'files_trashbin',
				'exception' => $e
			]);
			return true;
		}

		$fileInfo = $node->getFileInfo();

		if (!($fileInfo instanceof ITrashItem)) {
			return true;
		}

		$freeSpace = $this->view->free_space($destinationParentPath);

		if (
			$freeSpace === FileInfo::SPACE_NOT_COMPUTED
			|| $freeSpace === FileInfo::SPACE_UNKNOWN
			|| $freeSpace === FileInfo::SPACE_UNLIMITED
		) {
			// No relevant quota
			return true;
		}

		$fileSize = $fileInfo->getSize();

		if ($freeSpace - $fileSize < self::TRASHBIN_RESTORE_SPACE_SAFETY_MARGIN) {
			// Not enough space, block restore
			$this->server->httpResponse->setStatus(507);
			$logger->debug('Failed to move trashbin file', [
				'app' => 'files_trashbin',
				'reason' => 'Insufficient space available to restore safely'
			]);
			return false;
		}

		return true;
	}
}
