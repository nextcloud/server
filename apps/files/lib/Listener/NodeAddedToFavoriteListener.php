<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Listener;

use OCA\Files\Activity\FavoriteProvider;
use OCP\Activity\IManager as IActivityManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\NodeAddedToFavorite;

/** @template-implements IEventListener<NodeAddedToFavorite> */
class NodeAddedToFavoriteListener implements IEventListener {
	public function __construct(
		private IActivityManager $activityManager,
	) {
	}
	public function handle(Event $event):void {
		if (!($event instanceof NodeAddedToFavorite)) {
			return;
		}
		$activityEvent = $this->activityManager->generateEvent();
		try {
			$activityEvent->setApp('files')
				->setObject('files', $event->getFileId(), $event->getPath())
				->setType('favorite')
				->setAuthor($event->getUser()->getUID())
				->setAffectedUser($event->getUser()->getUID())
				->setTimestamp(time())
				->setSubject(
					FavoriteProvider::SUBJECT_ADDED,
					['id' => $event->getFileId(), 'path' => $event->getPath()]
				);
			$this->activityManager->publish($activityEvent);
		} catch (\InvalidArgumentException $e) {
		} catch (\BadMethodCallException $e) {
		}
	}
}
