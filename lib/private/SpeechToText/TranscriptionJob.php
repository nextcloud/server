<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			$file = $userFolder->getFirstNodeById($fileId);
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
			$result = $this->speechToTextManager->transcribeFile($file, $userId, $appId);
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
