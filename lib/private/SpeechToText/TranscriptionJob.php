<?php

namespace OC\SpeechToText;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\Events\TranscriptionFinishedEvent;
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
		try {
			$file = $this->rootFolder->getById($argument['fileId']);
			$result = $this->speechToTextManager->transcribeFile($file);
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionFinishedEvent(
					true,
					$result,
					'',
					$argument['context']
				)
			);
		} catch (PreConditionNotMetException|\RuntimeException|\InvalidArgumentException $e) {
			$this->eventDispatcher->dispatchTyped(
				new TranscriptionFinishedEvent(
					false,
					'',
					$e->getMessage(),
					$argument['context']
				)
			);
		}
	}
}
