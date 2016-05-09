<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Comments\Notification;

use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager;

class Listener {
	/** @var IManager */
	protected $notificationManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * Listener constructor.
	 *
	 * @param IManager $notificationManager
	 * @param IUserManager $userManager
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IManager $notificationManager,
		IUserManager $userManager,
		IURLGenerator $urlGenerator
	) {

		$this->notificationManager = $notificationManager;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param CommentsEvent $event
	 */
	public function evaluate(CommentsEvent $event) {
		$comment = $event->getComment();

		if($comment->getObjectType() !== 'files') {
			// comments App serves files only, other object types/apps need to
			// register their own ICommentsEventHandler and trigger notifications
			return;
		}

		$mentions = $this->extractMentions($comment->getMessage());
		if(empty($mentions)) {
			// no one to notify
			return;
		}

		$notification = $this->instantiateNotification($comment);

		foreach($mentions as $mention) {
			$user = substr($mention, 1); // @username → username
			if( ($comment->getActorType() === 'users' && $user === $comment->getActorId())
				|| !$this->userManager->userExists($user)
			) {
				// do not notify unknown users or yourself
				continue;
			}

			$notification->setUser($user);
			if($event->getEvent() === CommentsEvent::EVENT_DELETE) {
				$this->notificationManager->markProcessed($notification);
			} else {
				$this->notificationManager->notify($notification);
			}
		}
	}

	/**
	 * creates a notification instance and fills it with comment data
	 *
	 * @param IComment $comment
	 * @return \OCP\Notification\INotification
	 */
	public function instantiateNotification(IComment $comment) {
		$notification = $this->notificationManager->createNotification();
		$notification
			->setApp('comments')
			->setObject('comment', $comment->getId())
			->setSubject('mention', [ $comment->getObjectType(), $comment->getObjectId() ])
			->setDateTime($comment->getCreationDateTime())
			->setLink($this->urlGenerator->linkToRouteAbsolute(
				'comments.Notifications.view',
				['id' => $comment->getId()])
			);

		return $notification;
	}

	/**
	 * extracts @-mentions out of a message body.
	 *
	 * @param string $message
	 * @return string[] containing the mentions, e.g. ['@alice', '@bob']
	 */
	public function extractMentions($message) {
		$ok = preg_match_all('/\B@[a-z0-9_\-@\.\']+/i', $message, $mentions);
		if(!$ok || !isset($mentions[0]) || !is_array($mentions[0])) {
			return [];
		}
		return array_unique($mentions[0]);
	}
}
