<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IDBConnection;

/**
 * Class Watcher
 *
 * @package OC\Preview
 *
 * Class that will watch filesystem activity and remove previews as needed.
 */
class Watcher {
	/**
	 * Watcher constructor.
	 */
	public function __construct(
		private readonly StorageFactory $storageFactory,
		private readonly PreviewMapper $previewMapper,
		private readonly IDBConnection $connection,
	) {
	}

	public function postWrite(Node $node): void {
		$this->deleteNode($node);
	}

	protected function deleteNode(FileInfo $node): void {
		// We only handle files
		if ($node instanceof Folder) {
			return;
		}

		$nodeId = $node->getId();
		if (is_null($nodeId)) {
			return;
		}

		[$node->getId() => $previews] = $this->previewMapper->getAvailablePreviews([$nodeId]);
		$this->connection->beginTransaction();
		try {
			foreach ($previews as $preview) {
				$this->storageFactory->deletePreview($preview);
				$this->previewMapper->delete($preview);
			}
		} finally {
			$this->connection->commit();
		}
	}

	public function versionRollback(array $data): void {
		if (isset($data['node'])) {
			$this->deleteNode($data['node']);
		}
	}
}
