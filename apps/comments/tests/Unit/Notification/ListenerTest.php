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

namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\Notification\Listener;
use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Test\TestCase;

class ListenerTest extends TestCase {
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;

	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;

	/** @var  Listener */
	protected $listener;

	protected function setUp() {
		parent::setUp();

		$this->notificationManager = $this->getMockBuilder('\OCP\Notification\IManager')->getMock();
		$this->userManager = $this->getMockBuilder('\OCP\IUserManager')->getMock();
		$this->urlGenerator = $this->getMockBuilder('OCP\IURLGenerator')->getMock();

		$this->listener = new Listener(
			$this->notificationManager,
			$this->userManager,
			$this->urlGenerator
		);
	}

	public function eventProvider() {
		return [
			[CommentsEvent::EVENT_ADD, 'notify'],
			[CommentsEvent::EVENT_DELETE, 'markProcessed']
		];
	}

	/**
	 * @dataProvider eventProvider
	 * @param string $eventType
	 * @param string $notificationMethod
	 */
	public function testEvaluate($eventType, $notificationMethod) {
		$message = '@foobar and @barfoo you should know, @foo@bar.com is valid' .
			' and so is @bar@foo.org@foobar.io I hope that clarifies everything.' .
			' cc @23452-4333-54353-2342 @yolo!';

		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder('\OCP\Comments\IComment')->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->will($this->returnValue(new \DateTime()));
		$comment->expects($this->once())
			->method('getMessage')
			->will($this->returnValue($message));

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder('\OCP\Comments\CommentsEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->will($this->returnValue($comment));
		$event->expects(($this->any()))
			->method(('getEvent'))
			->will($this->returnValue($eventType));

		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('\OCP\Notification\INotification')->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->will($this->returnValue($notification));
		$notification->expects($this->exactly(6))
			->method('setUser');

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->will($this->returnValue($notification));
		$this->notificationManager->expects($this->exactly(6))
			->method($notificationMethod)
			->with($this->isInstanceOf('\OCP\Notification\INotification'));

		$this->userManager->expects($this->exactly(6))
			->method('userExists')
			->withConsecutive(
				['foobar'],
				['barfoo'],
				['foo@bar.com'],
				['bar@foo.org@foobar.io'],
				['23452-4333-54353-2342'],
				['yolo']
			)
			->will($this->returnValue(true));

		$this->listener->evaluate($event);
	}

	/**
	 * @dataProvider eventProvider
	 * @param string $eventType
	 */
	public function testEvaluateNoMentions($eventType) {
		$message = 'a boring comment without mentions';

		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder('\OCP\Comments\IComment')->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->will($this->returnValue(new \DateTime()));
		$comment->expects($this->once())
			->method('getMessage')
			->will($this->returnValue($message));

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder('\OCP\Comments\CommentsEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->will($this->returnValue($comment));
		$event->expects(($this->any()))
			->method(('getEvent'))
			->will($this->returnValue($eventType));

		$this->notificationManager->expects($this->never())
			->method('createNotification');
		$this->notificationManager->expects($this->never())
			->method('notify');
		$this->notificationManager->expects($this->never())
			->method('markProcessed');

		$this->userManager->expects($this->never())
			->method('userExists');

		$this->listener->evaluate($event);
	}

	public function testUnsupportedCommentObjectType() {
		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder('\OCP\Comments\IComment')->getMock();
		$comment->expects($this->once())
			->method('getObjectType')
			->will($this->returnValue('vcards'));
		$comment->expects($this->never())
			->method('getMessage');

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder('\OCP\Comments\CommentsEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->will($this->returnValue($comment));
		$event->expects(($this->any()))
			->method(('getEvent'))
			->will($this->returnValue(CommentsEvent::EVENT_ADD));

		$this->listener->evaluate($event);
	}

	public function testEvaluateUserDoesNotExist() {
		$message = '@foobar bla bla bla';

		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder('\OCP\Comments\IComment')->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->will($this->returnValue(new \DateTime()));
		$comment->expects($this->once())
			->method('getMessage')
			->will($this->returnValue($message));

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder('\OCP\Comments\CommentsEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->will($this->returnValue($comment));
		$event->expects(($this->any()))
			->method(('getEvent'))
			->will($this->returnValue(CommentsEvent::EVENT_ADD));

		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('\OCP\Notification\INotification')->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->will($this->returnValue($notification));
		$notification->expects($this->never())
			->method('setUser');

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->will($this->returnValue($notification));
		$this->notificationManager->expects($this->never())
			->method('notify');

		$this->userManager->expects($this->once())
			->method('userExists')
			->withConsecutive(
				['foobar']
			)
			->will($this->returnValue(false));

		$this->listener->evaluate($event);
	}

	/**
	 * @dataProvider eventProvider
	 * @param string $eventType
	 * @param string $notificationMethod
	 */
	public function testEvaluateOneMentionPerUser($eventType, $notificationMethod) {
		$message = '@foobar bla bla bla @foobar';

		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder('\OCP\Comments\IComment')->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->will($this->returnValue(new \DateTime()));
		$comment->expects($this->once())
			->method('getMessage')
			->will($this->returnValue($message));

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder('\OCP\Comments\CommentsEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->will($this->returnValue($comment));
		$event->expects(($this->any()))
			->method(('getEvent'))
			->will($this->returnValue($eventType));

		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('\OCP\Notification\INotification')->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->will($this->returnValue($notification));
		$notification->expects($this->once())
			->method('setUser');

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->will($this->returnValue($notification));
		$this->notificationManager->expects($this->once())
			->method($notificationMethod)
			->with($this->isInstanceOf('\OCP\Notification\INotification'));

		$this->userManager->expects($this->once())
			->method('userExists')
			->withConsecutive(
				['foobar']
			)
			->will($this->returnValue(true));

		$this->listener->evaluate($event);
	}

	/**
	 * @dataProvider eventProvider
	 * @param string $eventType
	 */
	public function testEvaluateNoSelfMention($eventType) {
		$message = '@foobar bla bla bla';

		/** @var IComment|\PHPUnit_Framework_MockObject_MockObject $comment */
		$comment = $this->getMockBuilder('\OCP\Comments\IComment')->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));
		$comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));
		$comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('foobar'));
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->will($this->returnValue(new \DateTime()));
		$comment->expects($this->once())
			->method('getMessage')
			->will($this->returnValue($message));

		/** @var CommentsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
		$event = $this->getMockBuilder('\OCP\Comments\CommentsEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->will($this->returnValue($comment));
		$event->expects(($this->any()))
			->method(('getEvent'))
			->will($this->returnValue($eventType));

		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('\OCP\Notification\INotification')->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->will($this->returnValue($notification));
		$notification->expects($this->never())
			->method('setUser');

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->will($this->returnValue($notification));
		$this->notificationManager->expects($this->never())
			->method('notify');
		$this->notificationManager->expects($this->never())
			->method('markProcessed');

		$this->userManager->expects($this->never())
			->method('userExists');

		$this->listener->evaluate($event);
	}

}
