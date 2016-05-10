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

namespace OCA\Comments\Tests\Unit\Controller;

use OCA\Comments\Controller\Notifications;
use Test\TestCase;

class NotificationsTest extends TestCase {
	/** @var  \OCA\Comments\Controller\Notifications */
	protected $notificationsController;

	/** @var  \OCP\Comments\ICommentsManager */
	protected $commentsManager;

	/** @var  \OCP\Files\Folder */
	protected $folder;

	/** @var \OCP\IUserSession */
	protected $session;

	/** @var \OCP\Notification\IManager */
	protected $notificationManager;

	protected function setUp() {
		parent::setUp();

		$this->commentsManager = $this->getMock('\OCP\Comments\ICommentsManager');
		$this->folder = $this->getMock('\OCP\Files\Folder');
		$this->session = $this->getMock('\OCP\IUserSession');
		$this->notificationManager = $this->getMock('\OCP\Notification\IManager');

		$this->notificationsController = new Notifications(
			'comments',
			$this->getMock('\OCP\IRequest'),
			$this->commentsManager,
			$this->folder,
			$this->getMock('\OCP\IURLGenerator'),
			$this->notificationManager,
			$this->session
		);
	}
	
	public function testViewSuccess() {
		$comment = $this->getMock('\OCP\Comments\IComment');
		$comment->expects($this->any())
			->method('getObjectType')
			->will($this->returnValue('files'));

		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->will($this->returnValue($comment));

		$file = $this->getMock('\OCP\Files\Node');
		$file->expects($this->once())
			->method('getParent')
			->will($this->returnValue($this->getMock('\OCP\Files\Folder')));

		$this->folder->expects($this->once())
			->method('getById')
			->will($this->returnValue([$file]));

		$this->session->expects($this->once())
			->method('getUser')
			->will($this->returnValue($this->getMock('\OCP\IUser')));

		$notification = $this->getMock('\OCP\Notification\INotification');
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
			->will($this->throwException(new \OCP\Comments\NotFoundException()));

		$file = $this->getMock('\OCP\Files\Node');
		$file->expects($this->never())
			->method('getParent');

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
		$comment = $this->getMock('\OCP\Comments\IComment');
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
			->will($this->returnValue($this->getMock('\OCP\IUser')));

		$notification = $this->getMock('\OCP\Notification\INotification');
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
