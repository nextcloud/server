<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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


namespace OC\SpeechToText;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\ICachedMountFileInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\Events\TranscriptionFailedEvent;
use OCP\SpeechToText\Events\TranscriptionSuccessfulEvent;
use OCP\SpeechToText\ISpeechToTextManager;
use Psr\Log\LoggerInterface;

class TranscriptionJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private ISpeechToTextManager $speechToTextManager,
		private IEventDispatcher $eventDispatcher,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
		private IUserMountCache $userMountCache,
	) {
		parent::__construct($timeFactory);
	}


	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		$fileId = $argument['fileId'];
		$file = null;
		try {
			$file = $this->getFileFromId($fileId);
			if (!($file instanceof File)) {
				$this->logger->warning('Transcription of file ' . $fileId . ' failed. The file could not be found');
				$this->eventDispatcher->dispatchTyped(
					new TranscriptionFailedEvent(
						$fileId,
						null,
						'File not found',
					)
				);
				return;
			}
			$result = $this->speechToTextManager->transcribeFile($file);
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionSuccessfulEvent(
					$fileId,
					$file,
					$result,
				)
			);
		} catch (PreConditionNotMetException|\RuntimeException|\InvalidArgumentException|NotFoundException $e) {
			$this->logger->warning('Transcription of file ' . $fileId . ' failed', ['exception' => $e]);
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionFailedEvent(
					$fileId,
					$file,
					$e->getMessage(),
				)
			);
		}
	}

	/**
	 * @throws NotFoundException
	 */
	private function getFileFromId(int $fileId): Node {
		$mountPoints = $this->userMountCache->getMountsForFileId($fileId);
		if (empty($mountPoints)) {
			throw new NotFoundException("No mount points found for file $fileId");
		}

		foreach ($mountPoints as $mountPoint) {
			try {
				return $this->getCreatableNodeFromMountPoint($mountPoint, $fileId);
			} catch (NotPermittedException $e) {
				// Check the next mount point
				$this->logger->debug('Mount point ' . ($mountPoint->getMountId() ?? 'null') . ' has no delete permissions for file ' . $fileId);
			} catch (NotFoundException $e) {
				// Already logged explicitly inside
			}
		}

		throw new NotFoundException("No mount point with delete permissions found for file $fileId");
	}

	/**
	 * @throws NotFoundException
	 */
	protected function getCreatableNodeFromMountPoint(ICachedMountFileInfo $mountPoint, int $fileId): Node {
			try {
				$userId = $mountPoint->getUser()->getUID();
				$userFolder = $this->rootFolder->getUserFolder($userId);
				\OC_Util::setupFS($userId);
			} catch (\Exception $e) {
				$this->logger->debug($e->getMessage(), [
					'exception' => $e,
				]);
				throw new NotFoundException('Could not get user', 0, $e);
			}

		$nodes = $userFolder->getById($fileId);
		if (empty($nodes)) {
			throw new NotFoundException('No node for file ' . $fileId . ' and user ' . $userId);
		}

		return array_shift($nodes);
	}
}
