<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TaskProcessing\Listeners;

use OC\TaskProcessing\Manager;
use OC\TextProcessing\Db\TaskMapper as TextProcessingTaskMapper;
use OC\TextToImage\Manager as TextToImageManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Cleans up the AI tasks of a deleted user in the task processing,
 * text processing and text to image tables, together with the
 * files they generated in appdata.
 *
 * @template-implements IEventListener<UserDeletedEvent>
 */
class UserDeletedTaskCleanupListener implements IEventListener {
	public function __construct(
		private Manager $taskProcessingManager,
		private TextProcessingTaskMapper $textProcessingTaskMapper,
		private TextToImageManager $textToImageManager,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			// Unrelated
			return;
		}

		$uid = $event->getUser()->getUID();

		/**
		 * Catch any exception during each of these steps as a failure here
		 * should not block the deletion of the user account.
		 */
		try {
			$this->taskProcessingManager->deleteUserTasks($uid);
		} catch (Throwable $e) {
			$this->logger->warning('Could not clean up task processing tasks after user deletion: ' . $e->getMessage(), ['exception' => $e]);
		}
		try {
			$this->textProcessingTaskMapper->deleteByUser($uid);
		} catch (Throwable $e) {
			$this->logger->warning('Could not clean up text processing tasks after user deletion: ' . $e->getMessage(), ['exception' => $e]);
		}
		try {
			$this->textToImageManager->deleteUserTasks($uid);
		} catch (Throwable $e) {
			$this->logger->warning('Could not clean up text to image tasks after user deletion: ' . $e->getMessage(), ['exception' => $e]);
		}
	}
}
