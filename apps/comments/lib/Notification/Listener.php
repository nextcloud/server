<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
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
			// we only support notifications for files at this point
			return;
		}

		$ok = preg_match_all('/\B@[a-z0-9_-]+/i', $comment->getMessage(), $mentions);
		if(!$ok || !isset($mentions[0]) || !is_array($mentions[0])) {
			return;
		}

		foreach($mentions[0] as $mention) {
			$user = substr($mention, 1); // @username → username
			if( ($comment->getActorType() === 'users' && $user === $comment->getActorId())
				|| !$this->userManager->userExists($user)
			) {
				// do not notify unknown users or yourself
				continue;
			}
			$notification = $this->notificationManager->createNotification();
			$notification
				->setApp('comments')
				->setUser($user)
				->setObject('comment', $comment->getId())
				->setSubject('mention', [ $comment->getObjectType(), $comment->getObjectId() ])
				->setDateTime($comment->getCreationDateTime())
				->setLink($this->urlGenerator->linkToRouteAbsolute(
					'comments.Notifications.view',
					['id' => $comment->getId()])
				);

			$this->notificationManager->notify($notification);
		}
	}
}
