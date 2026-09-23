<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use NCU\Sharing\ISharingBackend;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\ShareUserStatus;
use NCU\Sharing\Source\ShareSource;
use OC\Core\AppInfo\Application;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OC\Core\Sharing\Property\LabelSharePropertyType;
use OC\Core\Sharing\Property\NoteSharePropertyType;
use OC\Core\Sharing\Property\PasswordSharePropertyType;
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OC\Core\Sharing\Recipient\GroupShareRecipientType;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\Share20\DefaultShareProvider;
use OC\Share20\ShareAttributes;
use OC\Sharing\LegacyMapper;
use OC\Sharing\SharingLegacySync;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Property\NodeGridViewSharePropertyType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Files\Sharing\SourceNodeTargetManager;
use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IUser;
use OCP\Security\IHasher;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Snowflake\ISnowflakeDecoder;
use OCP\Snowflake\ISnowflakeGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Test\TestCase;
use Test\Traits\GroupTrait;
use Test\Traits\UserTrait;

// TODO: shareTime is unreliably and sometimes off by one second
#[Group('DB')]
final class SharingLegacySyncTest extends TestCase {
	use UserTrait;
	use GroupTrait;

	private ISharingRegistry $sharingRegistry;

	private ISharingManager $sharingManager;

	private ISharingBackend $sharingBackend;

	private IManager $legacySharingManager;

	private SharingLegacySync $legacySync;

	private LegacyMapper $legacyMapper;

	private IDBConnection $dbConnection;

	private ISnowflakeGenerator $snowflakeGenerator;

	private ISnowflakeDecoder $snowflakeDecoder;

	private SourceNodeTargetManager $sourceNodeTargetManager;

	private IUser $owner;

	private IUser $user1;

	private IUser $user2;

	private IGroup $group;

	private Folder $nodeFolder;

	private File $nodeFile;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->sharingRegistry = Server::get(ISharingRegistry::class);
		$this->sharingManager = Server::get(ISharingManager::class);
		$this->sharingBackend = Server::get(ISharingBackend::class);
		$this->legacySharingManager = Server::get(IManager::class);
		$this->legacySync = Server::get(SharingLegacySync::class);
		$this->legacyMapper = Server::get(LegacyMapper::class);
		$this->dbConnection = Server::get(IDBConnection::class);
		$this->snowflakeGenerator = Server::get(ISnowflakeGenerator::class);
		$this->snowflakeDecoder = Server::get(ISnowflakeDecoder::class);
		$this->sourceNodeTargetManager = Server::get(SourceNodeTargetManager::class);

		$this->sharingRegistry->clear();
		$this->invokePrivate(Server::get(Application::class), 'registerSharing');
		$this->invokePrivate(Server::get(\OCA\Files\AppInfo\Application::class), 'registerSharing');

		$this->owner = $this->createUser('owner', 'password');
		$this->user1 = $this->createUser('user1', 'password');
		$this->user2 = $this->createUser('user2', 'password');
		$this->group = $this->createGroup('group');
		$this->groupBackend->addToGroup($this->user1->getUID(), $this->group->getGID());

		$ownerFolder = Server::get(IRootFolder::class)->getUserFolder($this->owner->getUID());
		$this->nodeFolder = $ownerFolder->newFolder('foo');
		$this->nodeFile = $ownerFolder->newFile('foo.txt');

