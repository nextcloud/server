<?php

namespace OC\SpeechToText;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\Events\TranscriptionFailedEvent;
use OCP\SpeechToText\Events\TranscriptionSuccessfulEvent;
use OCP\SpeechToText\ISpeechToTextManager;

class TranscriptionJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private ISpeechToTextManager $speechToTextManager,
		private IEventDispatcher $eventDispatcher,
		private IRootFolder $rootFolder,
	) {
		parent::__construct($timeFactory);
	}


	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		$fileId = $argument['fileId'];
		try {
			$file = current($this->rootFolder->getById($fileId));
			if (!($file instanceof File)) {
				$this->eventDispatcher->dispatchTyped(
					new TranscriptionFailedEvent(
						$fileId,
						'File not found',
					)
				);
				return;
			}
			$result = $this->speechToTextManager->transcribeFile($file);
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionSuccessfulEvent(
					$fileId,
					$result,
				)
			);
		} catch (PreConditionNotMetException|\RuntimeException|\InvalidArgumentException $e) {
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionFailedEvent(
					$fileId,
					$e->getMessage(),
				)
			);
		}
	}
}
