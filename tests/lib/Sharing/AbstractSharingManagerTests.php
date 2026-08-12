<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUserStatus;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OC\Sharing\SharingManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\IDBConnection;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

/**
 * @psalm-import-type SharingShare from Share
 * @psalm-import-type SharingRecipient from Share
 *
 * @psalm-suppress RedundantConditionGivenDocblockType
 * @psalm-suppress PossiblyUndefinedArrayOffset
 */
abstract class AbstractSharingManagerTests extends TestCase {
	abstract protected function searchRecipients(
		ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?Share $forShare = null,
	): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function createShare(ShareAccessContext $accessContext): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function updateShareState(ShareAccessContext $accessContext, Share $share, ShareState $state): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function updateShareUserStatus(ShareAccessContext $accessContext, Share $share, ShareUserStatus $userStatus): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function addShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function removeShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function addShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function removeShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function updateShareRecipientSecret(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, string $secret): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function updateShareProperty(ShareAccessContext $accessContext, Share $share, ShareProperty $property): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function updateSharePermission(ShareAccessContext $accessContext, Share $share, SharePermission $permission): array;

	/**
	 * @return SharingShare
	 */
	abstract protected function selectSharePermissionPreset(ShareAccessContext $accessContext, Share $share, string $permissionPresetClass): array;

	abstract protected function deleteShare(ShareAccessContext $accessContext, Share $share): void;

	/**
	 * @return SharingShare
	 */
	abstract protected function getShare(ShareAccessContext $accessContext, string $id): array;

	/**
	 * @return SharingShare[]
	 */
	abstract protected function getShares(
		ShareAccessContext $accessContext,
		?string $filterSourceTypeClass,
		?string $filterSourceTypeValue,
		?ShareState $filterState,
		?ShareUserStatus $filterUserStatus,
		?string $lastShareID,
		?int $limit,
	): array;

	protected IDBConnection $dbConnection;

	protected ISharingManager $manager;

	protected ISharingRegistry $registry;

	protected IUser $owner;

	protected IUser $user1;

	protected IUser $user2;

	protected TestShareSourceType1 $shareSourceType1;

	protected TestShareSourceType2 $shareSourceType2;

	protected IFactory $l10nFactory;

	private function parseTime(mixed $timestampMs): \DateTimeImmutable {
		$timestampMs = (float)$timestampMs;
		$time = \DateTimeImmutable::createFromFormat('U.u', number_format($timestampMs / 1000.0, 3, '.', ''));
		if ($time === false) {
			throw new \RuntimeException("invalid timestamp: $timestampMs");
		}

		return $time;
	}

	private function assertDateBetween(\DateTimeImmutable $before, \DateTimeImmutable $after, \DateTimeImmutable $time): void {
		$this->assertGreaterThanOrEqual(SharingManager::timeToMs($before), SharingManager::timeToMs($time));
		$this->assertLessThanOrEqual(SharingManager::timeToMs($after), SharingManager::timeToMs($time));
	}

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->dbConnection = Server::get(IDBConnection::class);

		$this->manager = Server::get(ISharingManager::class);

		$this->l10nFactory = Server::get(IFactory::class);

		$userManager = Server::get(IUserManager::class);

		$owner = $userManager->createUser('owner', 'password');
		$this->assertNotFalse($owner);
		$this->owner = $owner;
		$this->owner->setDisplayName('Owner');

		$user1 = $userManager->createUser('user1', 'password');
		$this->assertNotFalse($user1);
		$this->user1 = $user1;
		$this->user1->setDisplayName('User 1');

		$user2 = $userManager->createUser('user2', 'password');
		$this->assertNotFalse($user2);
		$this->user2 = $user2;
		$this->user2->setDisplayName('User 2');

		$this->registry = Server::get(ISharingRegistry::class);
		$this->registry->clear();

