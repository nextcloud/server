<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Service;

use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;

class LivePhotosService {
	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
	) {
	}

	/**
	 * Get the associated live photo for a given file id
	 */
	public function getLivePhotoPeerId(int $fileId): ?int {
		try {
			$metadata = $this->filesMetadataManager->getMetadata($fileId);
		} catch (FilesMetadataNotFoundException $ex) {
			return null;
		}

		if (!$metadata->hasKey('files-live-photo')) {
			return null;
		}

		return (int)$metadata->getString('files-live-photo');
	}
}
