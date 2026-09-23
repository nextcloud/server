<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use DateTimeImmutable;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use OCP\IUser;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;
use Test\Traits\UserTrait;

final class ShareTest extends TestCase {
	use UserTrait;

	private ISharingRegistry $registry;

	private IUser $owner;

	private IUser $user1;

	private IUser $user2;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(ISharingRegistry::class);
		$this->registry->clear();

		$this->owner = $this->createUser('owner', 'password');
		$this->user1 = $this->createUser('user1', 'password');
		$this->user2 = $this->createUser('user2', 'password');
	}

	#[\Override]
	public function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testGetEffectiveRecipients(): void {
		$recipient1 = new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null);
		$recipient2 = new ShareRecipient(TestShareRecipientType1::class, 'recipient2', null);
		$recipient3 = new ShareRecipient(TestShareRecipientType1::class, 'recipient3', null, 'secret');

		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'recipient1' => 'Recipient 1',
					'recipient2' => 'Recipient 2',
					'recipient3' => 'Recipient 3',
				],
				[
					$this->user1->getUID() => ['recipient1'],
					$this->user2->getUID() => ['recipient2'],
				],
				[],
			)
		);

		$share = new Share(
			'123',
			new ShareUser($this->owner->getUID(), null),
			new DateTimeImmutable(),
			ShareState::Active,
			null,
			[],
			[
				$recipient1,
				$recipient2,
				$recipient3,
			],
			[],
			[],
		);

		$this->assertEquals([$recipient1], $share->getEffectiveRecipients(new ShareAccessContext($this->user1)));
		$this->assertEquals([$recipient2], $share->getEffectiveRecipients(new ShareAccessContext($this->user2)));
		$this->assertEquals([$recipient3], $share->getEffectiveRecipients(new ShareAccessContext(secret: 'secret')));
		$this->assertEquals([$recipient1, $recipient3], $share->getEffectiveRecipients(new ShareAccessContext($this->user1, 'secret')));
	}

	/**
	 * @return list<array{list<ShareRecipient>, array<class-string<ISharePermissionType>, SharePermission>, bool}>
	 */
	public static function dataGetEffectiveEnabledPermissions(): array {
		$recipient1 = new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null, null, null, [TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, true)]);
		$recipient2 = new ShareRecipient(TestShareRecipientType1::class, 'recipient2', null, null, null, [TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, false)]);

		return [
			[
				[$recipient1],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, true)],
				true,
			],
			[
				[$recipient1],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, false)],
				false,
			],
			[
				[$recipient1],
				[],
				false,
			],
			//
			[
				[$recipient2],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, true)],
				false,
			],
			[
				[$recipient2],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, false)],
				false,
			],
			[
				[$recipient2],
				[],
				false,
			],
			//
			[
				[$recipient1, $recipient2],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, true)],
				true,
			],
			[
				[$recipient1, $recipient2],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, false)],
				false,
			],
			[
				[$recipient1, $recipient2],
				[],
				false,
			],
			//
			[
				[$recipient2, $recipient1],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, true)],
				true,
			],
			[
				[$recipient2, $recipient1],
				[TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, false)],
				false,
			],
			[
				[$recipient2, $recipient1],
				[],
				false,
			],
		];
	}

	/**
	 * @param list<ShareRecipient> $recipients
	 * @param array<class-string<ISharePermissionType>, SharePermission> $permissions
	 */
	#[DataProvider('dataGetEffectiveEnabledPermissions')]
	public function testGetEffectiveEnabledPermissions(array $recipients, array $permissions, bool $enabled): void {
		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'recipient1' => 'Recipient 1',
					'recipient2' => 'Recipient 2',
				],
				[
					$this->user1->getUID() => ['recipient1', 'recipient2'],
				],
				[],
			)
		);

		$share = new Share(
			'123',
			new ShareUser($this->owner->getUID(), null),
			new DateTimeImmutable(),
			ShareState::Active,
			null,
			[],
			$recipients,
			[],
			$permissions,
		);

		$this->assertEquals(
			$enabled
			? [TestSharePermissionType1::class => new SharePermission(TestSharePermissionType1::class, true)]
			: [],
			$share->getEffectiveEnabledPermissions(new ShareAccessContext($this->user1)),
		);
	}
}
