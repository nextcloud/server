<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Preview\BeforePreviewFetchedEvent;

/**
 * @template-implements IEventListener<BeforePreviewFetchedEvent>
 */
class FileEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof BeforePreviewFetchedEvent) {
			$this->beforePreviewFetched($event);
		}
	}

	private function beforePreviewFetched(BeforePreviewFetchedEvent $event): void {
		$file = $event->getNode();

		$this->log(
			'Preview accessed: "%s" (width: "%s", height: "%s" crop: "%s", mode: "%s")',
			[
				'path' => mb_substr($file->getInternalPath(), 5),
				'width' => $event->getWidth(),
				'height' => $event->getHeight(),
				'crop' => $event->isCrop(),
				'mode' => $event->getMode(),
			],
			[
				'path',
				'width',
				'height',
				'crop',
				'mode'
			]
		);
	}
}
