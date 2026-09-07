<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing\SharingLegacySync;

use DateTime;
use DateTimeImmutable;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use OC\Sharing\LegacyMapper;
use OC\Sharing\SharingLegacySync;
use OCA\Circles\Model\Circle;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IUser;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Test\TestCase;
use Test\Traits\GroupTrait;
use Test\Traits\UserTrait;

/**
 * @psalm-suppress UndefinedClass Circles -_-
 */
abstract class AbstractTests extends TestCase {
	use UserTrait;
	use GroupTrait;

	private ISharingRegistry $sharingRegistry;

	protected ISharingManager $sharingManager;

	protected IManager $legacySharingManager;

	protected SharingLegacySync $legacySync;

	protected LegacyMapper $legacyMapper;

	private IDBConnection $dbConnection;

	protected IUser $owner;

	protected IUser $user1;

	protected IUser $user2;

	protected IGroup $group;

	protected ?Circle $circle = null;

	protected Folder $nodeFolder;

	protected File $nodeFile;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->sharingRegistry = Server::get(ISharingRegistry::class);
		$this->sharingManager = Server::get(ISharingManager::class);
		$this->legacySharingManager = Server::get(IManager::class);
		$this->legacySync = Server::get(SharingLegacySync::class);
		$this->legacyMapper = Server::get(LegacyMapper::class);
		$this->dbConnection = Server::get(IDBConnection::class);

		$this->owner = $this->createUser('owner', 'password');
		$this->user1 = $this->createUser('user1', 'password');
		$this->user2 = $this->createUser('user2', 'password');
		$this->group = $this->createGroup('group');

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

