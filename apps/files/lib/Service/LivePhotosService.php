<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
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
