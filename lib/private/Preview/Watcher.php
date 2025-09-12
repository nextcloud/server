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
		readonly private StorageFactory $storageFactory,
		readonly private PreviewMapper $previewMapper,
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

		if (is_null($node->getId())) {
			return;
		}

		[$node->getId() => $previews] = $this->previewMapper->getAvailablePreviews([$node->getId()]);
		foreach ($previews as $preview) {
			$this->storageFactory->deletePreview($preview);
		}
	}

	public function versionRollback(array $data): void {
		if (isset($data['node'])) {
			$this->deleteNode($data['node']);
		}
	}
}
