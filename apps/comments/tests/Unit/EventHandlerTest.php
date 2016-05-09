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

namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\AppInfo\Application;
use OCA\Comments\EventHandler;
use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCA\Comments\Activity\Listener as ActivityListener;
use OCA\Comments\Notification\Listener as NotificationListener;
use OCP\IContainer;
use Test\TestCase;

class EventHandlerTest extends TestCase {
	/** @var  EventHandler */
	protected $eventHandler;

	/** @var Application|\PHPUnit_Framework_MockObject_MockObject */
	protected $app;

	protected function setUp() {
		parent::setUp();

		$this->app = $this->getMockBuilder(Application::class)
			->disableOriginalConstructor()
			->getMock();

		$this->eventHandler = new EventHandler($this->app);
	}

	public function testNotFiles() {
		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('smiles');

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects($this->never())
			->method('getEvent');

		$this->eventHandler->handle($event);
	}

	public function notHandledProvider() {
		return [
			[CommentsEvent::EVENT_DELETE],
			[CommentsEvent::EVENT_UPDATE]
		];
	}

	/**
	 * @dataProvider notHandledProvider
	 * @param $eventType
	 */
	public function testNotHandled($eventType) {
		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('files');

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects($this->once())
			->method('getEvent')
			->willReturn($eventType);

		// further processing does not happen, because $event methods cannot be
		// access more than once.
		$this->eventHandler->handle($event);
	}

	public function testHandled() {
		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('files');

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->atLeastOnce())
			->method('getComment')
			->willReturn($comment);
		$event->expects($this->atLeastOnce())
			->method('getEvent')
			->willReturn(CommentsEvent::EVENT_ADD);

		$notificationListener = $this->getMockBuilder(NotificationListener::class)
			->disableOriginalConstructor()
			->getMock();
		$notificationListener->expects($this->once())
			->method('evaluate')
			->with($event);

		$activityListener = $this->getMockBuilder(ActivityListener::class)
			->disableOriginalConstructor()
			->getMock();
		$activityListener->expects($this->once())
			->method('commentEvent')
			->with($event);

		/** @var IContainer|\PHPUnit_Framework_MockObject_MockObject $c */
		$c = $this->getMockBuilder(IContainer::class)->getMock();
		$c->expects($this->exactly(2))
			->method('query')
			->withConsecutive([NotificationListener::class], [ActivityListener::class])
			->willReturnOnConsecutiveCalls($notificationListener, $activityListener);

		$this->app->expects($this->once())
			->method('getContainer')
			->willReturn($c);

		$this->eventHandler->handle($event);
	}

}