		$this->dbConnection->beginTransaction();
	}

	#[\Override]
	public function tearDown(): void {
		$this->clearShares();

		$this->sharingRegistry->clear();

		Server::get(ISetupManager::class)->tearDown();

		// Can't rollback, because it would rollback the classmap data, but the ClassMapper still has the values cached.
		$this->dbConnection->commit();
		parent::tearDown();
	}

	private function clearShares(): void {
		foreach ($this->legacySharingManager->getAllShares() as $legacyShare) {
			$this->legacySharingManager->deleteShare($legacyShare);
		}

		$accessContext = new ShareAccessContext(overrideChecks: true);
		foreach ($this->sharingManager->getShares($accessContext, null, null, null, null, null, null) as $share) {
			$this->sharingManager->deleteShare($accessContext, $share);
		}

		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->delete('sharing_source_node_target')
			->executeStatement();
	}

	/**
	 * {@see \OCP\Share\IManager::getAllShares()} does not return shares of {@see IShare::TYPE_USERGROUP}, which we need to verify the behavior.
	 * @return list<IShare>
	 */
	private function getAllLegacyShares(): array {
		$providers = Server::get(IProviderFactory::class)->getAllProviders();

		$legacyShares = [];
		foreach ($providers as $provider) {
			if ($provider->identifier() === 'ocinternal') {
				/** @var DefaultShareProvider $provider */
				$legacyShares[] = array_values(iterator_to_array($provider->getAllShares(true)));
			} else {
				$legacyShares[] = array_values(iterator_to_array($provider->getAllShares()));
			}
		}

		return array_merge(...$legacyShares);
	}

	/**
	 * @param array<string, ShareUserStatus> $shareUserStatuses
	 */
	private function createShare(Share $share, array $shareUserStatuses): void {
		$this->sharingBackend->createShare($share->id, $share->owner, $share->getCreatedAt());

		foreach ($share->sources as $source) {
			$this->sharingBackend->addShareSource($share->id, $source);
		}

		foreach ($share->recipients as $recipient) {
			$this->sharingBackend->addShareRecipient($share->id, $recipient);
		}

		foreach ($share->properties as $property) {
			$this->sharingBackend->updateShareProperty($share->id, $property);
		}

		foreach ($share->permissions as $permission) {
			$this->sharingBackend->updateSharePermission($share->id, $permission);
		}

		if ($share->userStatus instanceof \NCU\Sharing\ShareUserStatus) {
			throw new RuntimeException('Do not pass a user status directly.');
		}

		foreach ($shareUserStatuses as $userId => $userStatus) {
			$this->sharingBackend->updateShareUserStatus($share->id, $userId, $userStatus);
		}

		// Only use the manager here, so the update event is dispatched.
		$this->sharingManager->updateShareState(new ShareAccessContext(overrideChecks: true), $share, $share->state);
	}

	/**
	 * @param list<IShare> $legacyShares
	 * @return list<IShare>
	 */
	private function createLegacyShares(array $legacyShares): array {
		$filteredLegacyShares = array_values(array_filter($legacyShares, static fn (IShare $legacyShare): bool => $legacyShare->getShareType() !== IShare::TYPE_USERGROUP));
		/** @var non-empty-list<IShare> $filteredLegacyShares */
		$this->assertNotEmpty($filteredLegacyShares);
		foreach ($filteredLegacyShares as &$legacyShare) {
			$legacyShareWithoutProvider = clone $legacyShare;
			$this->invokePrivate($legacyShareWithoutProvider, 'providerId', [null]);
			$legacyShareWithoutProvider = $this->legacySharingManager->createShare($legacyShareWithoutProvider);
			// The share might get auto accepted, so we have to manually set the desired status.
			$legacyShareWithoutProvider->setStatus($legacyShare->getStatus() ?? IShare::STATUS_PENDING);
			$legacyShare = $this->legacySharingManager->updateShare($legacyShareWithoutProvider);
		}

		// Ensure child shares have been created
		$this->assertCount(count($legacyShares), $this->getAllLegacyShares());

		$legacyChildStatuses = array_values(array_unique(array_map(static fn (IShare $legacyShare): ?int => $legacyShare->getStatus(), array_filter($legacyShares, static fn (IShare $legacyShare): bool => $legacyShare->getShareType() === IShare::TYPE_USERGROUP))));
		if ($legacyChildStatuses !== []) {
			if (count($legacyChildStatuses) > 1) {
				throw new RuntimeException('More than one status value, not working yet.');
			}

			$this->assertNotNull($legacyChildStatuses[0]);

			// The child shares get auto accepted, so we have to manually set the desired status.
			$qb = $this->dbConnection->getQueryBuilder();
			$rowCount = $qb
				->update('share')
				->set('accepted', $qb->createNamedParameter($legacyChildStatuses[0], IQueryBuilder::PARAM_INT))
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->in('parent', $qb->createNamedParameter(array_map(static fn (IShare $legacyShare): int => (int)$legacyShare->getId(), $filteredLegacyShares), IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();
			$this->assertEquals(count(array_filter($legacyShares, static fn (IShare $legacyShare): bool => $legacyShare->getShareType() === IShare::TYPE_USERGROUP)), $rowCount);
		}

		$legacyChildTargets = array_values(array_unique(array_map(static fn (IShare $legacyShare): string => $legacyShare->getTarget(), array_filter($legacyShares, static fn (IShare $legacyShare): bool => $legacyShare->getShareType() === IShare::TYPE_USERGROUP))));
		if ($legacyChildTargets !== []) {
			if (count($legacyChildTargets) > 1) {
				throw new RuntimeException('More than one target value, not working yet.');
			}

			// The child shares get auto accepted, so we have to manually set the desired target.
			$qb = $this->dbConnection->getQueryBuilder();
			$rowCount = $qb
				->update('share')
				->set('file_target', $qb->createNamedParameter($legacyChildTargets[0]))
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->in('parent', $qb->createNamedParameter(array_map(static fn (IShare $legacyShare): int => (int)$legacyShare->getId(), $filteredLegacyShares), IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();
			$this->assertEquals(count(array_filter($legacyShares, static fn (IShare $legacyShare): bool => $legacyShare->getShareType() === IShare::TYPE_USERGROUP)), $rowCount);
		}

		if ($legacyChildStatuses !== [] || $legacyChildTargets !== []) {
			// Just update anything to trigger the syncing
			$this->legacySharingManager->updateShare($filteredLegacyShares[0]);
		}

		return $filteredLegacyShares;
	}

	private function fixLegacyShare(IShare $legacyShare): IShare {
		// Having the node set seems to trigger and infinite loop in PHPUnit when comparing the objects.
		$node = $legacyShare->getNode();
		$legacyShare->setNodeId($node->getId());
		$legacyShare->setNodeType($node instanceof File ? 'file': 'folder');

		// There might be a closure that we can't reproduce and we want the value the closure returns to be set.
		$legacyShare->getSharedWithDisplayName();

		// The original target is only an internal property and not stored in the DB.
		$this->invokePrivate($legacyShare, 'originalTarget', [null]);

		return $legacyShare;
	}

	private function fixShare(Share $share): Share {
		foreach ($share->sources as $source) {
			$this->invokePrivate($source, 'getMetadata', [$this->sharingRegistry]);
		}

		return $share;
	}

	/**
	 * @param list<IShare> $expected
	 * @param list<IShare> $actual
	 */
	private function assertLegacySharesEquals(array $expected, array $actual): void {
		$expected = array_map(
			function (int $index, IShare $legacyShare) use ($actual): IShare {
				$legacyShare->setId($actual[$index]->getId());
				if (($parent = $legacyShare->getParent()) !== null) {
					$legacyShare->setParent((int)$actual[$parent]->getId());
				}

				$shareTime = $legacyShare->getShareTime();
				$legacyShare->setShareTime($shareTime->setTime(
					(int)$shareTime->format('H'),
					(int)$shareTime->format('i'),
					(int)$shareTime->format('s'),
				));

				return $legacyShare;
			},
			array_keys($expected),
			$expected,
		);

		$this->assertEquals(array_map($this->fixLegacyShare(...), $expected), array_map($this->fixLegacyShare(...), $actual));
	}

	private function assertSharesEquals(Share $expected, Share $actual): void {
		$this->assertEquals($this->fixShare($expected), $this->fixShare($actual));
	}

	/**
	 * @param list<IShare> $expectedLegacyShares
	 * @param list<Share> $actualShares
	 * @param array<int, array<string, ShareUserStatus>> $shareUserStatuses
	 */
	private function assertSyncingShareToLegacySharesWorks(array $expectedLegacyShares, array $actualShares, array $shareUserStatuses): void {
		foreach ($actualShares as $index => $actualShare) {
			$this->createShare($actualShare, $shareUserStatuses[$index] ?? []);
		}

		$actualLegacyShares = $this->getAllLegacyShares();
		$this->assertCount(count($expectedLegacyShares), $actualLegacyShares);
		$this->assertLegacySharesEquals($expectedLegacyShares, $actualLegacyShares);

		$this->clearShares();
	}

	/**
	 * @param list<Share> $expectedShares
	 * @param list<IShare> $actualLegacyShares
	 * @param array<int, array<string, ShareUserStatus>> $shareUserStatuses
	 */
	private function assertSyncingLegacySharesToShareWorks(array $expectedShares, array $actualLegacyShares, array $shareUserStatuses, bool $ignoreRecipientValues): void {
		$this->createLegacyShares($actualLegacyShares);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(currentUser: $this->user1, overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(count($expectedShares), $actualShares);
		foreach ($actualShares as $index => $actualShare) {
			$expectedShare = $expectedShares[$index];

			$this->assertSharesEquals(
				new Share(
					$actualShare->id,
					$expectedShare->owner,
					$actualShare->lastUpdated,
					$expectedShare->state,
					($shareUserStatuses[$index] ?? [])[$this->user1->getUID()] ?? null,
					$expectedShare->sources,
					array_map(
						fn (int $index, ShareRecipient $recipient): ShareRecipient => new ShareRecipient(
							$recipient->class,
							$ignoreRecipientValues ? $actualShare->recipients[$index]->value : $recipient->value,
							$recipient->instance,
							$actualShare->recipients[$index]->secret,
							$recipient->initiator,
							$recipient->permissions,
						),
						array_keys($expectedShare->recipients),
						$expectedShare->recipients,
					),
					$expectedShare->properties,
					$expectedShare->permissions,
				),
				$actualShare,
			);
		}

		$this->clearShares();
	}

	/**
	 * @param list<IShare> $expectedLegacyShares
	 */
	private function assertSyncingLegacySharesToShareAndBackWorks(array $expectedLegacyShares, bool $delete): void {
		$filteredExpectedLegacyShares = $this->createLegacyShares($expectedLegacyShares);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(currentUser: $this->user1, overrideChecks: true), null, null, null, null, null, null);

		if ($delete) {
			$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [true]);
			foreach ($filteredExpectedLegacyShares as &$expectedLegacyShare) {
				$this->legacySharingManager->deleteShare($expectedLegacyShare);
				$this->invokePrivate($expectedLegacyShare, 'id', [null]);
			}

			foreach ($actualShares as $actualShare) {
				$this->invokePrivate($this->legacyMapper, 'deleteLegacyMappings', [$actualShare->id]);
			}

			$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [false]);
		}

		// Just update anything to trigger the syncing
		foreach ($actualShares as $actualShare) {
			$this->sharingManager->updateShareState(new ShareAccessContext(overrideChecks: true), $actualShare, $actualShare->state);
		}

		$actualLegacyShares = $this->getAllLegacyShares();
		$this->assertCount(count($expectedLegacyShares), $actualLegacyShares);
		$this->assertLegacySharesEquals($expectedLegacyShares, $actualLegacyShares);

		$this->clearShares();
	}

	/**
	 * @param list<Share> $expectedShares
	 * @param array<int, array<string, ShareUserStatus>> $shareUserStatuses
	 */
	private function assertSyncingShareToLegacySharesAndBackWorks(array $expectedShares, array $shareUserStatuses, bool $delete, bool $ignoreRecipientValues): void {
		foreach ($expectedShares as $index => $expectedShare) {
			$this->createShare($expectedShare, $shareUserStatuses[$index] ?? []);
		}

		$actualLegacyShares = $this->getAllLegacyShares();

		if ($delete) {
			$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [true]);
			foreach ($expectedShares as $expectedShare) {
				$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $expectedShare);
				$this->invokePrivate($this->legacyMapper, 'deleteLegacyMappings', [$expectedShare->id]);
			}

			$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [false]);
		}

		// Just update anything to trigger the syncing
		$this->legacySharingManager->updateShare($actualLegacyShares[0]);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(currentUser: $this->user1, overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(count($expectedShares), $actualShares);
		foreach ($actualShares as $index => $actualShare) {
			$expectedShare = $expectedShares[$index];

			$this->assertSharesEquals(
				new Share(
					$actualShare->id,
					$expectedShare->owner,
					$actualShare->lastUpdated,
					$expectedShare->state,
					($shareUserStatuses[$index] ?? [])[$this->user1->getUID()] ?? null,
					$expectedShare->sources,
					array_map(
						fn (int $index, ShareRecipient $recipient): ShareRecipient => new ShareRecipient(
							$recipient->class,
							$ignoreRecipientValues ? $actualShare->recipients[$index]->value : $recipient->value,
							$recipient->instance,
							$actualShare->recipients[$index]->secret,
							$recipient->initiator,
							$recipient->permissions,
						),
						array_keys($expectedShare->recipients),
						$expectedShare->recipients,
					),
					$expectedShare->properties,
					$expectedShare->permissions,
				),
				$actualShare,
			);
		}

		$this->clearShares();
	}

	/**
	 * @param list<Share> $shares
	 * @param list<IShare> $legacyShares
	 * @param array<int, array<string, ShareUserStatus>> $shareUserStatuses
	 */
	private function assertSyncingWorks(array $shares, array $legacyShares, array $shareUserStatuses = [], bool $ignoreRecipientValues = false): void {
		$this->assertSyncingShareToLegacySharesWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), $shares, $shareUserStatuses);
		$this->assertSyncingLegacySharesToShareWorks($shares, array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), $shareUserStatuses, $ignoreRecipientValues);

		$this->assertSyncingShareToLegacySharesAndBackWorks($shares, $shareUserStatuses, false, false);
		$this->assertSyncingShareToLegacySharesAndBackWorks($shares, $shareUserStatuses, true, $ignoreRecipientValues);

		$this->assertSyncingLegacySharesToShareAndBackWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), false);
		$this->assertSyncingLegacySharesToShareAndBackWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), true);
	}

	/**
	 * @return array{non-empty-string, DateTimeImmutable}
	 */
	private function generateIdAndCreatedTimestamp(): array {
		// Generate a Snowflake ID for the past, to find all codepaths that use the current time instead of the specified time.
		$id = $this->snowflakeGenerator->nextId((new DateTimeImmutable())->sub(new DateInterval('PT1M')));
		$created = $this->snowflakeDecoder->decode($id)->getCreatedAt();
		return [$id, $created];
	}

	public function testDeleteShare(): void {
		$this->legacySharingManager->createShare(
			$this->legacySharingManager->newShare()
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
		);

		$shares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $shares);

		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $shares[0]);

		$this->assertEmpty(iterator_to_array($this->legacySharingManager->getAllShares()));
	}

	public function testDeleteLegacyShare(): void {
		$legacyShare = $this->legacySharingManager->createShare(
			$this->legacySharingManager->newShare()
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
		);

		$shares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $shares);

		$this->legacySharingManager->deleteShare($legacyShare);

		$this->assertEmpty($this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null));
	}

	/**
	 * @return list<array{ShareState, int}>
	 */
	public static function dataShareState(): array {
		return [
			[ShareState::Active, 1],
			[ShareState::Draft, 0],
			[ShareState::Deleted, 0],
		];
	}

	#[DataProvider('dataShareState')]
	public function testShareStateToLegacy(ShareState $shareState, int $legacySharesCount): void {
		$accessContext = new ShareAccessContext($this->owner);
		$share = $this->sharingManager->createShare($accessContext);
		$share = $this->sharingManager->addShareSource($accessContext, $share, new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId()));
		$share = $this->sharingManager->addShareRecipient($accessContext, $share, new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null));
		$share = $this->sharingManager->updateSharePermission($accessContext, $share, new SharePermission(NodeReadSharePermissionType::class, true));
		$share = $this->sharingManager->updateSharePermission($accessContext, $share, new SharePermission(NodeDownloadSharePermissionType::class, true));

		$this->sharingManager->updateShareState($accessContext, $share, $shareState);

		$legacyShares = array_values(iterator_to_array($this->legacySharingManager->getAllShares()));
		$this->assertCount($legacySharesCount, $legacyShares);
	}

	public function testShareStateFromLegacy(): void {
		$this->legacySharingManager->createShare(
			$this->legacySharingManager->newShare()
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
		);

		$shares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $shares);
		$this->assertEquals(ShareState::Active, $shares[0]->state);
	}

	/**
	 * @return list<array{ShareUserStatus, IShare::STATUS_*}>
	 */
	public static function dataShareUserStatus(): array {
		return [
			[ShareUserStatus::Pending, IShare::STATUS_PENDING],
			[ShareUserStatus::Accepted, IShare::STATUS_ACCEPTED],
			[ShareUserStatus::Rejected, IShare::STATUS_REJECTED],
		];
	}

	/**
	 * @param IShare::STATUS_* $legacyStatus
	 */
	#[DataProvider('dataShareUserStatus')]
	public function testShareUserStatus(ShareUserStatus $shareUserStatus, int $legacyStatus): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus($legacyStatus)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => $shareUserStatus,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	public function testDefaultTarget(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Accepted,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	public function testNonDefaultTarget(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [new Share(
			$id,
			$owner,
			$created,
			ShareState::Active,
			null,
			[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
			[new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), $owner)],
			[
				ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
				NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
			],
			[
				NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
				NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
				ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
				NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
				NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
				NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
			],
		),
		];

		$this->sourceNodeTargetManager->setTarget($this->user1->getUID(), $owner, $this->nodeFolder->getId(), '/abc');

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/abc')
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Accepted,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	public function testNonDefaultTargetGroup(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(GroupShareRecipientType::class, $this->group->getGID(), null, $this->sharingManager->generateSecret(), $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$this->sourceNodeTargetManager->setTarget($this->user1->getUID(), $owner, $this->nodeFolder->getId(), '/abc');

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_GROUP)
				->setSharedWith($this->group->getGID())
				->setSharedWithDisplayName($this->group->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_PENDING)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USERGROUP)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/abc')
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setParent(0),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Accepted,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);

	}

	/**
	 * @param IShare::STATUS_* $legacyStatus
	 */
	#[DataProvider('dataShareUserStatus')]
	public function testGroupUserStatus(ShareUserStatus $shareUserStatus, int $legacyStatus): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(GroupShareRecipientType::class, $this->group->getGID(), null, $this->sharingManager->generateSecret(), $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_GROUP)
				->setSharedWith($this->group->getGID())
				->setSharedWithDisplayName($this->group->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_PENDING)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USERGROUP)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus($legacyStatus)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setParent(0),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => $shareUserStatus,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	// TODO: Test group share with child share rejected

	public function testReshareToLegacy(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[
					new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), $owner),
					new ShareRecipient(UserShareRecipientType::class, $this->user2->getUID(), null, $this->sharingManager->generateSecret(), new ShareUser($this->user1->getUID(), null)),
				],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, true),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user2->getUID())
				->setSharedWithDisplayName($this->user2->getDisplayName())
				->setSharedBy($this->user1->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Accepted,
				$this->user2->getUID() => ShareUserStatus::Accepted,
			],
		];

		// In this direction we can sync reshares correctly, because all information is contained in the share.
		$this->assertSyncingShareToLegacySharesWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), $shares, $shareUserStatuses);
		$this->assertSyncingShareToLegacySharesAndBackWorks($shares, $shareUserStatuses, false, false);
		// Can't test syncing forth and back with deletion, because the information for linking reshares is missing in legacy shares.
	}

	public function testReshareFromLegacy(): void {
		[$id1, $created1] = $this->generateIdAndCreatedTimestamp();
		[$id2, $created2] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id1,
				$owner,
				$created1,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[
					new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), $owner),
				],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, true),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
			new Share(
				$id2,
				$owner,
				$created2,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[
					new ShareRecipient(UserShareRecipientType::class, $this->user2->getUID(), null, $this->sharingManager->generateSecret(), new ShareUser($this->user1->getUID(), null)),
				],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, true),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created1))
				->setMailSend(false),
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user2->getUID())
				->setSharedWithDisplayName($this->user2->getDisplayName())
				->setSharedBy($this->user1->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created2))
				->setMailSend(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Accepted,
			],
			[
				$this->user1->getUID() => ShareUserStatus::Pending,
			],
		];

		// In this direction we can't sync reshares correctly, because the information for linking reshares is missing in legacy shares.
		$this->assertSyncingLegacySharesToShareWorks($shares, array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), $shareUserStatuses, false);
		$this->assertSyncingLegacySharesToShareAndBackWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), false);
		$this->assertSyncingLegacySharesToShareAndBackWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), true);
	}

	/**
	 * @return list<array{bool}>
	 */
	public static function dataDownloadPermission(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataDownloadPermission')]
	public function testDownloadPermissionPrivateShare(bool $enabled): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(UserShareRecipientType::class, $this->user1->getUID(), null, $this->sharingManager->generateSecret(), $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, $enabled),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_USER)
				->setSharedWith($this->user1->getUID())
				->setSharedWithDisplayName($this->user1->getDisplayName())
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', $enabled))
				->setStatus(IShare::STATUS_ACCEPTED)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setHideDownload(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Accepted,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	#[DataProvider('dataDownloadPermission')]
	public function testEmailShareWithDownloadPermission(bool $enabled): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);
		$secret = $this->sharingManager->generateSecret();

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(EmailShareRecipientType::class, 'test@example.com', null, $secret, $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
					PasswordSharePropertyType::class => new ShareProperty(PasswordSharePropertyType::class, null),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, $enabled),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocMailShare')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_EMAIL)
				->setSharedWith('test@example.com')
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', $enabled))
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setToken($secret)
				->setHideDownload(!$enabled),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Pending,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	#[DataProvider('dataDownloadPermission')]
	public function testLinkShareWithDownloadPermission(bool $enabled): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);
		$secret = $this->sharingManager->generateSecret();

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(TokenShareRecipientType::class, $this->sharingManager->generateSecret(), null, $secret, $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
					PasswordSharePropertyType::class => new ShareProperty(PasswordSharePropertyType::class, null),
					LabelSharePropertyType::class => new ShareProperty(LabelSharePropertyType::class, null),
					NodeGridViewSharePropertyType::class => new ShareProperty(NodeGridViewSharePropertyType::class, 'false'),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, $enabled),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_LINK)
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', $enabled))
				->setStatus(IShare::STATUS_PENDING)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setToken($secret)
				->setHideDownload(!$enabled),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Pending,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses, true);
	}

	public function testEmailShareWithPassword(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);
		$secret = $this->sharingManager->generateSecret();
		$passwordHash = Server::get(IHasher::class)->hash('123');

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(EmailShareRecipientType::class, 'test@example.com', null, $secret, $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
					PasswordSharePropertyType::class => new ShareProperty(PasswordSharePropertyType::class, $passwordHash),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocMailShare')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_EMAIL)
				->setSharedWith('test@example.com')
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setToken($secret)
				->setPasswordHash($passwordHash),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Pending,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses);
	}

	public function testLinkShare(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);
		$secret = $this->sharingManager->generateSecret();

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(TokenShareRecipientType::class, $this->sharingManager->generateSecret(), null, $secret, $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
					PasswordSharePropertyType::class => new ShareProperty(PasswordSharePropertyType::class, null),
					LabelSharePropertyType::class => new ShareProperty(LabelSharePropertyType::class, null),
					NodeGridViewSharePropertyType::class => new ShareProperty(NodeGridViewSharePropertyType::class, 'false'),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_LINK)
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_PENDING)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setToken($secret)
				->setHideDownload(false),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Pending,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses, true);
	}

	public function testLinkShareWithPassword(): void {
		[$id, $created] = $this->generateIdAndCreatedTimestamp();
		$owner = new ShareUser($this->owner->getUID(), null);
		$secret = $this->sharingManager->generateSecret();
		$passwordHash = Server::get(IHasher::class)->hash('123');

		$shares = [
			new Share(
				$id,
				$owner,
				$created,
				ShareState::Active,
				null,
				[new ShareSource(NodeShareSourceType::class, (string)$this->nodeFolder->getId())],
				[new ShareRecipient(TokenShareRecipientType::class, $this->sharingManager->generateSecret(), null, $secret, $owner)],
				[
					ExpirationDateSharePropertyType::class => new ShareProperty(ExpirationDateSharePropertyType::class, null),
					NoteSharePropertyType::class => new ShareProperty(NoteSharePropertyType::class, null),
					PasswordSharePropertyType::class => new ShareProperty(PasswordSharePropertyType::class, $passwordHash),
					LabelSharePropertyType::class => new ShareProperty(LabelSharePropertyType::class, null),
					NodeGridViewSharePropertyType::class => new ShareProperty(NodeGridViewSharePropertyType::class, 'false'),
				],
				[
					NodeReadSharePermissionType::class => new SharePermission(NodeReadSharePermissionType::class, true),
					NodeDownloadSharePermissionType::class => new SharePermission(NodeDownloadSharePermissionType::class, true),
					ReshareSharePermissionType::class => new SharePermission(ReshareSharePermissionType::class, false),
					NodeCreateSharePermissionType::class => new SharePermission(NodeCreateSharePermissionType::class, false),
					NodeUpdateSharePermissionType::class => new SharePermission(NodeUpdateSharePermissionType::class, false),
					NodeDeleteSharePermissionType::class => new SharePermission(NodeDeleteSharePermissionType::class, false),
				],
			),
		];

		$legacyShares = [
			$this->legacySharingManager->newShare()
				->setProviderId('ocinternal')
				->setNode($this->nodeFolder)
				->setShareType(IShare::TYPE_LINK)
				->setSharedBy($this->owner->getUID())
				->setShareOwner($this->owner->getUID())
				->setPermissions(Constants::PERMISSION_READ)
				->setAttributes((new ShareAttributes())->setAttribute('permissions', 'download', true))
				->setStatus(IShare::STATUS_PENDING)
				->setTarget('/' . $this->nodeFolder->getName())
				->setShareTime(DateTime::createFromImmutable($created))
				->setMailSend(false)
				->setToken($secret)
				->setHideDownload(false)
				->setPasswordHash($passwordHash),
		];

		$shareUserStatuses = [
			[
				$this->user1->getUID() => ShareUserStatus::Pending,
			],
		];

		$this->assertSyncingWorks($shares, $legacyShares, $shareUserStatuses, true);
	}
}
