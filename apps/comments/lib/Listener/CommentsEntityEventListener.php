<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Listener;

use OCP\Comments\CommentsEntityEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\IRootFolder;

/** @template-implements IEventListener<CommentsEntityEvent> */
class CommentsEntityEventListener implements IEventListener {
	public function __construct(
		private IRootFolder $rootFolder,
		private ?string $userId = null,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof CommentsEntityEvent)) {
			// Unrelated
			return;
		}

		$event->addEntityCollection('files', function ($name): bool {
			$nodes = $this->rootFolder->getUserFolder($this->userId)->getById((int)$name);
			return !empty($nodes);
		});
	}
}
