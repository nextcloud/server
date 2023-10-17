<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\FilesMetadata\Job;

use OC\FilesMetadata\FilesMetadataManager;
use OC\User\NoUserException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;

class UpdateSingleMetadata extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private IRootFolder $rootFolder,
		private FilesMetadataManager $filesMetadataManager,
	) {
		parent::__construct($time);
	}

	protected function run($argument) {
		[$userId, $fileId] = $argument;

		try {
			// TODO: is there a way to get Node without $userId ?
			$node = $this->rootFolder->getUserFolder($userId)->getById($fileId);
			if (count($node) > 0) {
				$file = array_shift($node);
				$this->filesMetadataManager->refreshMetadata($file, true);
			}
		} catch (NotPermittedException |NoUserException $e) {
		}
	}
}
