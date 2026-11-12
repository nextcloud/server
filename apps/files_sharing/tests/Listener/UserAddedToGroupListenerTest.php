<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Listener\UserAddedToGroupListener;
use OCA\Files_Sharing\Notification\Notifier;
use OCP\EventDispatcher\Event;
use OCP\Group\Events\UserAddedEvent;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\Mock\Config\MockUserConfig;
use Test\TestCase;

class UserAddedToGroupListenerTest extends TestCase {
	/** @var array<string, bool> */
	private array $systemConfig = [];
	private IConfig&MockObject $config;
	private MockUserConfig $userConfig;
	private IShareManager&MockObject $shareManager;
	private INotificationManager&MockObject $notificationManager;
	private UserAddedToGroupListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->systemConfig = [];
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValueBool')
			->willReturnCallback(fn (string $key, bool $default = false): bool => $this->systemConfig[$key] ?? $default);
		$this->userConfig = new MockUserConfig([]);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);

		$this->listener = new UserAddedToGroupListener(
			$this->config,
			$this->userConfig,
			$this->shareManager,
			$this->notificationManager,
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

	private function enableAutoAccept(): void {
		$this->userConfig->config = ['recipient' => [Application::APP_ID => ['default_accept' => true]]];
	}

	private function disableAutoAccept(): void {
		$this->userConfig->config = ['recipient' => [Application::APP_ID => ['default_accept' => false]]];
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

	public function testIgnoresUnrelatedEvents(): void {
		$this->shareManager->expects($this->never())->method('getSharedWith');
		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle(new Event());
	}

	/**
	 * @return array<string, array{bool, ?bool, bool, bool}>
	 */
	public static function dataAcceptMode(): array {
		return [
			// system default, user value, force accept, expected auto accept
			'auto accept by default' => [false, null, false, true],
			'accept required by system default' => [true, null, false, false],
			'auto accept enabled by user' => [true, true, false, true],
			'auto accept disabled by user' => [false, false, false, false],
			'forced share accept wins' => [false, true, true, false],
		];
	}

	#[DataProvider('dataAcceptMode')]
	public function testAcceptModeFollowsConfiguration(bool $systemDefault, ?bool $userValue, bool $forceAccept, bool $expectAutoAccept): void {
		$this->systemConfig = [
			'sharing.enable_share_accept' => $systemDefault,
			'sharing.force_share_accept' => $forceAccept,
		];
		if ($userValue !== null) {
			$this->userConfig->config = ['recipient' => [Application::APP_ID => ['default_accept' => $userValue]]];
		}

		$share = $this->getShare('joined-group');
		if ($expectAutoAccept) {
			$this->shareManager->expects($this->once())
				->method('getSharedWith')
				->with('recipient', IShare::TYPE_GROUP, null, -1)
				->willReturn([$share]);
			$this->shareManager->expects($this->once())->method('acceptShare');
			$this->notificationManager->expects($this->never())->method('notify');
		} else {
			$this->shareManager->expects($this->exactly(2))
				->method('getSharedWith')
				->willReturnOnConsecutiveCalls([$share], []);
			$this->shareManager->expects($this->never())->method('acceptShare');
			$this->expectNotification();
			$this->notificationManager->expects($this->once())->method('notify');
		}

		$this->listener->handle($this->getEvent());
	}

	public function testAcceptsSharesOfTheJoinedGroup(): void {
		$this->enableAutoAccept();
		$share = $this->getShare('joined-group');

		$this->shareManager->expects($this->once())
			->method('getSharedWith')
			->with('recipient', IShare::TYPE_GROUP, null, -1)
			->willReturn([$share]);
		$this->shareManager->expects($this->once())
			->method('acceptShare')
			->with($share, 'recipient');

		$this->listener->handle($this->getEvent());
	}

	public function testDoesNotAcceptSharesOfOtherGroups(): void {
		$this->enableAutoAccept();

		$this->shareManager->expects($this->once())
			->method('getSharedWith')
			->willReturn([$this->getShare('other-group')]);
		$this->shareManager->expects($this->never())->method('acceptShare');

		$this->listener->handle($this->getEvent());
	}

	public function testNotifiesAboutSharesOfTheJoinedGroup(): void {
		$this->disableAutoAccept();
		$this->shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls([$this->getShare('joined-group')], []);

		$this->expectNotification();
		$this->notificationManager->expects($this->once())->method('notify');

		$this->listener->handle($this->getEvent());
	}

	public function testIgnoresSharesOfOtherGroups(): void {
		$this->disableAutoAccept();
		$this->shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls([$this->getShare('other-group')], []);

		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle($this->getEvent());
	}

	public function testIgnoresOwnShares(): void {
		$this->disableAutoAccept();
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
		$this->disableAutoAccept();
		$page = array_fill(0, 50, $this->getShare('other-group'));
		$this->shareManager->expects($this->exactly(3))
			->method('getSharedWith')
			->willReturnOnConsecutiveCalls($page, $page, []);

		$this->notificationManager->expects($this->never())->method('notify');

		$this->listener->handle($this->getEvent());
	}
}
