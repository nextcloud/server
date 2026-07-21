<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\ISharingBackend;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Property\ISharePropertyTypeModifyValue;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;
use OCP\Snowflake\ISnowflakeGenerator;

// TODO: Add mapping table for class names in sources, recipients, permissions and properties

/**
 * @psalm-import-type SharingShare from Share
 */
final readonly class SharingBackend implements ISharingBackend {
	private IL10N $l10n;

	public function __construct(
		IFactory $factory,
		private IDBConnection $connection,
		private IUserManager $userManager,
		private ISnowflakeGenerator $snowflakeGenerator,
		private IAppConfig $appConfig,
		private ISharingRegistry $registry,
		private ISharingManager $manager,
	) {
		$this->l10n = $factory->get('sharing');
	}

	#[\Override]
	public function createShare(IUser $owner): string {
		$id = $this->snowflakeGenerator->nextId();
		$lastUpdated = $this->manager->generateTimestamp();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('sharing_share')
			->values([
				'id' => $qb->createNamedParameter($id),
				'owner_user_id' => $qb->createNamedParameter($owner->getUID()),
				'last_updated' => $qb->createNamedParameter($lastUpdated),
				'state' => $qb->createNamedParameter(ShareState::Draft->value),
			])
			->executeStatement();

		return $id;
	}

	#[\Override]
	public function onOwnerDeleted(IUser $owner): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share')
			->where($qb->expr()->eq('owner_user_id', $qb->createNamedParameter($owner->getUID())))
			->andWhere($qb->expr()->isNull('owner_instance'))
			->executeStatement();
	}

	#[\Override]
	public function updateShareState(string $id, ShareState $state): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_share')
			->set('state', $qb->createNamedParameter($state->value))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}
	}

	#[\Override]
	public function addShareSource(string $id, ShareSource $source): void {
		try {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->insert('sharing_share_sources')
				->values([
					'share_id' => $qb->createNamedParameter($id),
					'source_class' => $qb->createNamedParameter($source->class),
					'source_value' => $qb->createNamedParameter($source->value),
				])
				->executeStatement();
		} catch (Exception $exception) {
			if ($exception instanceof \OCP\DB\Exception && $exception->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new ShareInvalidException('Tried to add share source that already exists: ' . $source->class . ' ' . $source->value, $this->l10n->t('The share already contains the source.'), previous: $exception);
			}

			throw $exception;
		}
	}

	#[\Override]
	public function removeShareSource(string $id, ShareSource $source): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->delete('sharing_share_sources')
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('source_class', $qb->createNamedParameter($source->class)))
			->andWhere($qb->expr()->eq('source_value', $qb->createNamedParameter($source->value)))
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}
	}

	#[\Override]
	public function onSourceDeleted(ShareSource $source): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->selectDistinct('share_id')
			->from('sharing_share_sources')
			->where($qb->expr()->eq('source_class', $qb->createNamedParameter($source->class)))
			->andWhere($qb->expr()->eq('source_value', $qb->createNamedParameter($source->value)))
			->executeQuery();

		/** @var list<string|int> $ids */
		$ids = $result->fetchFirstColumn();
		if ($ids === []) {
			return [];
		}

		$ids = array_map(static fn (string|int $id): string => (string)$id, $ids);

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_sources')
			->where($qb->expr()->eq('source_class', $qb->createNamedParameter($source->class)))
			->andWhere($qb->expr()->eq('source_value', $qb->createNamedParameter($source->value)))
			->executeStatement();

		return $ids;
	}

	#[\Override]
	public function addShareRecipient(string $id, IUser $initiator, ShareRecipient $recipient): void {
		try {
			$qb = $this->connection->getQueryBuilder();

			$values = [
				'share_id' => $qb->createNamedParameter($id),
				'recipient_class' => $qb->createNamedParameter($recipient->class),
				'recipient_value' => $qb->createNamedParameter($recipient->value),
				'recipient_instance' => $qb->createNamedParameter($recipient->instance),
				'recipient_secret' => $qb->createNamedParameter($this->manager->generateSecret()),
				'initiator_user_id' => $qb->createNamedParameter($initiator->getUID(), IQueryBuilder::PARAM_STR),
			];

			$qb
				->insert('sharing_share_recipients')
				->values($values)
				->executeStatement();
		} catch (Exception $exception) {
			if ($exception instanceof \OCP\DB\Exception && $exception->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new ShareInvalidException('Tried to add share recipient that already exists: ' . $recipient->class . ' ' . $recipient->value . ' ' . ($recipient->instance ?? 'local'), $this->l10n->t('The share already contains the recipient.'), previous: $exception);
			}

			throw $exception;
		}
	}

	#[\Override]
	public function removeShareRecipient(string $id, ShareRecipient $recipient): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->delete('sharing_share_recipients')
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('recipient_class', $qb->createNamedParameter($recipient->class)))
			->andWhere($qb->expr()->eq('recipient_value', $qb->createNamedParameter($recipient->value)))
			->andWhere(
				$recipient->instance === null
					? $qb->expr()->isNull('recipient_instance')
					: $qb->expr()->eq('recipient_instance', $qb->createNamedParameter($recipient->instance))
			)
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}
	}

	#[\Override]
	public function onRecipientDeleted(ShareRecipient $recipient): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->selectDistinct('share_id')
			->from('sharing_share_recipients')
			->where($qb->expr()->eq('recipient_class', $qb->createNamedParameter($recipient->class)))
			->andWhere($qb->expr()->eq('recipient_value', $qb->createNamedParameter($recipient->value)))
			->andWhere(
				$recipient->instance === null
					? $qb->expr()->isNull('recipient_instance')
					: $qb->expr()->eq('recipient_instance', $qb->createNamedParameter($recipient->instance))
			)
			->executeQuery();

		/** @var list<string|int> $ids */
		$ids = $result->fetchFirstColumn();
		if ($ids === []) {
			return [];
		}

		$ids = array_map(static fn (string|int $id): string => (string)$id, $ids);

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_recipients')
			->where($qb->expr()->eq('recipient_class', $qb->createNamedParameter($recipient->class)))
			->andWhere($qb->expr()->eq('recipient_value', $qb->createNamedParameter($recipient->value)))
			->andWhere(
				$recipient->instance === null
					? $qb->expr()->isNull('recipient_instance')
					: $qb->expr()->eq('recipient_instance', $qb->createNamedParameter($recipient->instance))
			)
			->executeStatement();

		return $ids;
	}

	#[\Override]
	public function onInitiatorDeleted(IUser $initiator): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->selectDistinct('share_id')
			->from('sharing_share_recipients')
			->andWhere($qb->expr()->isNull('initiator_instance'))
			->andWhere($qb->expr()->eq('initiator_user_id', $qb->createNamedParameter($initiator->getUID())))
			->executeQuery();

		/** @var list<string|int> $ids */
		$ids = $result->fetchFirstColumn();
		if ($ids === []) {
			return [];
		}

		$ids = array_map(static fn (string|int $id): string => (string)$id, $ids);

		foreach ($ids as $id) {
			$owner = $this->getShareOwner($id);

			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share_recipients')
				->set('initiator_user_id', $qb->createNamedParameter($owner->userId))
				->set('initiator_instance', $qb->createNamedParameter($owner->instance))
				->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->isNull('initiator_instance'))
				->andWhere($qb->expr()->eq('initiator_user_id', $qb->createNamedParameter($initiator->getUID())))
				->executeStatement();
		}

		return $ids;
	}

	#[\Override]
	public function updateShareRecipientSecret(string $id, ShareRecipient $recipient, string $secret): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_share_recipients')
			->set('recipient_secret', $qb->createNamedParameter($secret))
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('recipient_class', $qb->createNamedParameter($recipient->class)))
			->andWhere($qb->expr()->eq('recipient_value', $qb->createNamedParameter($recipient->value)))
			->andWhere(
				$recipient->instance === null
					? $qb->expr()->isNull('recipient_instance')
					: $qb->expr()->eq('recipient_instance', $qb->createNamedParameter($recipient->instance))
			)
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}
	}

	#[\Override]
	public function updateShareProperty(string $id, ShareProperty $property): void {
		$value = $property->value;

		$propertyType = $this->registry->getPropertyTypes()[$property->class];

		if ($propertyType instanceof ISharePropertyTypeModifyValue) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select('sp.property_value')
				->from('sharing_share_properties', 'sp')
				->where($qb->expr()->eq('sp.share_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->eq('sp.property_class', $qb->createNamedParameter($property->class)));

			/** @var string|false $oldValue */
			$oldValue = $qb->executeQuery()->fetchOne();
			if ($oldValue === false) {
				$oldValue = null;
			}

			$value = $propertyType->modifyValueOnSave($oldValue, $property->value);
		}

		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_share_properties')
			->set('property_value', $qb->createNamedParameter($value))
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('property_class', $qb->createNamedParameter($property->class)))
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}
	}

	#[\Override]
	public function updateSharePermission(string $id, SharePermission $permission): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_share_permissions')
			->set('permission_enabled', $qb->createNamedParameter($permission->enabled, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('permission_class', $qb->createNamedParameter($permission->class)))
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}
	}

	#[\Override]
	public function selectSharePermissionPreset(string $id, string $permissionPresetClass): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->update('sharing_share_permissions')
			->set('permission_enabled', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->executeStatement();

		$permissionPresetCompatiblePermissionTypeClasses = $this->registry->getPermissionPresetCompatiblePermissionTypeClasses()[$permissionPresetClass];
		foreach (array_chunk($permissionPresetCompatiblePermissionTypeClasses, 1000) as $chunk) {
			// Some permissions might not be compatible with the share, just ignore it and update the ones that are present.
			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share_permissions')
				->set('permission_enabled', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
				->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->in('permission_class', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
				->executeStatement();
		}

		// We don't check if at least one permission is enabled and otherwise change the share state to draft, because we know every preset has at least one permission belonging to it.
	}

	#[\Override]
	public function deleteShare(string $id): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->delete('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();
		if ($rowCount === 0) {
			throw new ShareNotFoundException();
		}

		// The other tables are cleared by their foreign key constraints and on delete cascade.
	}

	#[\Override]
	public function getShare(ShareAccessContext $accessContext, string $id): Share {
		$shares = $this->list($accessContext, $id, null, null, null, null);
		if (count($shares) !== 1) {
			throw new ShareNotFoundException();
		}

		return $shares[0];
	}

	#[\Override]
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array {
		return $this->list($accessContext, null, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
	}

	#[\Override]
	public function hasShare(string $id): bool {
		$qb = $this->connection->getQueryBuilder();

		$result = $qb
			->select('id')
			->from('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery();

		return $result->fetchOne() !== false;
	}

	#[\Override]
	public function getShareOwner(string $id): ShareUser {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->select('owner_user_id', 'owner_instance')
			->from('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		$row = $qb->executeQuery()->fetchAssociative();
		if ($row === false) {
			throw new ShareNotFoundException();
		}

		/** @var non-empty-string $userId */
		$userId = $row['owner_user_id'];
		/** @var non-empty-string $instance */
		$instance = $row['owner_instance'];

		return new ShareUser(
			$userId,
			$instance,
		);
	}

	/**
	 * @param non-empty-list<string> $ids
	 */
	#[\Override]
	public function setLastUpdated(array $ids, int $lastUpdated): void {
		foreach (array_chunk($ids, 1000) as $chunk) {
			$qb = $this->connection->getQueryBuilder();

			$rowCount = $qb
				->update('sharing_share')
				->set('last_updated', $qb->createNamedParameter($lastUpdated, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();
			if ($rowCount !== count($chunk)) {
				throw new ShareNotFoundException();
			}
		}
	}

	private function hideDisabledUserShares(): bool {
		return $this->appConfig->getValueString('files_sharing', 'hide_disabled_user_shares', 'yes') === 'yes';
	}

	// TODO: Split up the method

	/**
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @return list<Share>
	 */
	private function list(ShareAccessContext $accessContext, ?string $filterShareID, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array {
		/** @var array<class-string<IShareRecipientType>, list<string>> $recipientTypeValues */
		$recipientTypeValues = [];

		/** @var list<IQueryBuilder> $queries */
		$queries = [];
		if ($accessContext->overrideChecks) {
			$queries[] = $this->connection->getQueryBuilder();
		} else {
			if ($accessContext->currentUser instanceof IUser) {
				$qb = $this->connection->getQueryBuilder();
				$qb->where($qb->expr()->eq('s.owner_user_id', $qb->createNamedParameter($accessContext->currentUser->getUID())));
				$queries[] = $qb;
			}

			foreach ($this->registry->getRecipientTypes() as $recipientType) {
				$recipientValues = $recipientType->getRecipients($accessContext->currentUser, $accessContext->arguments[$recipientType::class] ?? null);
				if ($recipientValues !== []) {
					$recipientTypeValues[$recipientType::class] = $recipientValues;
				}
			}

			// Do not add a query if no recipients matched, otherwise all shares will be returned.
			if ($recipientTypeValues !== []) {
				$qb = $this->connection->getQueryBuilder();
				$qb->innerJoin('s', 'sharing_share_recipients', 'sr', $qb->expr()->andX(
					$qb->expr()->eq('s.state', $qb->createNamedParameter(ShareState::Active->value)),
					$qb->expr()->eq('s.id', 'sr.share_id'),
				));

				foreach ($recipientTypeValues as $recipientTypeClass => $recipientValues) {
					$qb->orWhere($qb->expr()->andX(
						$qb->expr()->eq('sr.recipient_class', $qb->createNamedParameter($recipientTypeClass)),
						// TODO: Add chunking
						$qb->expr()->in('sr.recipient_value', $qb->createNamedParameter($recipientValues, IQueryBuilder::PARAM_STR_ARRAY)),
						$qb->expr()->isNull('sr.recipient_instance'),
					));
				}

				$queries[] = $qb;
			}

			if ($filterShareID !== null && $accessContext->secret !== null) {
				$qb = $this->connection->getQueryBuilder();
				$qb->innerJoin('s', 'sharing_share_recipients', 'sr', $qb->expr()->andX(
					$qb->expr()->eq('s.state', $qb->createNamedParameter(ShareState::Active->value)),
					$qb->expr()->eq('s.id', 'sr.share_id'),
					$qb->expr()->eq('sr.recipient_secret', $qb->createNamedParameter($accessContext->secret)),
				));

				$queries[] = $qb;
			}
		}

		// The key type is array-key, because PHP will automatically cast the value. We can't type it as integer though, because we need to also support 32 bit systems and there the autocasting doesn't happen, if the value is too large.
		/** @var array<array-key, array{id: non-empty-string, owner: ShareUser, last_updated: non-negative-int, state: ShareState, sources: list<ShareSource>, recipients: list<ShareRecipient>, properties: array<class-string<ISharePropertyType>, ShareProperty>, permissions: array<class-string<ISharePermissionType>, SharePermission>}> $shares */
		$shares = [];
		foreach ($queries as $qb) {
			$qb
				->select(
					's.id',
					's.owner_user_id',
					's.owner_instance',
					's.last_updated',
					's.state',
				)
				->from('sharing_share', 's')
				->orderBy('s.id', 'ASC');

			if ($filterShareID !== null) {
				$qb->andWhere($qb->expr()->eq('s.id', $qb->createNamedParameter($filterShareID)));
			}

			if ($filterSourceTypeClass !== null) {
				$sourceTypeFilters = [
					$qb->expr()->eq('s.id', 'ss.share_id'),
					$qb->expr()->eq('ss.source_class', $qb->createNamedParameter($filterSourceTypeClass)),
				];

				if ($filterSourceTypeValue !== null) {
					$sourceTypeFilters[] = $qb->expr()->eq('ss.source_value', $qb->createNamedParameter($filterSourceTypeValue));
				}

				$qb->innerJoin('s', 'sharing_share_sources', 'ss', $qb->expr()->andX(...$sourceTypeFilters));
			}

			if ($lastShareID !== null) {
				$qb->andWhere($qb->expr()->gt('s.id', $qb->createNamedParameter($lastShareID)));
			}

			if ($limit !== null) {
				$qb->setMaxResults($limit);
			}

			$result = $qb->executeQuery();
			$rows = $result->fetchAll();
			foreach ($rows as $row) {
				/** @var non-empty-string $id */
				$id = (string)$row['id'];
				/** @var non-empty-string $ownerUserId */
				$ownerUserId = $row['owner_user_id'];
				/** @var ?non-empty-string $ownerInstance */
				$ownerInstance = $row['owner_instance'];

				/** @psalm-suppress PossiblyNullReference The share is automatically deleted, when the owner is deleted. */
				if ($ownerInstance === null && !$accessContext->overrideChecks && $this->hideDisabledUserShares() && !$this->userManager->get($ownerUserId)->isEnabled()) {
					continue;
				}

				/** @var non-negative-int $lastUpdated */
				$lastUpdated = (int)$row['last_updated'];
				/** @var string $state */
				$state = $row['state'];
				$shares[$id] ??= [
					'id' => $id,
					'owner' => new ShareUser($ownerUserId, $ownerInstance),
					'last_updated' => $lastUpdated,
					'state' => ShareState::from($state),
					'sources' => [],
					'recipients' => [],
					'properties' => [],
					'permissions' => [],
				];
			}
		}

		if ($shares === []) {
			return [];
		}

		// The queries are limited already, but could return more results in total, so discard them here.
		if ($limit !== null) {
			$shares = array_slice($shares, 0, $limit, true);
		}

		/** @var list<list<array-key>> $chunks */
		$chunks = array_chunk(array_keys($shares), 1000);

		$registrySourceTypes = $this->registry->getSourceTypes();
		/** @var array<int, array<class-string<IShareSourceType>, bool>> $shareSourceTypeClasses */
		$shareSourceTypeClasses = [];
		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'ss.share_id',
					'ss.source_class',
					'ss.source_value',
				)
				->from('sharing_share_sources', 'ss')
				->where($qb->expr()->in('ss.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				/** @var class-string<IShareSourceType> $typeClass */
				$typeClass = $row['source_class'];
				if (!isset($registrySourceTypes[$typeClass])) {
					// Skip sources that are currently not compatible, but don't remove them.
					continue;
				}

				/** @var non-empty-string $value */
				$value = $row['source_value'];
				/** @var non-empty-string $id */
				$id = (string)$row['share_id'];
				$shares[$id]['sources'][] = new ShareSource(
					$typeClass,
					$value,
				);

				$shareSourceTypeClasses[$id] ??= [];
				$shareSourceTypeClasses[$id][$typeClass] = true;
			}
		}

		$registryRecipientTypes = $this->registry->getRecipientTypes();
		/** @var array<int, array<class-string<IShareRecipientType>, bool>> $shareRecipientTypeClasses */
		$shareRecipientTypeClasses = [];
		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'sr.share_id',
					'sr.recipient_class',
					'sr.recipient_value',
					'sr.recipient_instance',
					'sr.recipient_secret',
					'sr.initiator_user_id',
					'sr.initiator_instance',
				)
				->from('sharing_share_recipients', 'sr')
				->where($qb->expr()->in('sr.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			foreach ($qb->executeQuery()->fetchAll() as $row) {
				/** @var class-string<IShareRecipientType> $typeClass */
				$typeClass = $row['recipient_class'];
				if (!isset($registryRecipientTypes[$typeClass])) {
					// Skip recipients that are currently not compatible, but don't remove them.
					continue;
				}

				/** @var non-empty-string $id */
				$id = (string)$row['share_id'];
				/** @var non-empty-string $initiatorUserId */
				$initiatorUserId = $row['initiator_user_id'];
				/** @var ?non-empty-string $initiatorInstance */
				$initiatorInstance = $row['initiator_instance'];

				/** @psalm-suppress PossiblyNullReference The initiator is automatically promoted to the owner, when the initiator is deleted. */
				if ($initiatorInstance === null && !$accessContext->overrideChecks && !$shares[$id]['owner']->isCurrentUser($accessContext) && $this->hideDisabledUserShares() && !$this->userManager->get($initiatorUserId)->isEnabled()) {
					continue;
				}

				/** @var non-empty-string $value */
				$value = $row['recipient_value'];
				/** @var ?non-empty-string $instance */
				$instance = $row['recipient_instance'];
				// The secret is only removed in the next step, because we still need it to check if the current access context still has access to the share, after the recipients of disabled initiators have been skipped.
				/** @var non-empty-string $secret */
				$secret = $row['recipient_secret'];

				$shares[$id]['recipients'][] = new ShareRecipient(
					$typeClass,
					$value,
					$instance,
					$secret,
					new ShareUser(
						$initiatorUserId,
						$initiatorInstance,
					),
				);

				$shareRecipientTypeClasses[$id] ??= [];
				$shareRecipientTypeClasses[$id][$typeClass] = true;
			}
		}

		// Some recipients might have been removed if the initiator was disabled, so check again if this share can be accessed by the current user as a recipient.
		// This logic is a bit duplicated with the SQL logic that selects shares based on the secret and the recipient type values, but neither can be removed.
		if (!$accessContext->overrideChecks) {
			foreach ($shares as $id => &$share) {
				if ($share['owner']->isCurrentUser($accessContext)) {
					continue;
				}

				$isAnyMatchingRecipient = false;
				foreach ($share['recipients'] as &$recipient) {
					$isMatchingRecipient = false;
					if (($accessContext->secret !== null && $recipient->secret === $accessContext->secret)
						|| ($recipient->initiator !== null && $recipient->initiator->isCurrentUser($accessContext))) {
						$isMatchingRecipient = true;
					}

					foreach ($recipientTypeValues as $recipientTypeClass => $recipientValues) {
						if ($recipient->instance === null && $recipient->class === $recipientTypeClass && in_array($recipient->value, $recipientValues, true)) {
							$isMatchingRecipient = true;
							break;
						}
					}

					if ($isMatchingRecipient) {
						$isAnyMatchingRecipient = true;
					} else {
						// Remove the secret if the recipient didn't match
						$recipient = new ShareRecipient(
							$recipient->class,
							$recipient->value,
							$recipient->instance,
							null,
							$recipient->initiator,
						);
					}
				}

				unset($recipient);

				if (!$isAnyMatchingRecipient) {
					unset($shares[$id]);
				}
			}

			unset($share);
		}

		if ($shares === []) {
			return [];
		}

		/** @var list<list<array-key>> $chunks */
		$chunks = array_chunk(array_keys($shares), 1000);

		$registryPropertyTypes = $this->registry->getPropertyTypes();
		$registryPropertyTypeCompatibleSourceTypeClasses = $this->registry->getPropertyTypeCompatibleSourceTypeClasses();
		$registryPropertyTypeCompatibleRecipientTypeClasses = $this->registry->getPropertyTypeCompatibleRecipientTypes();

		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'sp.share_id',
					'sp.property_class',
					'sp.property_value',
				)
				->from('sharing_share_properties', 'sp')
				->where($qb->expr()->in('sp.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				/** @var non-empty-string $id */
				$id = (string)$row['share_id'];
				if (!isset($shareSourceTypeClasses[$id], $shareRecipientTypeClasses[$id])) {
					continue;
				}

				/** @var class-string<ISharePropertyType> $propertyTypeClass */
				$propertyTypeClass = $row['property_class'];
				if (!isset($registryPropertyTypeCompatibleSourceTypeClasses[$propertyTypeClass], $registryPropertyTypeCompatibleRecipientTypeClasses[$propertyTypeClass])) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				if (array_intersect($registryPropertyTypeCompatibleSourceTypeClasses[$propertyTypeClass], array_keys($shareSourceTypeClasses[$id])) === []) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				if (array_intersect($registryPropertyTypeCompatibleRecipientTypeClasses[$propertyTypeClass], array_keys($shareRecipientTypeClasses[$id])) === []) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				/** @var ?string $value */
				$value = $row['property_value'];

				$propertyType = $registryPropertyTypes[$propertyTypeClass];
				if ($propertyType instanceof ISharePropertyTypeModifyValue) {
					$value = $propertyType->modifyValueOnLoad($value);
				}

				$shares[$id]['properties'][$propertyTypeClass] = new ShareProperty($propertyTypeClass, $value);
			}
		}

		foreach (array_keys($shares) as $id) {
			foreach ($registryPropertyTypes as $propertyTypeClass => $propertyType) {
				if (
					!isset($shares[$id]['properties'][$propertyTypeClass])
					&& isset($shareSourceTypeClasses[$id], $shareRecipientTypeClasses[$id])
					&& array_intersect($registryPropertyTypeCompatibleSourceTypeClasses[$propertyTypeClass], array_keys($shareSourceTypeClasses[$id])) !== []
					&& array_intersect($registryPropertyTypeCompatibleRecipientTypeClasses[$propertyTypeClass], array_keys($shareRecipientTypeClasses[$id])) !== []) {
					$value = $propertyType->getDefaultValue();

					$timestamp = $this->manager->generateTimestamp();
					$this->setLastUpdated([(string)$id], $timestamp);

					$qb = $this->connection->getQueryBuilder();
					$qb
						->insert('sharing_share_properties')
						->values([
							'share_id' => $qb->createNamedParameter($id),
							'property_class' => $qb->createNamedParameter($propertyTypeClass),
							'property_value' => $qb->createNamedParameter($value),
						])
						->executeStatement();

					$shares[$id]['properties'][$propertyTypeClass] = new ShareProperty($propertyTypeClass, $value);
					$shares[$id]['last_updated'] = $timestamp;
				}
			}
		}

		$registrySourceTypePermissionTypeClasses = $this->registry->getSourceTypePermissionTypeClasses();
		$registryGenericPermissionTypeClasses = $this->registry->getGenericPermissionTypeClasses();

		/** @var array<int, array<class-string<ISharePermissionType>, bool>> $shareCompatiblePermissionTypeClasses */
		$shareCompatiblePermissionTypeClasses = [];
		foreach (array_keys($shares) as $id) {
			$shareCompatiblePermissionTypeClasses[$id] = [];
			foreach ($registryGenericPermissionTypeClasses as $permissionTypeClass) {
				$shareCompatiblePermissionTypeClasses[$id][$permissionTypeClass] = true;
			}

			if (isset($shareSourceTypeClasses[$id])) {
				foreach (array_keys($shareSourceTypeClasses[$id]) as $shareSourceTypeClass) {
					if (isset($registrySourceTypePermissionTypeClasses[$shareSourceTypeClass])) {
						foreach ($registrySourceTypePermissionTypeClasses[$shareSourceTypeClass] as $permissionTypeClass) {
							$shareCompatiblePermissionTypeClasses[$id][$permissionTypeClass] = true;
						}
					}
				}
			}
		}

		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'sp.share_id',
					'sp.permission_class',
					'sp.permission_enabled',
				)
				->from('sharing_share_permissions', 'sp')
				->where($qb->expr()->in('sp.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				/** @var non-empty-string $id */
				$id = (string)$row['share_id'];

				/** @var class-string<ISharePermissionType> $permissionTypeClass */
				$permissionTypeClass = $row['permission_class'];
				if (!isset($shareCompatiblePermissionTypeClasses[$id][$permissionTypeClass])) {
					// Skip permissions that are currently not compatible, but don't remove them.
					continue;
				}

				$enabled = (bool)$row['permission_enabled'];
				$shares[$id]['permissions'][$permissionTypeClass] = new SharePermission($permissionTypeClass, $enabled);
			}
		}

		$permissionTypes = $this->registry->getPermissionTypes();

		foreach (array_keys($shares) as $id) {
			foreach (array_keys($shareCompatiblePermissionTypeClasses[$id]) as $permissionTypeClass) {
				$permissionType = $permissionTypes[$permissionTypeClass];
				if (!isset($shares[$id]['permissions'][$permissionTypeClass])) {
					$enabled = $permissionType->isEnabledByDefault();

					$timestamp = $this->manager->generateTimestamp();
					$this->setLastUpdated([(string)$id], $timestamp);

					$qb = $this->connection->getQueryBuilder();
					$qb
						->insert('sharing_share_permissions')
						->values([
							'share_id' => $qb->createNamedParameter($id),
							'permission_class' => $qb->createNamedParameter($permissionTypeClass),
							'permission_enabled' => $qb->createNamedParameter($enabled, IQueryBuilder::PARAM_BOOL),
						])
						->executeStatement();

					$shares[$id]['permissions'][$permissionTypeClass] = new SharePermission($permissionTypeClass, $enabled);
					$shares[$id]['last_updated'] = $timestamp;
				}
			}
		}

		$shares = array_map(static fn (array $share): Share => new Share(
			$share['id'],
			$share['owner'],
			$share['last_updated'],
			$share['state'],
			$share['sources'],
			$share['recipients'],
			$share['properties'],
			$share['permissions'],
		), $shares);

		if (!$accessContext->overrideChecks) {
			$filterPropertyTypes = array_filter($registryPropertyTypes, static fn (ISharePropertyType $propertyType): bool => $propertyType instanceof ISharePropertyTypeFilter);
			if ($filterPropertyTypes !== []) {
				$shares = array_filter($shares, static function (Share $share) use ($accessContext, $filterPropertyTypes): bool {
					if ($share->owner->isCurrentUser($accessContext)) {
						return true;
					}

					foreach ($filterPropertyTypes as $filterPropertyType) {
						if ($filterPropertyType->isFiltered($accessContext, $share)) {
							return false;
						}
					}

					return true;
				});
			}
		}

		return array_values($shares);
	}
}
