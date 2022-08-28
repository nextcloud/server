<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */
namespace OCA\Comments;

use OCA\Comments\Activity\Listener as ActivityListener;
use OCA\Comments\Notification\Listener as NotificationListener;
use OCP\Comments\CommentsEvent;
use OCP\Comments\ICommentsEventHandler;

/**
 * Class EventHandler
 *
 * @package OCA\Comments
 */
class EventHandler implements ICommentsEventHandler {
	public function __construct(
		private ActivityListener $activityListener,
		private NotificationListener $notificationListener,
	) {
	}

	public function handle(CommentsEvent $event): void {
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
