<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Comments\Listener;

use OCA\Comments\Activity\Listener as ActivityListener;
use OCA\Comments\Notification\Listener as NotificationListener;
use OCP\Comments\CommentsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<CommentsEvent|Event> */
class CommentsEventListener implements IEventListener {
	public function __construct(
		private ActivityListener $activityListener,
		private NotificationListener $notificationListener,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof CommentsEvent) {
			return;
		}

		if ($event->getComment()->getObjectType() !== 'files') {
			// this is a 'files'-specific Handler
			return;
		}

		$eventType = $event->getEvent();
		if ($eventType === CommentsEvent::EVENT_ADD
		) {
			$this->notificationHandler($event);
			$this->activityHandler($event);
			return;
		}

		$applicableEvents = [
			CommentsEvent::EVENT_PRE_UPDATE,
			CommentsEvent::EVENT_UPDATE,
			CommentsEvent::EVENT_DELETE,
		];
		if (in_array($eventType, $applicableEvents)) {
			$this->notificationHandler($event);
			return;
		}
	}

	private function activityHandler(CommentsEvent $event): void {
		$this->activityListener->commentEvent($event);
	}

	private function notificationHandler(CommentsEvent $event): void {
		$this->notificationListener->evaluate($event);
	}
}
