<?php

/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Comments;

use OCA\Comments\Activity\Listener as ActivityListener;
use OCA\Comments\AppInfo\Application;
use OCA\Comments\Notification\Listener as NotificationListener;
use OCP\Comments\CommentsEvent;
use OCP\Comments\ICommentsEventHandler;

/**
 * Class EventHandler
 *
 * @package OCA\Comments
 */
class EventHandler implements ICommentsEventHandler {

	/** @var Application */
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	/**
	 * @param CommentsEvent $event
	 */
	public function handle(CommentsEvent $event) {
		if($event->getComment()->getObjectType() !== 'files') {
			// this is a 'files'-specific Handler
			return;
		}

		if( $event->getEvent() === CommentsEvent::EVENT_ADD
			&& $event instanceof CommentsEvent
		) {
			$this->onAdd($event);
			return;
		}
	}

	/**
	 * @param CommentsEvent $event
	 */
	private function onAdd(CommentsEvent $event) {
		$c = $this->app->getContainer();

		/** @var NotificationListener $notificationListener */
		$notificationListener = $c->query(NotificationListener::class);
		$notificationListener->evaluate($event);

		/** @var ActivityListener $listener */
		$activityListener = $c->query(ActivityListener::class);
		$activityListener->commentEvent($event);
	}
}
