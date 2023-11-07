<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\IFilesMetadataManager;
use Psr\Log\LoggerInterface;

/**
 * Simple background job, created when requested by an app during the
 * dispatch of MetadataLiveEvent.
 * This background job will re-run the event to refresh metadata on a non-live thread.
 *
 * @see MetadataLiveEvent::requestBackgroundJob()
 * @since 28.0.0
 */
class UpdateSingleMetadata extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private IRootFolder $rootFolder,
		private FilesMetadataManager $filesMetadataManager,
		private LoggerInterface $logger
	) {
		parent::__construct($time);
	}

	protected function run($argument) {
		[$userId, $fileId] = $argument;

		try {
			$node = $this->rootFolder->getUserFolder($userId)->getById($fileId);
			if (count($node) > 0) {
				$file = array_shift($node);
				$this->filesMetadataManager->refreshMetadata($file, IFilesMetadataManager::PROCESS_BACKGROUND);
			}
		} catch (\Exception $e) {
			$this->logger->warning('issue while running UpdateSingleMetadata', ['exception' => $e, 'userId' => $userId, 'fileId' => $fileId]);
		}
	}
}