		// Can't rollback, because it would rollback the classmap data, but the ClassMapper still has the values cached.
		$this->dbConnection->commit();
		parent::tearDown();
	}

	private function createShare(Share $share): Share {
		$accessContext = new ShareAccessContext($this->owner);

		$s = $this->sharingManager->createShare($accessContext);

		foreach ($share->sources as $source) {
			$s = $this->sharingManager->addShareSource($accessContext, $s, $source);
		}

		foreach ($share->recipients as $recipient) {
			$s = $this->sharingManager->addShareRecipient($accessContext, $s, $recipient);
		}

		foreach ($share->properties as $property) {
			$s = $this->sharingManager->updateShareProperty($accessContext, $s, $property);
		}

		foreach ($share->permissions as $permission) {
			$s = $this->sharingManager->updateSharePermission($accessContext, $s, $permission);
		}

		return $this->sharingManager->updateShareState($accessContext, $s, $share->state);
	}

	/**
	 * @psalm-template T of DateTimeImmutable|DateTime
	 * @param T $created
	 * @return T
	 */
	private function truncateCreationTime(DateTimeImmutable|DateTime $created): DateTimeImmutable|DateTime {
		return $created->setTime(
			(int)$created->format('H'),
			(int)$created->format('i'),
			(int)$created->format('s'),
		);
	}

	private function fixLegacyShare(IShare $legacyShare): IShare {
		// Having the node set seems to trigger and infinite loop in PHPUnit when comparing the objects.
		$node = $legacyShare->getNode();
		$legacyShare->setNodeId($node->getId());
		$legacyShare->setNodeType($node instanceof File ? 'file': 'folder');

		// There might be a closure that we can't reproduce and we want the value the closure returns to be set.
		$legacyShare->getSharedWithDisplayName();

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
				$legacyShare = (clone $legacyShare);
				$legacyShare->setId($actual[$index]->getId());
				$legacyShare->setShareTime($this->truncateCreationTime($legacyShare->getShareTime()));
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
	 */
	private function assertSyncingShareToLegacySharesWorks(array $expectedLegacyShares, Share $actualShare): void {
		$actualShare = $this->createShare($actualShare);

		// TODO: Replace shareTime of $expectedLegacyShares to match $actualShare

		$actualLegacyShares = array_values(iterator_to_array($this->legacySharingManager->getAllShares()));
		$this->assertCount(count($expectedLegacyShares), $actualLegacyShares);
		$this->assertLegacySharesEquals($expectedLegacyShares, $actualLegacyShares);

		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $actualShare);
	}

	/**
	 * @param list<IShare> $actualLegacyShares
	 */
	private function assertSyncingLegacySharesToShareWorks(Share $expectedShare, array $actualLegacyShares): void {
		foreach ($actualLegacyShares as &$actualLegacyShare) {
			$actualLegacyShare = clone $actualLegacyShare;
			$this->invokePrivate($actualLegacyShare, 'providerId', [null]);
			$actualLegacyShare = $this->legacySharingManager->createShare($actualLegacyShare);
		}

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $actualShares);
		$actualShare = $actualShares[0];
		$this->assertSharesEquals(
			new Share(
				$actualShare->id,
				$expectedShare->owner,
				$actualShare->created,
				$actualShare->lastUpdated,
				$expectedShare->state,
				$expectedShare->userStatus,
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

		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $actualShares[0]);
	}

	/**
	 * @param list<IShare> $expectedLegacyShares
	 */
	private function assertSyncingLegacySharesToShareAndBackWorks(array $expectedLegacyShares): void {
		foreach ($expectedLegacyShares as &$expectedLegacyShare) {
			$expectedLegacyShare = clone $expectedLegacyShare;
			$this->invokePrivate($expectedLegacyShare, 'providerId', [null]);
			$expectedLegacyShare = $this->legacySharingManager->createShare($expectedLegacyShare);
		}

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $actualShares);
		$actualShare = $actualShares[0];

		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [true]);
		foreach ($expectedLegacyShares as &$expectedLegacyShare) {
			$this->legacySharingManager->deleteShare($expectedLegacyShare);
			$expectedLegacyShare = clone $expectedLegacyShare;
			$this->invokePrivate($expectedLegacyShare, 'id', [null]);
		}

		$this->invokePrivate($this->legacyMapper, 'deleteLegacyMappings', [$actualShare->id]);
		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [false]);

		// Just update anything to trigger the syncing
		$this->sharingManager->updateShareState(new ShareAccessContext(overrideChecks: true), $actualShare, $actualShare->state);

		$actualLegacyShares = array_values(iterator_to_array($this->legacySharingManager->getAllShares()));
		$this->assertCount(count($expectedLegacyShares), $actualLegacyShares);
		$this->assertLegacySharesEquals(
			$expectedLegacyShares,
			array_map(
				// createShare implicitly sets the $originalTarget through OCA\Files_Sharing\Listener\SharesUpdatedListener
				static fn (IShare $legacyShare): IShare => $legacyShare->setTarget($legacyShare->getTarget()),
				$actualLegacyShares,
			),
		);
	}

	private function assertSyncingShareToLegacySharesAndBackWorks(Share $expectedShare): void {
		$expectedShare = $this->createShare($expectedShare);

		$actualLegacyShares = array_values(iterator_to_array($this->legacySharingManager->getAllShares()));
		$this->assertCount(count($expectedShare->sources) * count($expectedShare->recipients), $actualLegacyShares);

		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [true]);
		$this->sharingManager->deleteShare(new ShareAccessContext(overrideChecks: true), $expectedShare);
		$this->invokePrivate($this->legacyMapper, 'deleteLegacyMappings', [$expectedShare->id]);
		$this->invokePrivate($this->legacySync, 'ignoreAllEvents', [false]);

		// Just update anything to trigger the syncing
		$this->legacySharingManager->updateShare($actualLegacyShares[0]);

		$actualShares = $this->sharingManager->getShares(new ShareAccessContext(overrideChecks: true), null, null, null, null, null, null);
		$this->assertCount(1, $actualShares);
		$actualShare = $actualShares[0];
		$this->assertSharesEquals(
			new Share(
				$actualShare->id,
				$expectedShare->owner,
				$this->truncateCreationTime($expectedShare->created),
				$actualShare->lastUpdated,
				$expectedShare->state,
				$expectedShare->userStatus,
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
	 */
	protected function assertSyncingWorks(Share $share, array $legacyShares): void {
		$this->assertSyncingShareToLegacySharesWorks($legacyShares, $share);
		$this->assertSyncingLegacySharesToShareWorks($share, $legacyShares);
		$this->assertSyncingShareToLegacySharesAndBackWorks($share);
		$this->assertSyncingLegacySharesToShareAndBackWorks($legacyShares);
	}
}
