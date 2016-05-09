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

use OCA\Comments\Notification\Notifier;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use Test\TestCase;

class NotifierTest extends TestCase {

	/** @var Notifier */
	protected $notifier;

	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $l10nFactory;

	/** @var  Folder|\PHPUnit_Framework_MockObject_MockObject */
	protected $folder;

	/** @var  ICommentsManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $commentsManager;

	/** @var  IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;


	/** @var string */
	protected $lc = 'tlh_KX';

	/** @var  INotification|\PHPUnit_Framework_MockObject_MockObject */
	protected $notification;

	/** @var  IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;

	/** @var  IComment|\PHPUnit_Framework_MockObject_MockObject */
	protected $comment;

	protected function setUp() {
		parent::setUp();

		$this->l10nFactory = $this->getMockBuilder('OCP\L10N\IFactory')->getMock();
		$this->folder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->commentsManager = $this->getMockBuilder('OCP\Comments\ICommentsManager')->getMock();
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')->getMock();

		$this->notifier = new Notifier(
			$this->l10nFactory,
			$this->folder,
			$this->commentsManager,
			$this->userManager
		);

		$this->l = $this->getMockBuilder('OCP\IL10N')->getMock();
		$this->notification = $this->getMockBuilder('OCP\Notification\INotification')->getMock();
		$this->comment = $this->getMockBuilder('OCP\Comments\IComment')->getMock();
	}

	public function testPrepareSuccess() {
		$fileName = 'Gre\'thor.odp';
		$displayName = 'Huraga';
		$message = 'You were mentioned in a comment on "Gre\'thor.odp" by Huraga.';

		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$user->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayName);

		/** @var Node|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getMockBuilder('OCP\Files\Node')->getMock();
		$node
			->expects($this->once())
			->method('getName')
			->willReturn($fileName);

		$this->folder
			->expects($this->once())
			->method('getById')
			->with('678')
			->willReturn([$node]);

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['files', '678']);
		$this->notification
			->expects($this->once())
			->method('setParsedSubject')
			->with($message);

		$this->l
			->expects($this->once())
			->method('t')
			->with('You were mentioned in a comment on "%s" by %s.', [$fileName, $displayName])
			->willReturn($message);

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects(($this->once()))
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('huraga')
			->willReturn($user);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareSuccessDeletedUser() {
		$fileName = 'Gre\'thor.odp';
		$displayName = 'a now deleted user';
		$message = 'You were mentioned in a comment on "Gre\'thor.odp" by a now deleted user.';

		/** @var Node|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getMockBuilder('OCP\Files\Node')->getMock();
		$node
			->expects($this->once())
			->method('getName')
			->willReturn($fileName);

		$this->folder
			->expects($this->once())
			->method('getById')
			->with('678')
			->willReturn([$node]);

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['files', '678']);
		$this->notification
			->expects($this->once())
			->method('setParsedSubject')
			->with($message);

		$this->l
			->expects($this->once())
			->method('t')
			->with('You were mentioned in a comment on "%s" by a now deleted user.', [ $fileName ])
			->willReturn($message);

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn(ICommentsManager::DELETED_USER);

		$this->commentsManager
			->expects(($this->once()))
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->never())
			->method('get');

		$this->notifier->prepare($this->notification, $this->lc);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareDifferentApp() {
		$this->folder
			->expects($this->never())
			->method('getById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('constructions');
		$this->notification
			->expects($this->never())
			->method('getSubject');
		$this->notification
			->expects($this->never())
			->method('getSubjectParameters');
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->l10nFactory
			->expects($this->never())
			->method('get');

		$this->commentsManager
			->expects(($this->never()))
			->method('get');

		$this->userManager
			->expects($this->never())
			->method('get');

		$this->notifier->prepare($this->notification, $this->lc);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareNotFound() {
		$this->folder
			->expects($this->never())
			->method('getById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->never())
			->method('getSubject');
		$this->notification
			->expects($this->never())
			->method('getSubjectParameters');
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->l10nFactory
			->expects($this->never())
			->method('get');

		$this->commentsManager
			->expects(($this->once()))
			->method('get')
			->willThrowException(new NotFoundException());

		$this->userManager
			->expects($this->never())
			->method('get');

		$this->notifier->prepare($this->notification, $this->lc);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareDifferentSubject() {
		$displayName = 'Huraga';

		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$user->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayName);

		$this->folder
			->expects($this->never())
			->method('getById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('unlike');
		$this->notification
			->expects($this->never())
			->method('getSubjectParameters');
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->l
			->expects($this->never())
			->method('t');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects(($this->once()))
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('huraga')
			->willReturn($user);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareNotFiles() {
		$displayName = 'Huraga';

		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$user->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayName);

		$this->folder
			->expects($this->never())
			->method('getById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['ships', '678']);
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->l
			->expects($this->never())
			->method('t');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects(($this->once()))
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('huraga')
			->willReturn($user);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareUnresolvableFileID() {
		$displayName = 'Huraga';

		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$user->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayName);

		$this->folder
			->expects($this->once())
			->method('getById')
			->with('678')
			->willReturn([]);

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['files', '678']);
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->l
			->expects($this->never())
			->method('t');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects(($this->once()))
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('huraga')
			->willReturn($user);

		$this->notifier->prepare($this->notification, $this->lc);
	}

}