		$this->shareSourceType1 = new TestShareSourceType1(['source1' => 'Source 1']);
		$this->registry->registerSourceType($this->shareSourceType1);
		$this->shareSourceType2 = new TestShareSourceType2(['source2' => 'Source 2']);
		$this->registry->registerSourceType($this->shareSourceType2);
		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'recipient1' => 'Recipient 1',
				],
				[
					$this->user1->getUID() => ['recipient1'],
				],
				[
					new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null),
				],
			)
		);
		$this->registry->registerRecipientType(
			new TestShareRecipientType2(
				[
					'recipient2' => 'Recipient 2',
				],
				[
					$this->user2->getUID() => ['recipient2'],
				],
				[
					new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null),
				],
			)
		);
		$this->registry->registerPropertyType(new TestSharePropertyType1(['valid1']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyType1::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyType1::class, TestShareRecipientType1::class);
		$this->registry->registerPropertyType(new TestSharePropertyType2(['valid2']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyType2::class, TestShareSourceType2::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyType2::class, TestShareRecipientType2::class);
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset1());
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset2());
		$this->registry->registerPermissionType(TestShareSourceType1::class, new TestSharePermissionType1());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset1::class);
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset2::class);
		$this->registry->registerPermissionType(TestShareSourceType2::class, new TestSharePermissionType2());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType2::class, TestSharePermissionPreset2::class);
		$this->registry->registerPermissionType(null, Server::get(ReshareSharePermissionType::class));
	}

	#[\Override]
	protected function tearDown(): void {
		$openTransaction = false;
		if ($this->dbConnection->inTransaction()) {
			$this->dbConnection->rollBack();
			$openTransaction = true;
		}

		$accessContext = new ShareAccessContext(overrideChecks: true);

		$this->dbConnection->beginTransaction();
		foreach ($this->manager->getShares($accessContext, null, null, null, null, null, null) as $share) {
			$this->manager->deleteShare($accessContext, $share);
		}

		$this->dbConnection->commit();

		$this->owner->delete();
		$this->user1->delete();
		$this->user2->delete();

		$this->registry->clear();

		foreach ([
			'sharing_share',
			'sharing_share_permissions',
			'sharing_share_properties',
			'sharing_share_recipients',
			'sharing_share_sources',
		] as $table) {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb
				->select($qb->func()->count('*'))
				->from($table);
			$this->assertEquals(0, $qb->executeQuery()->fetchOne(), $table);
		}

		if ($openTransaction) {
			$this->fail('Open transaction was not committed.');
		}

		parent::tearDown();
	}

	private function reloadShare(ShareAccessContext $accessContext, Share $share): Share {
		$this->dbConnection->beginTransaction();
		$share = $this->manager->getShare($accessContext, $share->id);
		$this->dbConnection->commit();
		return $share;
	}

	public function testSearchRecipients(): void {
		$this->registry->clear();
		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'recipient1a' => 'Recipient 1A',
					'recipient1b' => 'Recipient 1B',
					'recipient1c' => 'Recipient 1C',
				],
				[],
				[
					new ShareRecipient(TestShareRecipientType1::class, 'recipient1a', null),
					new ShareRecipient(TestShareRecipientType1::class, 'recipient1b', null),
					new ShareRecipient(TestShareRecipientType1::class, 'recipient1c', null),
				],
			)
		);
		$this->registry->registerRecipientType(
			new TestShareRecipientType2(
				[
					'recipient2a' => 'Recipient 2A',
					'recipient2b' => 'Recipient 2B',
					'recipient2c' => 'Recipient 2C',
				],
				[],
				[
					new ShareRecipient(TestShareRecipientType2::class, 'recipient2a', null),
					new ShareRecipient(TestShareRecipientType2::class, 'recipient2b', null),
					new ShareRecipient(TestShareRecipientType2::class, 'recipient2c', null),
				],
			)
		);

		$accessContext = new ShareAccessContext($this->owner);

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1a',
				'instance' => null,
				'display_name' => 'Recipient 1A',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1b',
				'instance' => null,
				'display_name' => 'Recipient 1B',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1c',
				'instance' => null,
				'display_name' => 'Recipient 1C',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2a',
				'instance' => null,
				'display_name' => 'Recipient 2A',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2b',
				'instance' => null,
				'display_name' => 'Recipient 2B',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2c',
				'instance' => null,
				'display_name' => 'Recipient 2C',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, null, 'recipient', 10, 0));

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1a',
				'instance' => null,
				'display_name' => 'Recipient 1A',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1b',
				'instance' => null,
				'display_name' => 'Recipient 1B',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1c',
				'instance' => null,
				'display_name' => 'Recipient 1C',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, [TestShareRecipientType1::class], 'recipient', 10, 0));

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1a',
				'instance' => null,
				'display_name' => 'Recipient 1A',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, [TestShareRecipientType1::class], 'recipient', 1, 0));

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1b',
				'instance' => null,
				'display_name' => 'Recipient 1B',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1c',
				'instance' => null,
				'display_name' => 'Recipient 1C',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, [TestShareRecipientType1::class], 'recipient', 10, 1));
	}

	public function testSearchRecipientsUniqueDisplayNames(): void {
		$this->registry->clear();
		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'recipient1' => 'Recipient',
				],
				[],
				[
					new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null),
				],
			)
		);
		$this->registry->registerRecipientType(
			new TestShareRecipientType2(
				[
					'recipient2' => 'Recipient',
					'recipient3' => 'Other',
				],
				[],
				[
					new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null),
					new ShareRecipient(TestShareRecipientType2::class, 'recipient3', null),
				],
			)
		);

		$accessContext = new ShareAccessContext($this->owner);

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient (TestShareRecipientType1: recipient1)',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient (TestShareRecipientType2: recipient2)',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient3',
				'instance' => null,
				'display_name' => 'Other',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, null, 'recipient', 10, 0));
	}

	public function testSearchRecipientsIcons(): void {
		$this->registry->clear();
		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'svg' => 'SVG',
					'url' => 'URL',
				],
				[],
				[
					new ShareRecipient(TestShareRecipientType1::class, 'svg', null),
					new ShareRecipient(TestShareRecipientType1::class, 'url', null),
				],
			)
		);

		$accessContext = new ShareAccessContext($this->owner);

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'svg',
				'instance' => null,
				'display_name' => 'SVG',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'url',
				'instance' => null,
				'display_name' => 'URL',
				'icon' => [
					'light' => 'https://example.com/light.png',
					'dark' => 'https://example.com/dark.png',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, null, 'icon', 10, 0));
	}

	public function testSearchRecipientsOmitExisting(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->dbConnection->commit();

		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient 1',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient 2',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, null, 'recipient', 3, 0, $share));

		$this->dbConnection->beginTransaction();
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$this->dbConnection->commit();

		$this->assertEquals([
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient 2',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => null,
			],
		], $this->searchRecipients($accessContext, null, 'recipient', 3, 0, $share));
	}

	public function testCreateShare(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$share = $this->createShare($accessContext);
		$after = $this->manager->getTime();
		unset($share['id']);
		$this->assertDateBetween($before, $after, $this->parseTime($share['last_updated']));
		unset($share['last_updated']);
		$this->assertEquals([
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Draft->value,
			'user_status' => null,
			'sources' => [],
			'recipients' => [],
			'properties' => [],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
			],
			'permission_preset' => null,
		], $share);
	}

	/**
	 * @return list<array{list<ShareSource>, list<ShareRecipient>, list<ShareProperty>, list<SharePermission>, ?string}>
	 */
	public static function dataProviderUpdateShareState(): array {
		return [
			[
				[new ShareSource(TestShareSourceType1::class, 'source1')],
				[new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null)],
				[new ShareProperty(TestSharePropertyTypeRequired::class, 'valid1')],
				[new SharePermission(ReshareSharePermissionType::class, true)],
				null,
			],
			[
				[],
				[new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null)],
				[],
				[new SharePermission(ReshareSharePermissionType::class, true)],
				'You need to add at least one source to make the share available.',
			],
			[
				[new ShareSource(TestShareSourceType1::class, 'source1')],
				[],
				[],
				[new SharePermission(ReshareSharePermissionType::class, true)],
				'You need to add at least one recipient to make the share available.',
			],
			[
				[new ShareSource(TestShareSourceType1::class, 'source1')],
				[new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null)],
				[new ShareProperty(TestSharePropertyTypeRequired::class, null)],
				[new SharePermission(ReshareSharePermissionType::class, true)],
				'You need to set a value for the TestSharePropertyTypeRequired',
			],
			[
				[new ShareSource(TestShareSourceType1::class, 'source1')],
				[new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null)],
				[new ShareProperty(TestSharePropertyTypeRequired::class, 'valid1')],
				[new SharePermission(ReshareSharePermissionType::class, false)],
				'You need to allow at least one permission to make the share available.',
			],
		];
	}

	/**
	 * @param list<ShareSource> $sources
	 * @param list<ShareRecipient> $recipients
	 * @param list<ShareProperty> $properties
	 * @param list<SharePermission> $permissions
	 */
	#[DataProvider('dataProviderUpdateShareState')]
	public function testUpdateShareState(array $sources, array $recipients, array $properties, array $permissions, ?string $errorMessage): void {
		$this->registry->registerPropertyType(new TestSharePropertyTypeRequired(['valid1']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyTypeRequired::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyTypeRequired::class, TestShareRecipientType1::class);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		foreach ($sources as $source) {
			$share = $this->manager->addShareSource($accessContext, $share, $source);
		}

		foreach ($recipients as $recipient) {
			$share = $this->manager->addShareRecipient($accessContext, $share, $recipient);
		}

		foreach ($properties as $property) {
			$share = $this->manager->updateShareProperty($accessContext, $share, $property);
		}

		foreach ($permissions as $permission) {
			$share = $this->manager->updateSharePermission($accessContext, $share, $permission);
		}

		$this->dbConnection->commit();

		if ($errorMessage !== null) {
			try {
				$this->updateShareState($accessContext, $share, ShareState::Active);
				$this->fail('Allowed to set share state active.');
			} catch (HintException $exception) {
				$this->assertEquals($errorMessage, $exception->getHint());
			}
		} else {
			$before = $this->manager->getTime();
			$formatted = $this->updateShareState($accessContext, $share, ShareState::Active);
			$after = $this->manager->getTime();
			$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
			$this->assertEquals(ShareState::Active->value, $formatted['state']);
		}
	}

	public function testUpdateShareUserStatus(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$this->assertEquals(null, $share->userStatus);

		try {
			$this->updateShareUserStatus($accessContext, $share, ShareUserStatus::Accepted);
			$this->fail('Not allowed.');
		} catch (HintException $hintException) {
			$this->assertEquals('Cannot set user status for the owner of the share.', $hintException->getHint());
		}

		$accessContext2 = new ShareAccessContext($this->user1);

		$this->dbConnection->beginTransaction();
		$share2 = $this->manager->getShare($accessContext2, $share->id);
		$this->dbConnection->commit();
		$this->assertDateBetween($before, $after, $share->lastUpdated);
		$this->assertEquals(ShareUserStatus::Pending, $share2->userStatus);

		foreach (ShareUserStatus::cases() as $case) {
			$userStatus = ShareUserStatus::from($case->value);

			$formatted = $this->updateShareUserStatus($accessContext2, $share2, $userStatus);
			$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
			$this->assertEquals($userStatus->value, $formatted['user_status']);
		}
	}

	public function testAddShareSource(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestShareSourceType1::class,
				'value' => 'source1',
				'display_name' => 'Source 1',
				'icon' => [
					'svg' => '<svg/>',
				],
			],
		], $formatted['sources']);
	}

	public function testAddShareSourceInteractionRestricted(): void {
		$listener = function (RestrictInteractionEvent $event): void {
			foreach ($event->resources as $resource) {
				if ($resource instanceof TestInteractionResource && $resource->getID() === 'source1') {
					throw new InteractionRestrictedException('Source not allowed.', 'You are not allowed to add this source.');
				}
			}
		};
		$eventDispatcher = Server::get(IEventDispatcher::class);
		$eventDispatcher->addListener(RestrictInteractionEvent::class, $listener);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->reloadShare($accessContext, $share);
		$this->dbConnection->commit();

		try {
			$this->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
			$this->fail('Not restricted.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to add this source.', $hintException->getHint());
		}

		$eventDispatcher->removeListener(RestrictInteractionEvent::class, $listener);
	}

	public function testRemoveShareSource(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType2::class, 'source2'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType2::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->removeShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Active->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => TestShareSourceType2::class,
				'value' => 'source2',
				'display_name' => 'Source 2',
				'icon' => [
					'svg' => '<svg/>',
				],
			],
		], $formatted['sources']);

		$before = $this->manager->getTime();
		$formatted = $this->removeShareSource($accessContext, $share, new ShareSource(TestShareSourceType2::class, 'source2'));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Draft->value, $formatted['state']);
		$this->assertEquals([], $formatted['sources']);
	}

	public function testAddShareRecipient(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient 1',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
		], $formatted['recipients']);
	}

	public function testAddShareRecipientInteractionRestricted(): void {
		$listener = function (RestrictInteractionEvent $event): void {
			foreach ($event->receivers as $receiver) {
				if ($receiver instanceof TestInteractionReceiver && $receiver->getID() === 'recipient1') {
					throw new InteractionRestrictedException('Recipient not allowed.', 'You are not allowed to add this recipient.');
				}
			}
		};
		$eventDispatcher = Server::get(IEventDispatcher::class);
		$eventDispatcher->addListener(RestrictInteractionEvent::class, $listener);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));

		$this->dbConnection->commit();

		try {
			$this->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
			$this->fail('Interaction not restricted.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to add this recipient.', $hintException->getHint());
		}

		$eventDispatcher->removeListener(RestrictInteractionEvent::class, $listener);
	}

	public function testAddChildShareRecipientWithoutResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->addShareRecipient(new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
			$this->fail('Able to add child recipient without reshare permission.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testAddChildShareRecipientWithResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		$accessContext2 = new ShareAccessContext($this->user1);

		$this->dbConnection->beginTransaction();
		$share2 = $this->manager->getShare($accessContext2, $share->id);
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->addShareRecipient(
			$accessContext2, $share2, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient 1',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient 2',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'user1',
					'instance' => null,
					'display_name' => 'User 1',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/user1/64',
						'dark' => 'http://localhost/index.php/avatar/user1/64/dark',
					],
				],
			],
		], $formatted['recipients']);
	}

	public function testRemoveShareRecipient(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->removeShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Active->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient 2',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
		], $formatted['recipients']);

		$before = $this->manager->getTime();
		$formatted = $this->removeShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Draft->value, $formatted['state']);
		$this->assertEquals([], $formatted['recipients']);
	}

	public function testRemoveSelfShareRecipientWithoutResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
			$this->fail('Able to remove self recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testRemoveSelfShareRecipientWithResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
			$this->fail('Able to remove self recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testRemoveChildShareRecipientWithoutResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, false));

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
			$this->fail('Able to remove child recipient without reshare permission.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testRemoveChildShareRecipientWithResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);

		$this->dbConnection->commit();

		$accessContext2 = new ShareAccessContext($this->user1);

		$this->dbConnection->beginTransaction();
		$share2 = $this->manager->getShare($accessContext2, $share->id);
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->removeShareRecipient(
			$accessContext2, $share2, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient 1',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
		], $formatted['recipients']);
	}

	public function testRemoveSiblingShareRecipientWithoutResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
			$this->fail('Able to remove sibling recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testRemoveSiblingShareRecipientWithResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
			$this->fail('Able to remove sibling recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testRemoveParentShareRecipientWithoutResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, false));

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user2), $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
			$this->fail('Able to remove parent recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	public function testRemoveParentShareRecipientWithResharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);

		$this->dbConnection->commit();

		try {
			$this->removeShareRecipient(new ShareAccessContext($this->user2), $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
			$this->fail('Able to remove parent recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to edit this share.', $hintException->getHint());
		}
	}

	/**
	 * @return list<array{bool}>
	 */
	public static function dataUpdateShareRecipientSecret(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataUpdateShareRecipientSecret')]
	public function testUpdateShareRecipientSecret(bool $isSecretUpdatable): void {
		$this->registry->registerRecipientType(
			new TestShareRecipientTypePublicSecret(
				[
					'recipient1' => 'Recipient 1',
				],
				[],
				true,
				$isSecretUpdatable,
			)
		);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$recipient = new ShareRecipient(TestShareRecipientTypePublicSecret::class, 'recipient1', null);
		$share = $this->manager->addShareRecipient($accessContext, $share, $recipient);
		$this->dbConnection->commit();

		if (!$isSecretUpdatable) {
			try {
				$this->updateShareRecipientSecret($accessContext, $share, $recipient, 'mysecret');
				$this->fail('Able to update recipient secret.');
			} catch (HintException $exception) {
				$this->assertEquals('You are not allowed to edit this share.', $exception->getHint());
			}
		} else {
			$before = $this->manager->getTime();
			$formatted = $this->updateShareRecipientSecret($accessContext, $share, $recipient, 'mysecret');
			$after = $this->manager->getTime();
			$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
			unset($formatted['last_updated']);
			$this->assertEquals([
				[
					'class' => TestShareRecipientTypePublicSecret::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>'
					],
					'secret' => [
						'updatable' => true,
						'value' => 'mysecret',
						'url' => 'http://localhost/index.php/s/mysecret',
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			], $formatted['recipients']);
		}
	}

	/**
	 * @return list<array{list<?string>}>
	 */
	public static function dataProviderUpdateShareProperty(): array {
		return [
			[[null, 'valid1']],
			[['valid1', null]],
		];
	}

	/**
	 * @param list<?string> $values
	 */
	#[DataProvider('dataProviderUpdateShareProperty')]
	public function testUpdateShareProperty(array $values): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->getShare($accessContext, $share->id);

		$this->dbConnection->commit();

		foreach ($values as $value) {
			$before = $this->manager->getTime();
			$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyType1::class, $value));
			$after = $this->manager->getTime();
			$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
			$this->assertEquals([
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => $value,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
			], $formatted['properties']);
		}
	}

	public function testUpdateSharePropertyRequired(): void {
		$this->registry->registerPropertyType(new TestSharePropertyTypeRequired(['valid1', 'valid2']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyTypeRequired::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyTypeRequired::class, TestShareRecipientType1::class);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->getShare($accessContext, $share->id);
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));

		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeRequired::class, 'valid1'));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Draft->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeRequired::class,
				'display_name' => 'TestSharePropertyTypeRequired',
				'hint' => 'hint TestSharePropertyTypeRequired',
				'priority' => 1,
				'advanced' => false,
				'required' => true,
				'value' => 'valid1',
				'type' => 'enum',
				'valid_values' => ['valid1', 'valid2'],
			],
		], $formatted['properties']);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeRequired::class, 'valid2'));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Active->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeRequired::class,
				'display_name' => 'TestSharePropertyTypeRequired',
				'hint' => 'hint TestSharePropertyTypeRequired',
				'priority' => 1,
				'advanced' => false,
				'required' => true,
				'value' => 'valid2',
				'type' => 'enum',
				'valid_values' => ['valid1', 'valid2'],
			],
		], $formatted['properties']);

		$before = $this->manager->getTime();
		$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeRequired::class, null));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Draft->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeRequired::class,
				'display_name' => 'TestSharePropertyTypeRequired',
				'hint' => 'hint TestSharePropertyTypeRequired',
				'priority' => 1,
				'advanced' => false,
				'required' => true,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1', 'valid2'],
			],
		], $formatted['properties']);
	}

	public function testUpdateSharePropertyModifyProperties(): void {
		$this->registry->registerPropertyType(
			new TestSharePropertyTypeModifyValue(['old-value', 'modify-on-save-old-value', 'modify-on-save', 'modify-on-load'])
		);
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyTypeModifyValue::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyTypeModifyValue::class, TestShareRecipientType1::class);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$this->dbConnection->commit();

		// We cannot test for modify-on-save, because we will always see modified-on-save as the returned value.

		$formatted = $this->getShare($accessContext, $share->id);
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeModifyValue::class,
				'display_name' => 'TestSharePropertyTypeModifyValue',
				'hint' => 'hint TestSharePropertyTypeModifyValue',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => 'modified-on-save',
				'type' => 'enum',
				'valid_values' => ['old-value', 'modify-on-save-old-value', 'modify-on-save', 'modify-on-load'],
			],
		], $formatted['properties']);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeModifyValue::class, 'old-value'));
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeModifyValue::class, 'modify-on-save-old-value'));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeModifyValue::class,
				'display_name' => 'TestSharePropertyTypeModifyValue',
				'hint' => 'hint TestSharePropertyTypeModifyValue',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => 'old-value',
				'type' => 'enum',
				'valid_values' => ['old-value', 'modify-on-save-old-value', 'modify-on-save', 'modify-on-load'],
			],
		], $formatted['properties']);

		$before = $this->manager->getTime();
		$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeModifyValue::class, 'modify-on-save'));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeModifyValue::class,
				'display_name' => 'TestSharePropertyTypeModifyValue',
				'hint' => 'hint TestSharePropertyTypeModifyValue',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => 'modified-on-save',
				'type' => 'enum',
				'valid_values' => ['old-value', 'modify-on-save-old-value', 'modify-on-save', 'modify-on-load'],
			],
		], $formatted['properties']);

		$before = $this->manager->getTime();
		$formatted = $this->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeModifyValue::class, 'modify-on-load'));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestSharePropertyType1::class,
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => null,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			],
			[
				'class' => TestSharePropertyTypeModifyValue::class,
				'display_name' => 'TestSharePropertyTypeModifyValue',
				'hint' => 'hint TestSharePropertyTypeModifyValue',
				'priority' => 1,
				'advanced' => false,
				'required' => false,
				'value' => 'modified-on-load',
				'type' => 'enum',
				'valid_values' => ['old-value', 'modify-on-save-old-value', 'modify-on-save', 'modify-on-load'],
			],
		], $formatted['properties']);
	}

	public function testUpdateSharePermission(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->getShare($accessContext, $share->id);

		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$this->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->reloadShare($accessContext, $share);
		$formatted = $this->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Draft->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => ReshareSharePermissionType::class,
				'source_class' => null,
				'display_name' => 'Share with others',
				'hint' => null,
				'presets' => [],
				'enabled' => true,
				'priority' => 90,
			],
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => TestShareSourceType1::class,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$this->dbConnection->commit();

		$before = $this->manager->getTime();
		$formatted = $this->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, false));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Active->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => ReshareSharePermissionType::class,
				'source_class' => null,
				'display_name' => 'Share with others',
				'hint' => null,
				'presets' => [],
				'enabled' => false,
				'priority' => 90,
			],
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => TestShareSourceType1::class,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, false));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(ShareState::Draft->value, $formatted['state']);
		$this->assertEquals([
			[
				'class' => ReshareSharePermissionType::class,
				'source_class' => null,
				'display_name' => 'Share with others',
				'hint' => null,
				'presets' => [],
				'enabled' => false,
				'priority' => 90,
			],
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => TestShareSourceType1::class,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
		], $formatted['permissions']);
	}

	public function testUpdateSharePermissionInteractionRestricted(): void {
		$listener = function (RestrictInteractionEvent $event): void {
			if ($event->action instanceof ShareAction && $event->action->unifiedSharingPermissions !== null && in_array(
				TestSharePermissionType1::class, $event->action->unifiedSharingPermissions, true
			)) {
				throw new InteractionRestrictedException('Permission not allowed.', 'You are not allowed to enable this permission.');
			}
		};
		$eventDispatcher = Server::get(IEventDispatcher::class);
		$eventDispatcher->addListener(RestrictInteractionEvent::class, $listener);

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$this->dbConnection->commit();

		try {
			$this->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
			$this->fail('Not restricted.');
		} catch (HintException $hintException) {
			$this->assertEquals('You are not allowed to enable this permission.', $hintException->getHint());
		}

		$eventDispatcher->removeListener(RestrictInteractionEvent::class, $listener);
	}

	public function testSelectSharePermissionPreset(): void {
		$this->registry->clear();
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset1());
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset2());
		$this->registry->registerPermissionType(null, new TestSharePermissionType1());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset1::class);
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset2::class);
		$this->registry->registerPermissionType(null, new TestSharePermissionType2());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType2::class, TestSharePermissionPreset2::class);
		$this->registry->registerPermissionType(null, new TestSharePermissionType3());

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->getShare($accessContext, $share->id);
		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare($accessContext, $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertNull($formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType3::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType3',
				'hint' => 'hint TestSharePermissionType3',
				'presets' => [],
				'enabled' => false,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->selectSharePermissionPreset($accessContext, $share, TestSharePermissionPreset2::class);
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(TestSharePermissionPreset2::class, $formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType3::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType3',
				'hint' => 'hint TestSharePermissionType3',
				'presets' => [],
				'enabled' => false,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType3::class, true));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertNull($formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType3::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType3',
				'hint' => 'hint TestSharePermissionType3',
				'presets' => [],
				'enabled' => true,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->selectSharePermissionPreset($accessContext, $share, TestSharePermissionPreset1::class);
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(TestSharePermissionPreset1::class, $formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType3::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType3',
				'hint' => 'hint TestSharePermissionType3',
				'presets' => [],
				'enabled' => false,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, false));
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertNull($formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType3::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType3',
				'hint' => 'hint TestSharePermissionType3',
				'presets' => [],
				'enabled' => false,
				'priority' => 1,
			],
		], $formatted['permissions']);
	}

	public function testSelectSharePermissionPresetCompatible(): void {
		$this->registry->clear();
		$this->registry->registerSourceType(new TestShareSourceType1(['source1' => 'Source 1']));
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset1());
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset2());
		$this->registry->registerPermissionType(TestShareSourceType1::class, new TestSharePermissionType1());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset1::class);
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset2::class);
		$this->registry->registerPermissionType(null, new TestSharePermissionType2());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType2::class, TestSharePermissionPreset2::class);

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->getShare($accessContext, $share->id);
		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare($accessContext, $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertNull($formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->selectSharePermissionPreset($accessContext, $share, TestSharePermissionPreset2::class);
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(TestSharePermissionPreset2::class, $formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->reloadShare($accessContext, $share);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertNull($formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => TestShareSourceType1::class,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => false,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
		], $formatted['permissions']);

		$before = $this->manager->getTime();
		$formatted = $this->selectSharePermissionPreset($accessContext, $share, TestSharePermissionPreset2::class);
		$after = $this->manager->getTime();
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals(TestSharePermissionPreset2::class, $formatted['permission_preset']);
		$this->assertEquals([
			[
				'class' => TestSharePermissionType1::class,
				'source_class' => TestShareSourceType1::class,
				'display_name' => 'TestSharePermissionType1',
				'hint' => 'hint TestSharePermissionType1',
				'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
			[
				'class' => TestSharePermissionType2::class,
				'source_class' => null,
				'display_name' => 'TestSharePermissionType2',
				'hint' => 'hint TestSharePermissionType2',
				'presets' => [TestSharePermissionPreset2::class],
				'enabled' => true,
				'priority' => 1,
			],
		], $formatted['permissions']);
	}

	public function testDeleteShare(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);

		$this->deleteShare($accessContext, $share);

		try {
			$this->manager->getShare(new ShareAccessContext(overrideChecks: true), $share->id);
			$this->fail('Share not deleted.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		} finally {
			$this->dbConnection->commit();
		}
	}

	public function testGetShare(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$this->manager->getShare($accessContext, $share->id);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare($accessContext, $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Draft->value,
			'user_status' => null,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType1::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
			],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => false,
					'priority' => 1,
				],
			],
			'permission_preset' => null,
		], $formatted);
	}

	public function testGetShareAsRecipientNotActive(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$this->dbConnection->commit();

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
			$this->fail('Draft share visible.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testGetShareAsRecipientActive(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => ShareUserStatus::Pending->value,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType1::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
				[
					'class' => TestShareRecipientType2::class,
					'value' => 'recipient2',
					'instance' => null,
					'display_name' => 'Recipient 2',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
			],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);
	}

	public function testGetShareAsRecipientWithArguments(): void {
		$this->registry->registerRecipientType(new TestShareRecipientTypeArguments());

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientTypeArguments::class, 'secret', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare(new ShareAccessContext(currentUser: $this->user1, arguments: [TestShareRecipientTypeArguments::class => 'secret']),
			$share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => ShareUserStatus::Pending->value,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientTypeArguments::class,
					'value' => 'secret',
					'instance' => null,
					'display_name' => 'secret',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/guest/secret/64',
						'dark' => 'http://localhost/index.php/avatar/guest/secret/64?darkTheme=1',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
			$this->fail('Share visible without arguments.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testGetShareWithSecretNotActive(): void {
		$this->registry->registerRecipientType(new TestShareRecipientTypeArguments());

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientTypeArguments::class, 'secret', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));

		$this->dbConnection->commit();
		$secret = $share->recipients[0]->secret;
		$this->assertNotNull($secret);

		try {
			$this->getShare(new ShareAccessContext(secret: $secret), $share->id);
			$this->fail('Draft share visible with secret.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testGetShareWithSecretActive(): void {
		$this->registry->registerRecipientType(new TestShareRecipientTypeArguments());

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientTypeArguments::class, 'secret', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$after = $this->manager->getTime();

		$this->dbConnection->commit();
		$secret = $share->recipients[0]->secret;
		$this->assertNotNull($secret);

		$formatted = $this->getShare(new ShareAccessContext(secret: $secret), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => null,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientTypeArguments::class,
					'value' => 'secret',
					'instance' => null,
					'display_name' => 'secret',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/guest/secret/64',
						'dark' => 'http://localhost/index.php/avatar/guest/secret/64?darkTheme=1',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);

		try {
			$this->getShare(new ShareAccessContext(), $share->id);
			$this->fail('Share visible without secret.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testGetShareAsNonRecipient(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
			$this->fail('Share visible as non-recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testGetShareAsRecipientFilteredProperties(): void {
		$this->registry->registerPropertyType(new TestSharePropertyTypeFilter(['visible', 'filtered']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyTypeFilter::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyTypeFilter::class, TestShareRecipientType1::class);

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeFilter::class, 'visible'));

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => ShareUserStatus::Pending->value,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType1::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
				[
					'class' => TestSharePropertyTypeFilter::class,
					'display_name' => 'TestSharePropertyTypeFilter',
					'hint' => 'hint TestSharePropertyTypeFilter',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => 'visible',
					'type' => 'enum',
					'valid_values' => ['visible', 'filtered'],
				],
			],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$this->manager->updateShareProperty($accessContext, $share, new ShareProperty(TestSharePropertyTypeFilter::class, 'filtered'));
		$share = $this->reloadShare($accessContext, $share);
		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare(new ShareAccessContext(currentUser: $this->owner), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => null,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType1::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
				[
					'class' => TestSharePropertyTypeFilter::class,
					'display_name' => 'TestSharePropertyTypeFilter',
					'hint' => 'hint TestSharePropertyTypeFilter',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => 'filtered',
					'type' => 'enum',
					'valid_values' => ['visible', 'filtered'],
				],
			],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
			$this->fail('Share visible with active filter property.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testGetShareAsRecipientFilteredArguments(): void {
		$this->registry->registerPropertyType(new TestSharePropertyTypeFilter(['visible', 'filtered']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyTypeFilter::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyTypeFilter::class, TestShareRecipientType1::class);

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => ShareUserStatus::Pending->value,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType1::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
				[
					'class' => TestSharePropertyTypeFilter::class,
					'display_name' => 'TestSharePropertyTypeFilter',
					'hint' => 'hint TestSharePropertyTypeFilter',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['visible', 'filtered'],
				],
			],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);

		$formatted = $this->getShare(new ShareAccessContext(currentUser: $this->owner, arguments: [TestSharePropertyTypeFilter::class => 'filtered']),
			$share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertEquals([
			'id' => $share->id,
			'owner' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
			'state' => ShareState::Active->value,
			'user_status' => null,
			'sources' => [
				[
					'class' => TestShareSourceType1::class,
					'value' => 'source1',
					'display_name' => 'Source 1',
					'icon' => [
						'svg' => '<svg/>',
					],
				],
			],
			'recipients' => [
				[
					'class' => TestShareRecipientType1::class,
					'value' => 'recipient1',
					'instance' => null,
					'display_name' => 'Recipient 1',
					'icon' => [
						'svg' => '<svg/>',
					],
					'secret' => [
						'updatable' => false,
					],
					'initiator' => [
						'user_id' => 'owner',
						'instance' => null,
						'display_name' => 'Owner',
						'icon' => [
							'light' => 'http://localhost/index.php/avatar/owner/64',
							'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
						],
					],
				],
			],
			'properties' => [
				[
					'class' => TestSharePropertyType1::class,
					'display_name' => 'TestSharePropertyType1',
					'hint' => 'hint TestSharePropertyType1',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['valid1'],
				],
				[
					'class' => TestSharePropertyTypeFilter::class,
					'display_name' => 'TestSharePropertyTypeFilter',
					'hint' => 'hint TestSharePropertyTypeFilter',
					'priority' => 1,
					'advanced' => false,
					'required' => false,
					'value' => null,
					'type' => 'enum',
					'valid_values' => ['visible', 'filtered'],
				],
			],
			'permissions' => [
				[
					'class' => ReshareSharePermissionType::class,
					'source_class' => null,
					'display_name' => 'Share with others',
					'hint' => null,
					'presets' => [],
					'enabled' => false,
					'priority' => 90,
				],
				[
					'class' => TestSharePermissionType1::class,
					'source_class' => TestShareSourceType1::class,
					'display_name' => 'TestSharePermissionType1',
					'hint' => 'hint TestSharePermissionType1',
					'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
					'enabled' => true,
					'priority' => 1,
				],
			],
			'permission_preset' => TestSharePermissionPreset1::class,
		], $formatted);

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user1, arguments: [TestSharePropertyTypeFilter::class => 'filtered']), $share->id);
			$this->fail('Share visible with filtered value as recipient.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	/**
	 * @return list<array{bool}>
	 */
	public static function dataGetShareWithPublicSecret(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataGetShareWithPublicSecret')]
	public function testGetShareWithPublicSecret(bool $isSecretPublic): void {
		$this->registry->clear();
		$this->registry->registerRecipientType(
			new TestShareRecipientType1(
				[
					'recipient1' => 'Recipient 1',
				],
				[],
				[],
			)
		);
		$this->registry->registerRecipientType(
			new TestShareRecipientTypePublicSecret(
				[
					'recipient2' => 'Recipient 2',
				],
				[],
				$isSecretPublic,
				false,
			)
		);

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientTypePublicSecret::class, 'recipient2', null));

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare($accessContext, $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		unset($formatted['last_updated']);
		$this->assertIsList($formatted['recipients']);
		$this->assertCount(2, $formatted['recipients']);
		// Sort because database order is not guaranteed
		usort($formatted['recipients'], fn (array $a, array $b): int => $a['value'] <=> $b['value']);
		$this->assertEquals([
			'class' => TestShareRecipientType1::class,
			'value' => 'recipient1',
			'instance' => null,
			'display_name' => 'Recipient 1',
			'icon' => [
				'svg' => '<svg/>',
			],
			'secret' => [
				'updatable' => false,
			],
			'initiator' => [
				'user_id' => 'owner',
				'instance' => null,
				'display_name' => 'Owner',
				'icon' => [
					'light' => 'http://localhost/index.php/avatar/owner/64',
					'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
				],
			],
		], $formatted['recipients'][0]);
		$this->assertIsArray($formatted['recipients'][1]);
		if ($isSecretPublic) {
			$this->assertArrayHasKey('secret', $formatted['recipients'][1]);
			$this->assertIsArray($formatted['recipients'][1]['secret']);

			$this->assertArrayHasKey('updatable', $formatted['recipients'][1]['secret']);
			$this->assertFalse($formatted['recipients'][1]['secret']['updatable']);

			$this->assertArrayHasKey('value', $formatted['recipients'][1]['secret']);
			$this->assertIsString($formatted['recipients'][1]['secret']['value']);
			$this->assertNotEmpty($formatted['recipients'][1]['secret']['value']);

			$this->assertArrayHasKey('url', $formatted['recipients'][1]['secret']);
			$this->assertIsString($formatted['recipients'][1]['secret']['url']);
			$this->assertMatchesRegularExpression('/http:\/\/localhost\/index\.php\/s\/.+/', $formatted['recipients'][1]['secret']['url']);
		} else {
			$this->assertArrayNotHasKey('url', $formatted['recipients'][1]);
		}
	}

	public function testGetShareWithSecret(): void {
		$this->registry->clear();
		$this->registry->registerSourceType(new TestShareSourceType1(['source1' => 'Source']));
		$this->registry->registerRecipientType(
			new TestShareRecipientTypePublicSecret(
				[
					'recipient1' => 'Recipient 1',
					'recipient2' => 'Recipient 2',
					'recipient3' => 'Recipient 3',
					'recipient4' => 'Recipient 4',
				],
				[
					$this->user1->getUID() => ['recipient1'],
					$this->user2->getUID() => ['recipient2'],
				],
				true,
				false,
			)
		);
		$this->registry->registerPermissionType(null, Server::get(ReshareSharePermissionType::class));

		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientTypePublicSecret::class, 'recipient1', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientTypePublicSecret::class, 'recipient2', null)
		);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user1), $share, new ShareRecipient(TestShareRecipientTypePublicSecret::class, 'recipient3', null)
		);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext($this->user2), $share, new ShareRecipient(TestShareRecipientTypePublicSecret::class, 'recipient4', null)
		);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$formatted = $this->getShare(new ShareAccessContext($this->user2), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));

		usort($formatted['recipients'], fn (array $a, array $b): int => $a['value'] <=> $b['value']);
		$this->assertArrayHasKey('recipients', $formatted);
		$this->assertIsArray($formatted['recipients']);
		$this->assertCount(4, $formatted['recipients']);

		// Parent - secret not visible
		$this->assertIsArray($formatted['recipients'][0]);
		$this->assertArrayHasKey('value', $formatted['recipients'][0]);
		$this->assertEquals('recipient1', $formatted['recipients'][0]['value']);
		$this->assertArrayHasKey('secret', $formatted['recipients'][0]);
		$this->assertIsArray($formatted['recipients'][0]['secret']);
		$this->assertArrayNotHasKey('value', $formatted['recipients'][0]['secret']);

		// Self - secret visible
		$this->assertIsArray($formatted['recipients'][1]);
		$this->assertArrayHasKey('value', $formatted['recipients'][1]);
		$this->assertEquals('recipient2', $formatted['recipients'][1]['value']);
		$this->assertArrayHasKey('secret', $formatted['recipients'][1]);
		$this->assertIsArray($formatted['recipients'][1]['secret']);
		$this->assertNotEmpty($formatted['recipients'][1]['secret']['value']);

		// Sibling - secret not visible
		$this->assertIsArray($formatted['recipients'][2]);
		$this->assertArrayHasKey('value', $formatted['recipients'][2]);
		$this->assertEquals('recipient3', $formatted['recipients'][2]['value']);
		$this->assertArrayHasKey('secret', $formatted['recipients'][2]);
		$this->assertIsArray($formatted['recipients'][2]['secret']);
		$this->assertArrayNotHasKey('value', $formatted['recipients'][2]['secret']);

		// Child - secret visible
		$this->assertIsArray($formatted['recipients'][3]);
		$this->assertArrayHasKey('value', $formatted['recipients'][3]);
		$this->assertEquals('recipient4', $formatted['recipients'][3]['value']);
		$this->assertArrayHasKey('secret', $formatted['recipients'][3]);
		$this->assertIsArray($formatted['recipients'][3]['secret']);
		$this->assertNotEmpty($formatted['recipients'][3]['secret']['value']);
	}

	public function testGetShareUniqueDisplayNames(): void {
		$this->registry->clear();
		$this->registry->registerSourceType(new TestShareSourceType1(['source1' => 'Source']));
		$this->registry->registerSourceType(new TestShareSourceType2(['source2' => 'Source', 'source3' => 'Other']));
		$this->registry->registerRecipientType(new TestShareRecipientType1(['recipient1' => 'Recipient'], [], []));
		$this->registry->registerRecipientType(new TestShareRecipientType2(['recipient2' => 'Recipient', 'recipient3' => 'Other'], [], []));

		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType2::class, 'source2'));
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType2::class, 'source3'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient3', null));

		$this->dbConnection->commit();

		$formatted = $this->getShare($accessContext, $share->id);

		// Sort because database order is not guaranteed
		usort($formatted['sources'], fn (array $a, array $b): int => $a['value'] <=> $b['value']);
		usort($formatted['recipients'], fn (array $a, array $b): int => $a['value'] <=> $b['value']);
		$this->assertEquals([
			[
				'class' => TestShareSourceType1::class,
				'value' => 'source1',
				'display_name' => 'Source (TestShareSourceType1: source1)',
				'icon' => [
					'svg' => '<svg/>',
				],
			],
			[
				'class' => TestShareSourceType2::class,
				'value' => 'source2',
				'display_name' => 'Source (TestShareSourceType2: source2)',
				'icon' => [
					'svg' => '<svg/>',
				],
			],
			[
				'class' => TestShareSourceType2::class,
				'value' => 'source3',
				'display_name' => 'Other',
				'icon' => [
					'svg' => '<svg/>',
				],
			],
		], $formatted['sources']);
		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient (TestShareRecipientType1: recipient1)',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient (TestShareRecipientType2: recipient2)',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient3',
				'instance' => null,
				'display_name' => 'Other',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
		], $formatted['recipients']);
	}

	public function testGetShareDisabledOwner(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$this->owner->setEnabled(false);

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user1), $share->id);
			$this->fail('Share still visible.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}

		$formatted = $this->getShare(new ShareAccessContext(overrideChecks: true), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			'user_id' => 'owner',
			'instance' => null,
			'display_name' => 'Owner',
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner/64',
				'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
			],
		], $formatted['owner']);
	}

	public function testGetShareDisabledInitiator(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext(currentUser: $this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);

		$this->dbConnection->commit();
		$after = $this->manager->getTime();

		$this->user1->setEnabled(false);

		try {
			$this->getShare(new ShareAccessContext(currentUser: $this->user2), $share->id);
			$this->fail('Share still visible.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}

		$formatted = $this->getShare($accessContext, $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient 1',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient 2',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'user1',
					'instance' => null,
					'display_name' => 'User 1',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/user1/64',
						'dark' => 'http://localhost/index.php/avatar/user1/64/dark',
					],
				],
			],
		], $formatted['recipients']);
	}

	public function testGetShares(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$before1 = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share1 = $this->manager->createShare($accessContext);
		$share1 = $this->manager->addShareSource($accessContext, $share1, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share1 = $this->manager->addShareRecipient($accessContext, $share1, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share1 = $this->manager->updateSharePermission($accessContext, $share1, new SharePermission(TestSharePermissionType1::class, true));

		$this->dbConnection->commit();
		$after1 = $this->manager->getTime();

		$before2 = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share2 = $this->manager->createShare($accessContext);
		$share2 = $this->manager->addShareSource($accessContext, $share2, new ShareSource(TestShareSourceType2::class, 'source2'));
		$share2 = $this->manager->addShareRecipient($accessContext, $share2, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null));
		$share2 = $this->manager->updateSharePermission($accessContext, $share2, new SharePermission(TestSharePermissionType2::class, true));
		$share2 = $this->manager->updateShareState($accessContext, $share2, ShareState::Active);

		$this->dbConnection->commit();
		$after2 = $this->manager->getTime();

		$accessContext2 = new ShareAccessContext($this->user2);
		$this->dbConnection->beginTransaction();
		$this->manager->updateShareUserStatus($accessContext2, $this->manager->getShare($accessContext2, $share2->id), ShareUserStatus::Accepted);
		$this->dbConnection->commit();

		$formatted = $this->getShares($accessContext, null, null, null, null, null, null);
		$this->assertCount(2, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before1, $after1, $this->parseTime($formatted[0]['last_updated']));
		$this->assertIsArray($formatted[1]);
		$this->assertDateBetween($before2, $after2, $this->parseTime($formatted[1]['last_updated']));
		unset($formatted[0]['last_updated'], $formatted[1]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share1->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Draft->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType1::class,
						'value' => 'source1',
						'display_name' => 'Source 1',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType1::class,
						'value' => 'recipient1',
						'instance' => null,
						'display_name' => 'Recipient 1',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType1::class,
						'display_name' => 'TestSharePropertyType1',
						'hint' => 'hint TestSharePropertyType1',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid1'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType1::class,
						'source_class' => TestShareSourceType1::class,
						'display_name' => 'TestSharePermissionType1',
						'hint' => 'hint TestSharePermissionType1',
						'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset1::class,
			],
			[
				'id' => $share2->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Active->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType2::class,
						'value' => 'source2',
						'display_name' => 'Source 2',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType2::class,
						'value' => 'recipient2',
						'instance' => null,
						'display_name' => 'Recipient 2',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType2::class,
						'display_name' => 'TestSharePropertyType2',
						'hint' => 'hint TestSharePropertyType2',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid2'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType2::class,
						'source_class' => TestShareSourceType2::class,
						'display_name' => 'TestSharePermissionType2',
						'hint' => 'hint TestSharePermissionType2',
						'presets' => [TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset2::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext, TestShareSourceType1::class, null, null, null, null, null);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before1, $after1, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share1->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Draft->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType1::class,
						'value' => 'source1',
						'display_name' => 'Source 1',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType1::class,
						'value' => 'recipient1',
						'instance' => null,
						'display_name' => 'Recipient 1',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType1::class,
						'display_name' => 'TestSharePropertyType1',
						'hint' => 'hint TestSharePropertyType1',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid1'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType1::class,
						'source_class' => TestShareSourceType1::class,
						'display_name' => 'TestSharePermissionType1',
						'hint' => 'hint TestSharePermissionType1',
						'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset1::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext, TestShareSourceType1::class, 'source1', null, null, null, null);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before1, $after1, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share1->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Draft->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType1::class,
						'value' => 'source1',
						'display_name' => 'Source 1',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType1::class,
						'value' => 'recipient1',
						'instance' => null,
						'display_name' => 'Recipient 1',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType1::class,
						'display_name' => 'TestSharePropertyType1',
						'hint' => 'hint TestSharePropertyType1',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid1'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType1::class,
						'source_class' => TestShareSourceType1::class,
						'display_name' => 'TestSharePermissionType1',
						'hint' => 'hint TestSharePermissionType1',
						'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset1::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext, TestShareSourceType1::class, 'non-existent', null, null, null, null);
		$this->assertCount(0, $formatted);

		$formatted = $this->getShares($accessContext, null, null, ShareState::Draft, null, null, null);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before1, $after1, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share1->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Draft->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType1::class,
						'value' => 'source1',
						'display_name' => 'Source 1',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType1::class,
						'value' => 'recipient1',
						'instance' => null,
						'display_name' => 'Recipient 1',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType1::class,
						'display_name' => 'TestSharePropertyType1',
						'hint' => 'hint TestSharePropertyType1',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid1'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType1::class,
						'source_class' => TestShareSourceType1::class,
						'display_name' => 'TestSharePermissionType1',
						'hint' => 'hint TestSharePermissionType1',
						'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset1::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext, null, null, ShareState::Active, null, null, null);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before2, $after2, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share2->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Active->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType2::class,
						'value' => 'source2',
						'display_name' => 'Source 2',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType2::class,
						'value' => 'recipient2',
						'instance' => null,
						'display_name' => 'Recipient 2',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType2::class,
						'display_name' => 'TestSharePropertyType2',
						'hint' => 'hint TestSharePropertyType2',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid2'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType2::class,
						'source_class' => TestShareSourceType2::class,
						'display_name' => 'TestSharePermissionType2',
						'hint' => 'hint TestSharePermissionType2',
						'presets' => [TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset2::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext2, null, null, null, ShareUserStatus::Pending, null, null);
		$this->assertCount(0, $formatted);

		$formatted = $this->getShares($accessContext2, null, null, null, ShareUserStatus::Accepted, null, null);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before2, $after2, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share2->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Active->value,
				'user_status' => ShareUserStatus::Accepted->value,
				'sources' => [
					[
						'class' => TestShareSourceType2::class,
						'value' => 'source2',
						'display_name' => 'Source 2',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType2::class,
						'value' => 'recipient2',
						'instance' => null,
						'display_name' => 'Recipient 2',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType2::class,
						'display_name' => 'TestSharePropertyType2',
						'hint' => 'hint TestSharePropertyType2',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid2'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType2::class,
						'source_class' => TestShareSourceType2::class,
						'display_name' => 'TestSharePermissionType2',
						'hint' => 'hint TestSharePermissionType2',
						'presets' => [TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset2::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext2, null, null, null, ShareUserStatus::Rejected, null, null);
		$this->assertCount(0, $formatted);

		$formatted = $this->getShares($accessContext, null, null, null, null, $share1->id, null);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before2, $after2, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share2->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Active->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType2::class,
						'value' => 'source2',
						'display_name' => 'Source 2',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType2::class,
						'value' => 'recipient2',
						'instance' => null,
						'display_name' => 'Recipient 2',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType2::class,
						'display_name' => 'TestSharePropertyType2',
						'hint' => 'hint TestSharePropertyType2',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid2'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType2::class,
						'source_class' => TestShareSourceType2::class,
						'display_name' => 'TestSharePermissionType2',
						'hint' => 'hint TestSharePermissionType2',
						'presets' => [TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset2::class,
			],
		], $formatted);

		$formatted = $this->getShares($accessContext, null, null, null, null, null, 1);
		$this->assertCount(1, $formatted);
		$this->assertIsArray($formatted[0]);
		$this->assertDateBetween($before1, $after1, $this->parseTime($formatted[0]['last_updated']));
		unset($formatted[0]['last_updated']);
		$this->assertEquals([
			[
				'id' => $share1->id,
				'owner' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
				'state' => ShareState::Draft->value,
				'user_status' => null,
				'sources' => [
					[
						'class' => TestShareSourceType1::class,
						'value' => 'source1',
						'display_name' => 'Source 1',
						'icon' => [
							'svg' => '<svg/>',
						],
					],
				],
				'recipients' => [
					[
						'class' => TestShareRecipientType1::class,
						'value' => 'recipient1',
						'instance' => null,
						'display_name' => 'Recipient 1',
						'icon' => [
							'svg' => '<svg/>',
						],
						'secret' => [
							'updatable' => false,
						],
						'initiator' => [
							'user_id' => 'owner',
							'instance' => null,
							'display_name' => 'Owner',
							'icon' => [
								'light' => 'http://localhost/index.php/avatar/owner/64',
								'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
							],
						],
					],
				],
				'properties' => [
					[
						'class' => TestSharePropertyType1::class,
						'display_name' => 'TestSharePropertyType1',
						'hint' => 'hint TestSharePropertyType1',
						'priority' => 1,
						'advanced' => false,
						'required' => false,
						'value' => null,
						'type' => 'enum',
						'valid_values' => ['valid1'],
					],
				],
				'permissions' => [
					[
						'class' => ReshareSharePermissionType::class,
						'source_class' => null,
						'display_name' => 'Share with others',
						'hint' => null,
						'presets' => [],
						'enabled' => false,
						'priority' => 90,
					],
					[
						'class' => TestSharePermissionType1::class,
						'source_class' => TestShareSourceType1::class,
						'display_name' => 'TestSharePermissionType1',
						'hint' => 'hint TestSharePermissionType1',
						'presets' => [TestSharePermissionPreset1::class, TestSharePermissionPreset2::class],
						'enabled' => true,
						'priority' => 1,
					],
				],
				'permission_preset' => TestSharePermissionPreset1::class,
			],
		], $formatted);
	}

	public function testGetSharesSorted(): void {
		$accessContext = new ShareAccessContext(currentUser: $this->owner);

		$this->dbConnection->beginTransaction();
		$share1 = $this->manager->createShare($accessContext);
		$share2 = $this->manager->createShare($accessContext);
		$this->dbConnection->commit();

		$shares = $this->getShares($accessContext, null, null, null, null, null, null);
		$this->assertIsArray($shares[0]);
		$this->assertArrayHasKey('id', $shares[0]);
		$this->assertIsArray($shares[1]);
		$this->assertArrayHasKey('id', $shares[1]);
		$this->assertEquals($share1->id, $shares[0]['id']);
		$this->assertEquals($share2->id, $shares[1]['id']);

		$this->dbConnection->beginTransaction();
		$this->manager->addShareSource($accessContext, $share2, new ShareSource(TestShareSourceType2::class, 'source2'));
		$this->manager->getShare($accessContext, $share2->id);
		$this->manager->updateSharePermission($accessContext, $share2, new SharePermission(TestSharePermissionType2::class, true));

		$this->dbConnection->commit();

		$formatted = $this->getShares($accessContext, null, null, null, null, null, null);
		$this->assertIsArray($formatted[0]);
		$this->assertArrayHasKey('id', $formatted[0]);
		$this->assertIsArray($formatted[1]);
		$this->assertArrayHasKey('id', $formatted[1]);
		$this->assertEquals($share2->id, $formatted[0]['id']);
		$this->assertEquals($share1->id, $formatted[1]['id']);
	}

	public function testOwnerDeleted(): void {
		$accessContext = new ShareAccessContext(currentUser: $this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$this->owner->delete();
		$this->dbConnection->commit();

		try {
			$this->getShare(new ShareAccessContext(overrideChecks: true), $share->id);
			$this->fail('Share still exists.');
		} catch (HintException $hintException) {
			$this->assertEquals('Share not found.', $hintException->getHint());
		}
	}

	public function testInitiatorDeleted(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));
		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(ReshareSharePermissionType::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);
		$share = $this->manager->addShareRecipient(
			new ShareAccessContext(currentUser: $this->user1), $share, new ShareRecipient(TestShareRecipientType2::class, 'recipient2', null)
		);

		$before = $this->manager->getTime();
		$this->user1->delete();
		$after = $this->manager->getTime();
		$this->dbConnection->commit();

		$formatted = $this->getShare(new ShareAccessContext(overrideChecks: true), $share->id);
		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			[
				'class' => TestShareRecipientType1::class,
				'value' => 'recipient1',
				'instance' => null,
				'display_name' => 'Recipient 1',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
			[
				'class' => TestShareRecipientType2::class,
				'value' => 'recipient2',
				'instance' => null,
				'display_name' => 'Recipient 2',
				'icon' => [
					'svg' => '<svg/>',
				],
				'secret' => [
					'updatable' => false,
				],
				'initiator' => [
					'user_id' => 'owner',
					'instance' => null,
					'display_name' => 'Owner',
					'icon' => [
						'light' => 'http://localhost/index.php/avatar/owner/64',
						'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
					],
				],
			],
		], $formatted['recipients']);
	}

	public function testGetWithDirectAccess(): void {
		$accessContext = new ShareAccessContext($this->owner);
		$accessContext2 = new ShareAccessContext(currentUser: $this->user2);

		$before = $this->manager->getTime();
		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		$after = $this->manager->getTime();

		// user2 has no direct access, no shares
		$formattedShares = $this->getShares($accessContext2, TestShareSourceType1::class, 'source1', null, null, null, null);
		$this->assertCount(0, $formattedShares);

		try {
			$this->getShare($accessContext2, $share->id);
			$this->fail('user has invalid share access');
		} catch (HintException) {

		}

		// give user2 direct access, can see shares
		$this->shareSourceType1->userAccess[$this->user2->getUID()] = ['source1'];
		$formattedShares = $this->getShares($accessContext2, TestShareSourceType1::class, 'source1', null, null, null, null);

		$this->assertCount(1, $formattedShares);
		$formatted = $formattedShares[0];

		$this->assertDateBetween($before, $after, $this->parseTime($formatted['last_updated']));
		$this->assertEquals([
			'user_id' => 'owner',
			'instance' => null,
			'display_name' => 'Owner',
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/owner/64',
				'dark' => 'http://localhost/index.php/avatar/owner/64/dark',
			],
		], $formatted['owner']);

		$singleFormatted = $this->getShare($accessContext2, $share->id);
		$this->assertEquals($singleFormatted, $formatted);

		// add a source that user2 doesn't have access to, can't see share anymore
		$this->dbConnection->beginTransaction();
		$this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType2::class, 'source2'));
		$this->dbConnection->commit();
		$formattedShares = $this->getShares(new ShareAccessContext(currentUser: $this->user2), TestShareSourceType1::class, 'source1', null, null, null, null);
		$this->assertCount(0, $formattedShares);
	}

	public function testEditWithDirectAccess(): void {
		$accessContext = new ShareAccessContext($this->owner);
		$accessContext2 = new ShareAccessContext(currentUser: $this->user2);

		$this->dbConnection->beginTransaction();
		$share = $this->manager->createShare($accessContext);
		$share = $this->manager->addShareSource($accessContext, $share, new ShareSource(TestShareSourceType1::class, 'source1'));
		$share = $this->manager->addShareRecipient($accessContext, $share, new ShareRecipient(TestShareRecipientType1::class, 'recipient1', null));

		$share = $this->manager->updateSharePermission($accessContext, $share, new SharePermission(TestSharePermissionType1::class, true));
		$share = $this->manager->updateShareState($accessContext, $share, ShareState::Active);

		$this->dbConnection->commit();

		try {
			$this->updateShareProperty($accessContext2, $share, new ShareProperty(TestSharePropertyType1::class, 'valid1'));
			$this->fail('update allowed');
		} catch (HintException) {

		}

		// give user2 direct access
		$this->shareSourceType1->userAccess[$this->user2->getUID()] = ['source1'];
		$user2Share = $this->reloadShare($accessContext2, $share);
		$this->assertNull($user2Share->recipients[0]->secret);
		$formatted = $this->updateShareProperty($accessContext2, $user2Share, new ShareProperty(TestSharePropertyType1::class, 'valid1'));

		$this->assertEquals([
			[
				'display_name' => 'TestSharePropertyType1',
				'hint' => 'hint TestSharePropertyType1',
				'priority' => 1,
				'required' => false,
				'advanced' => false,
				'value' => 'valid1',
				'class' => \Test\Sharing\TestSharePropertyType1::class,
				'type' => 'enum',
				'valid_values' => ['valid1'],
			]
		], $formatted['properties']);
	}
}
