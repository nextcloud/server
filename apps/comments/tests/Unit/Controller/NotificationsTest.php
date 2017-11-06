<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Comments\Tests\Unit\Controller;

use OCA\Comments\Controller\Notifications;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Test\TestCase;

class NotificationsTest extends TestCase {
	/** @var  \OCA\Comments\Controller\Notifications */
	protected $notificationsController;

	/** @var  \OCP\Comments\ICommentsManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $commentsManager;

	/** @var  \OCP\Files\Folder|\PHPUnit_Framework_MockObject_MockObject */
	protected $folder;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	/** @var \OCP\Notification\IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;

	protected function setUp() {
		parent::setUp();

		$this->commentsManager = $this->getMockBuilder(ICommentsManager::class)->getMock();
		$this->folder = $this->getMockBuilder(Folder::class)->getMock();
		$this->session = $this->getMockBuilder(IUserSession::class)->getMock();
		$this->notificationManager = $this->getMockBuilder(IManager::class)->getMock();

		$this->notificationsController = new Notifications(
			'comments',
			$this->getMockBuilder(IRequest::class)->getMock(),
			$this->commentsManager,
			$this->folder,
			$this->getMockBuilder(IURLGenerator::class)->getMock(),
			$this->notificationManager,
			$this->session
		);
	}
	
	public function testViewSuccess() {
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));

		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->will($this->returnValue($comment));

		$file = $this->getMockBuilder(Node::class)->getMock();

		$this->folder->expects($this->once())
			->method('getById')
			->will($this->returnValue([$file]));

		$this->session->expects($this->once())
			->method('getUser')
			->will($this->returnValue($this->getMockBuilder(IUser::class)->getMock()));

		$notification = $this->getMockBuilder(INotification::class)->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->will($this->returnValue($notification));

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->will($this->returnValue($notification));
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf('\OCP\AppFramework\Http\RedirectResponse', $response);
	}

	public function testViewInvalidComment() {
		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->will($this->throwException(new NotFoundException()));

		$this->folder->expects($this->never())
			->method('getById');

		$this->session->expects($this->never())
			->method('getUser');

		$this->notificationManager->expects($this->never())
			->method('createNotification');
		$this->notificationManager->expects($this->never())
			->method('markProcessed');

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf('\OCP\AppFramework\Http\NotFoundResponse', $response);
	}

	public function testViewNoFile() {
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));

		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->will($this->returnValue($comment));

		$this->folder->expects($this->once())
			->method('getById')
			->will($this->returnValue([]));

		$this->session->expects($this->once())
			->method('getUser')
			->will($this->returnValue($this->getMockBuilder(IUser::class)->getMock()));

		$notification = $this->getMockBuilder(INotification::class)->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->will($this->returnValue($notification));

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->will($this->returnValue($notification));
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf('\OCP\AppFramework\Http\NotFoundResponse', $response);
	}
}
