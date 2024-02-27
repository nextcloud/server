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

use OC\User\NoUserException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IRootFolder;
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
	) {
		parent::__construct($timeFactory);
		$this->setAllowParallelRuns(false);
	}


	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		$fileId = $argument['fileId'];
		$owner = $argument['owner'];
		$userId = $argument['userId'];
		$appId = $argument['appId'];
		$file = null;
		try {
			\OC_Util::setupFS($owner);
			$userFolder = $this->rootFolder->getUserFolder($owner);
			$file = current($userFolder->getById($fileId));
			if (!($file instanceof File)) {
				$this->logger->warning('Transcription of file ' . $fileId . ' failed. The file could not be found');
				$this->eventDispatcher->dispatchTyped(
					new TranscriptionFailedEvent(
						$fileId,
						null,
						'File not found',
						$userId,
						$appId,
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
					$userId,
					$appId,
				)
			);
		} catch (PreConditionNotMetException|\RuntimeException|\InvalidArgumentException|NotFoundException|NotPermittedException|NoUserException $e) {
			$this->logger->warning('Transcription of file ' . $fileId . ' failed', ['exception' => $e]);
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionFailedEvent(
					$fileId,
					$file,
					$e->getMessage(),
					$userId,
					$appId,
				)
			);
		}
	}
}
