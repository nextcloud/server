<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\Config\ConfigLexicon;
use OCA\Files_Sharing\Event\UserShareAccessUpdatedEvent;
use OCA\Files_Sharing\Listener\SharesUpdatedListener;
use OCA\Files_Sharing\ShareRecipientUpdater;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Test\Mock\Config\MockAppConfig;
use Test\Mock\Config\MockUserConfig;
use Test\Traits\UserTrait;

class SharesUpdatedListenerTest extends \Test\TestCase {
	use UserTrait;

	private SharesUpdatedListener $sharesUpdatedListener;
	private ShareRecipientUpdater&MockObject $shareRecipientUpdater;
	private IManager&MockObject $manager;
	private IUserConfig $userConfig;
	private IAppConfig $appConfig;
	private ClockInterface&MockObject $clock;
	private $clockFn;

	protected function setUp(): void {
		parent::setUp();

		$this->shareRecipientUpdater = $this->createMock(ShareRecipientUpdater::class);
		$this->manager = $this->createMock(IManager::class);
		$this->appConfig = new MockAppConfig([
			ConfigLexicon::UPDATE_CUTOFF_TIME => -1,
		]);
		$this->userConfig = new MockUserConfig();
		$this->clock = $this->createMock(ClockInterface::class);
		$this->clockFn = function () {
			return new \DateTimeImmutable('@0');
		};
		$this->clock->method('now')
			->willReturnCallback(function () {
				// extra wrapper so we can modify clockFn
				return ($this->clockFn)();
			});
		$this->sharesUpdatedListener = new SharesUpdatedListener(
			$this->manager,
			$this->shareRecipientUpdater,
			$this->userConfig,
			$this->clock,
			$this->appConfig,
		);
	}

	public function testShareAdded() {
		$share = $this->createMock(IShare::class);
		$user1 = $this->createUser('user1', '');
		$user2 = $this->createUser('user2', '');

		$this->manager->method('getUsersForShare')
			->willReturn([$user1, $user2]);

		$event = new ShareCreatedEvent($share);

		$this->shareRecipientUpdater
			->expects($this->exactly(2))
			->method('updateForAddedShare')
			->willReturnCallback(function (IUser $user, IShare $eventShare) use ($user1, $user2, $share) {
				$this->assertContains($user, [$user1, $user2]);
				$this->assertEquals($share, $eventShare);
			});

		$this->sharesUpdatedListener->handle($event);
	}

	public function testShareAddedFilterOwner() {
		$share = $this->createMock(IShare::class);
		$user1 = $this->createUser('user1', '');
		$user2 = $this->createUser('user2', '');
		$share->method('getSharedBy')
			->willReturn($user1->getUID());

		$this->manager->method('getUsersForShare')
			->willReturn([$user1, $user2]);

		$event = new ShareCreatedEvent($share);

		$this->shareRecipientUpdater
			->expects($this->exactly(1))
			->method('updateForAddedShare')
			->willReturnCallback(function (IUser $user, IShare $eventShare) use ($user2, $share) {
				$this->assertEquals($user, $user2);
				$this->assertEquals($share, $eventShare);
			});

		$this->sharesUpdatedListener->handle($event);
	}

	public function testShareAccessUpdated() {
		$user1 = $this->createUser('user1', '');
		$user2 = $this->createUser('user2', '');

		$event = new UserShareAccessUpdatedEvent([$user1, $user2]);

		$this->shareRecipientUpdater
			->expects($this->exactly(2))
			->method('updateForUser')
			->willReturnCallback(function (IUser $user) use ($user1, $user2) {
				$this->assertContains($user, [$user1, $user2]);
			});

		$this->sharesUpdatedListener->handle($event);
	}

	public function testShareDeleted() {
		$share = $this->createMock(IShare::class);
		$user1 = $this->createUser('user1', '');
		$user2 = $this->createUser('user2', '');

		$this->manager->method('getUsersForShare')
			->willReturn([$user1, $user2]);

		$event = new BeforeShareDeletedEvent($share);

		$this->shareRecipientUpdater
			->expects($this->exactly(2))
			->method('updateForDeletedShare')
			->willReturnCallback(function (IUser $user) use ($user1, $user2, $share) {
				$this->assertContains($user, [$user1, $user2]);
			});

		$this->sharesUpdatedListener->handle($event);
	}

	public static function shareMarkAfterTimeProvider(): array {
		// note that each user will take exactly 1s in this test
		return [
			[0, 0],
			[0.9, 1],
			[1.1, 2],
			[-1, 2],
		];
	}

	#[DataProvider('shareMarkAfterTimeProvider')]
	public function testShareMarkAfterTime(float $cutOff, int $expectedCount) {
		$share = $this->createMock(IShare::class);
		$user1 = $this->createUser('user1', '');
		$user2 = $this->createUser('user2', '');

		$this->manager->method('getUsersForShare')
			->willReturn([$user1, $user2]);

		$event = new ShareCreatedEvent($share);

		$this->sharesUpdatedListener->setCutOffMarkTime($cutOff);
		$time = 0;
		$this->clockFn = function () use (&$time) {
			$time++;
			return new \DateTimeImmutable('@' . $time);
		};

		$this->shareRecipientUpdater
			->expects($this->exactly($expectedCount))
			->method('updateForAddedShare');

		$this->sharesUpdatedListener->handle($event);

		$this->assertEquals($expectedCount < 1, $this->userConfig->getValueBool($user1->getUID(), 'files_sharing', ConfigLexicon::USER_NEEDS_SHARE_REFRESH));
		$this->assertEquals($expectedCount < 2, $this->userConfig->getValueBool($user2->getUID(), 'files_sharing', ConfigLexicon::USER_NEEDS_SHARE_REFRESH));
	}
}
