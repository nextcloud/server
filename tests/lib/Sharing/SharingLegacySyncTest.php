<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing;

use DateTime;
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
use OC\Core\Sharing\Property\NoteSharePropertyType;
use OC\Core\Sharing\Recipient\GroupShareRecipientType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\Share20\DefaultShareProvider;
use OC\Share20\ShareAttributes;
use OC\Sharing\LegacyMapper;
use OC\Sharing\SharingLegacySync;
use OCA\Circles\Model\Circle;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Files\Sharing\SourceNodeTargetManager;
use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IUser;
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

	protected ISharingManager $sharingManager;

	protected ISharingBackend $sharingBackend;

	protected IManager $legacySharingManager;

	protected SharingLegacySync $legacySync;

	protected LegacyMapper $legacyMapper;

	private IDBConnection $dbConnection;

	private ISnowflakeGenerator $snowflakeGenerator;

	private ISnowflakeDecoder $snowflakeDecoder;

	private SourceNodeTargetManager $sourceNodeTargetManager;

	protected IUser $owner;

	protected IUser $user1;

	protected IGroup $group;

	protected ?Circle $circle = null;

	protected Folder $nodeFolder;

	protected File $nodeFile;

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
		$this->group = $this->createGroup('group');
		$this->groupBackend->addToGroup($this->user1->getUID(), $this->group->getGID());

		if (class_exists(CirclesManager::class)) {
			$circlesManager = Server::get(CirclesManager::class);
			$circlesManager->startSession($circlesManager->getLocalFederatedUser($this->owner->getUID()));
			/** @psalm-suppress MixedAssignment */
			$this->circle = $circlesManager->createCircle('circle');
		}

		$ownerFolder = Server::get(IRootFolder::class)->getUserFolder($this->owner->getUID());
		$this->nodeFolder = $ownerFolder->newFolder('foo');
		$this->nodeFile = $ownerFolder->newFile('foo.txt');

		$this->dbConnection->beginTransaction();
	}

	#[\Override]
	public function tearDown(): void {
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

		$this->sharingRegistry->clear();

		// Can't rollback, because it would rollback the classmap data, but the ClassMapper still has the values cached.
		$this->dbConnection->commit();
		parent::tearDown();
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
	 * @param array<string, ShareUserStatus> $shareUserStatuses
	 */
	private function assertSyncingShareToLegacySharesWorks(array $expectedLegacyShares, Share $actualShare, array $shareUserStatuses): void {
		$this->createShare($actualShare, $shareUserStatuses);

		$actualLegacyShares = $this->getAllLegacyShares();
		$this->assertCount(count($expectedLegacyShares), $actualLegacyShares);
		$this->assertLegacySharesEquals($expectedLegacyShares, $actualLegacyShares);

		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $actualShare);
	}

	/**
	 * @param list<IShare> $actualLegacyShares
	 * @param array<string, ShareUserStatus> $shareUserStatuses
	 */
	private function assertSyncingLegacySharesToShareWorks(Share $expectedShare, array $actualLegacyShares, array $shareUserStatuses): void {
		$this->createLegacyShares($actualLegacyShares);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(currentUser: $this->user1, overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $actualShares);
		$actualShare = $actualShares[0];
		$this->assertSharesEquals(
			new Share(
				$actualShare->id,
				$expectedShare->owner,
				$actualShare->lastUpdated,
				$expectedShare->state,
				$shareUserStatuses[$this->user1->getUID()] ?? null,
				$expectedShare->sources,
				array_map(
					fn (int $index, ShareRecipient $recipient): ShareRecipient => new ShareRecipient(
						$recipient->class,
						$recipient->value,
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

		// TODO: Validate targets

		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $actualShares[0]);
	}

	/**
	 * @param list<IShare> $expectedLegacyShares
	 */
	private function assertSyncingLegacySharesToShareAndBackWorks(array $expectedLegacyShares): void {
		$filteredExpectedLegacyShares = $this->createLegacyShares($expectedLegacyShares);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(currentUser: $this->user1, overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $actualShares);
		$actualShare = $actualShares[0];

		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [true]);
		foreach ($filteredExpectedLegacyShares as &$expectedLegacyShare) {
			$this->legacySharingManager->deleteShare($expectedLegacyShare);
			$this->invokePrivate($expectedLegacyShare, 'id', [null]);
		}

		$this->invokePrivate($this->legacyMapper, 'deleteLegacyMappings', [$actualShare->id]);
		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [false]);

		// Just update anything to trigger the syncing
		$this->sharingManager->updateShareState(new ShareAccessContext(overrideChecks: true), $actualShare, $actualShare->state);

		$actualLegacyShares = $this->getAllLegacyShares();
		$this->assertCount(count($expectedLegacyShares), $actualLegacyShares);
		$this->assertLegacySharesEquals($expectedLegacyShares, $actualLegacyShares);
	}

	/**
	 * @param array<string, ShareUserStatus> $shareUserStatuses
	 */
	private function assertSyncingShareToLegacySharesAndBackWorks(Share $expectedShare, array $shareUserStatuses): void {
		$this->createShare($expectedShare, $shareUserStatuses);

		$actualLegacyShares = $this->getAllLegacyShares();
		$this->assertCount(count($expectedShare->sources) * count($expectedShare->recipients), array_filter($actualLegacyShares, static fn (IShare $legacyShare): bool => $legacyShare->getShareType() !== IShare::TYPE_USERGROUP));

		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [true]);
		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $expectedShare);
		$this->invokePrivate($this->legacyMapper, 'deleteLegacyMappings', [$expectedShare->id]);
		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [false]);

		// Just update anything to trigger the syncing
		$this->legacySharingManager->updateShare($actualLegacyShares[0]);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(currentUser: $this->user1, overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $actualShares);
		$actualShare = $actualShares[0];
		$this->assertSharesEquals(
			new Share(
				$actualShare->id,
				$expectedShare->owner,
				$actualShare->lastUpdated,
				$expectedShare->state,
				$shareUserStatuses[$this->user1->getUID()] ?? null,
				$expectedShare->sources,
				array_map(
					fn (int $index, ShareRecipient $recipient): ShareRecipient => new ShareRecipient(
						$recipient->class,
						$recipient->value,
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

	/**
	 * @param list<IShare> $legacyShares
	 * @param array<string, ShareUserStatus> $shareUserStatuses
	 */
	protected function assertSyncingWorks(Share $share, array $legacyShares, array $shareUserStatuses = []): void {
		$this->assertSyncingShareToLegacySharesWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), $share, $shareUserStatuses);
		$this->assertSyncingLegacySharesToShareWorks($share, array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares), $shareUserStatuses);
		$this->assertSyncingShareToLegacySharesAndBackWorks($share, $shareUserStatuses);
		$this->assertSyncingLegacySharesToShareAndBackWorks(array_map(static fn (IShare $legacyShare): IShare => clone $legacyShare, $legacyShares));
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
		$id = $this->snowflakeGenerator->nextId();
		$created = $this->snowflakeDecoder->decode($id)->getCreatedAt();
		$owner = new ShareUser($this->owner->getUID(), null);

		$share = new Share(
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
		);

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

		$this->assertSyncingWorks($share, $legacyShares, [$this->user1->getUID() => $shareUserStatus]);
	}

	public function testDefaultTarget(): void {
		$id = $this->snowflakeGenerator->nextId();
		$created = $this->snowflakeDecoder->decode($id)->getCreatedAt();
		$owner = new ShareUser($this->owner->getUID(), null);

		$share = new Share(
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
		);

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

		$this->assertSyncingWorks($share, $legacyShares, [$this->user1->getUID() => ShareUserStatus::Accepted]);
	}

	public function testNonDefaultTarget(): void {
		$id = $this->snowflakeGenerator->nextId();
		$created = $this->snowflakeDecoder->decode($id)->getCreatedAt();
		$owner = new ShareUser($this->owner->getUID(), null);

		$share = new Share(
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
		);

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

		$this->assertSyncingWorks($share, $legacyShares, [$this->user1->getUID() => ShareUserStatus::Accepted]);
	}

	public function testNonDefaultTargetGroup(): void {
		$id = $this->snowflakeGenerator->nextId();
		$created = $this->snowflakeDecoder->decode($id)->getCreatedAt();
		$owner = new ShareUser($this->owner->getUID(), null);

		$share = new Share(
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
		);

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

		$this->assertSyncingWorks($share, $legacyShares, [$this->user1->getUID() => ShareUserStatus::Accepted]);
	}

	/**
	 * @param IShare::STATUS_* $legacyStatus
	 */
	#[DataProvider('dataShareUserStatus')]
	public function testGroupUserStatus(ShareUserStatus $shareUserStatus, int $legacyStatus): void {
		$id = $this->snowflakeGenerator->nextId();
		$created = $this->snowflakeDecoder->decode($id)->getCreatedAt();
		$owner = new ShareUser($this->owner->getUID(), null);

		$share = new Share(
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
		);

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

		$this->assertSyncingWorks($share, $legacyShares, [$this->user1->getUID() => $shareUserStatus]);
	}
}
