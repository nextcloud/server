<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Comments\Notification;

use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

class Listener {
	public function __construct(
		protected IManager $notificationManager,
		protected IUserManager $userManager
	) {
	}

	public function evaluate(CommentsEvent $event): void {
		$comment = $event->getComment();

		$mentions = $this->extractMentions($comment->getMentions());
		if (empty($mentions)) {
			// no one to notify
			return;
		}

		$notification = $this->instantiateNotification($comment);

		foreach ($mentions as $uid) {
			if (($comment->getActorType() === 'users' && $uid === $comment->getActorId())
				|| !$this->userManager->userExists($uid)
			) {
				// do not notify unknown users or yourself
				continue;
			}

			$notification->setUser($uid);
			if ($event->getEvent() === CommentsEvent::EVENT_DELETE
				|| $event->getEvent() === CommentsEvent::EVENT_PRE_UPDATE) {
				$this->notificationManager->markProcessed($notification);
			} else {
				$this->notificationManager->notify($notification);
			}
		}
	}

	/**
	 * Creates a notification instance and fills it with comment data
	 */
	public function instantiateNotification(IComment $comment): INotification {
		$notification = $this->notificationManager->createNotification();
		$notification
			->setApp('comments')
			->setObject('comment', $comment->getId())
			->setSubject('mention', [ $comment->getObjectType(), $comment->getObjectId() ])
			->setDateTime($comment->getCreationDateTime());

		return $notification;
	}

	/**
	 * Flattens the mention array returned from comments to a list of user ids.
	 *
	 * @param array $mentions
	 * @return list<string> containing the mentions, e.g. ['alice', 'bob']
	 */
	public function extractMentions(array $mentions): array {
		if (empty($mentions)) {
			return [];
		}
		$uids = [];
		foreach ($mentions as $mention) {
			if ($mention['type'] === 'user') {
				$uids[] = $mention['id'];
			}
		}
		return $uids;
	}
}
