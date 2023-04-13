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
