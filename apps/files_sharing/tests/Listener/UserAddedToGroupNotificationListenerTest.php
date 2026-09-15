<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Listener;

use OCA\Files_Sharing\Listener\UserAddedToGroupNotificationListener;
use OCA\Files_Sharing\Notification\Notifier;
use OCP\EventDispatcher\Event;
use OCP\Group\Events\UserAddedEvent;
use OCP\IGroup;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserAddedToGroupNotificationListenerTest extends TestCase {
	private INotificationManager&MockObject $notificationManager;
	private IShareManager&MockObject $shareManager;
	private UserAddedToGroupNotificationListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->listener = new UserAddedToGroupNotificationListener(
			$this->notificationManager,
			$this->shareManager,
		);
	}

	private function getEvent(): UserAddedEvent {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('recipient');
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('joined-group');

		return new UserAddedEvent($group, $user);
	}

	private function getShare(string $sharedWith, string $owner = 'owner', string $sharedBy = 'owner'): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getShareOwner')->willReturn($owner);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getFullId')->willReturn('ocinternal:1');
		$share->method('getShareTime')->willReturn(new \DateTime());

		return $share;
	}

	private function expectNotification(): INotification&MockObject {
		$notification = $this->createMock(INotification::class);
		$notification->method('setApp')->willReturnSelf();
		$notification->method('setObject')->willReturnSelf();
		$notification->method('setDateTime')->willReturnSelf();
		$notification->method('setUser')->willReturnSelf();
		$notification->expects($this->once())
			->method('setSubject')
			->with(Notifier::INCOMING_GROUP_SHARE)
			->willReturnSelf();
		$this->notificationManager->method('createNotification')->willReturn($notification);

		return $notification;
	}

	public function testNotifiesAboutSharesOfTheJoinedGroup(): void {
		$this->shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls([$this->getShare('joined-group')], []);

		$this->expectNotification();
		$this->notificationManager->expects($this->once())->method('notify');

		$this->listener->handle($this->getEvent());
	}

	public function testIgnoresSharesOfOtherGroups(): void {
		$this->shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls([$this->getShare('other-group')], []);

		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle($this->getEvent());
	}

	public function testIgnoresOwnShares(): void {
		$this->shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls([
				$this->getShare('joined-group', 'recipient', 'recipient'),
				$this->getShare('joined-group', 'owner', 'recipient'),
			], []);

		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle($this->getEvent());
	}

	public function testPaginatesUntilNoSharesAreLeft(): void {
		$page = array_fill(0, 50, $this->getShare('other-group'));
		$this->shareManager->expects($this->exactly(3))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls($page, $page, []);

		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle($this->getEvent());
	}

	public function testIgnoresUnrelatedEvents(): void {
		$this->shareManager->expects($this->never())->method('getSharedWith');
		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle(new Event());
	}
}
