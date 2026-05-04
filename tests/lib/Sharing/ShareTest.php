<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Test\Sharing;

use OCA\Sharing\Tests\TestShareRecipientType;
use OCA\Sharing\Tests\TestShareRecipientType2;
use OCA\Sharing\Tests\TestShareSourceType;
use OCA\Sharing\Tests\TestShareSourceType2;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\IRegistry;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareOwner;
use OCP\Sharing\ShareState;
use OCP\Sharing\Source\ShareSource;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class ShareTest extends TestCase {
	private IRegistry $registry;

	private IUser $owner;


	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(IRegistry::class);
		$this->registry->clear();

		$owner = Server::get(IUserManager::class)->createUser('owner', 'password');
		$this->assertNotFalse($owner);
		$this->owner = $owner;
		$this->owner->setDisplayName('Owner');
	}

	#[\Override]
	protected function tearDown(): void {
		$this->registry->clear();

		$this->owner->delete();

		parent::tearDown();
	}

	public function testUniqueDisplayNames(): void {
		$this->registry->registerSourceType(new TestShareSourceType(['source1' => 'Source']));
		$this->registry->registerSourceType(new TestShareSourceType2(['source2' => 'Source', 'source3' => 'Other']));
		$this->registry->registerRecipientType(new TestShareRecipientType(['recipient1' => 'Recipient'], [], []));
		$this->registry->registerRecipientType(new TestShareRecipientType2(['recipient2' => 'Recipient', 'recipient3' => 'Other'], [], []));

		$source1 = new ShareSource(TestShareSourceType::class, 'source1');
		$source2 = new ShareSource(TestShareSourceType2::class, 'source2');
		$source3 = new ShareSource(TestShareSourceType2::class, 'source3');

		$recipient1 = new ShareRecipient(TestShareRecipientType::class, 'recipient1');
		$recipient2 = new ShareRecipient(TestShareRecipientType2::class, 'recipient2');
		$recipient3 = new ShareRecipient(TestShareRecipientType2::class, 'recipient3');

		$share = new Share(
			'123',
			new ShareOwner($this->owner->getUID()),
			456,
			ShareState::Draft,
			[$source1, $source2, $source3],
			[$recipient1, $recipient2, $recipient3],
			[],
			[],
		);

		$this->assertEquals([
			'id' => '123',
			'owner' => [
				'user_id' => 'owner',
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'last_updated' => 456,
			'state' => ShareState::Draft->value,
			'sources' => [
				[
					'class' => TestShareSourceType::class,
					'value' => 'source1',
					'display_name' => 'Source (TestShareSourceType: source1)',
				],
				[
					'class' => TestShareSourceType2::class,
					'value' => 'source2',
					'display_name' => 'Source (TestShareSourceType2: source2)',
				],
				[
					'class' => TestShareSourceType2::class,
					'value' => 'source3',
					'display_name' => 'Other',
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType::class,
					'value' => 'recipient1',
					'display_name' => 'Recipient (TestShareRecipientType: recipient1)',
				],
				[
					'class' => TestShareRecipientType2::class,
					'value' => 'recipient2',
					'display_name' => 'Recipient (TestShareRecipientType2: recipient2)',
				],
				[
					'class' => TestShareRecipientType2::class,
					'value' => 'recipient3',
					'display_name' => 'Other',
				],
			],
			'properties' => [],
			'permissions' => [],
		], $share->format());
	}
}
