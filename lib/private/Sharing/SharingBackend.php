<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use Exception;
use NCU\Sharing\Event\SharesDefaultSetEvent;
use NCU\Sharing\Exception\ShareInvalidException;
use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\ISharingBackend;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Property\ISharePropertyTypeFilter;
use NCU\Sharing\Property\ISharePropertyTypeModifyValue;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\ShareUserStatus;
use NCU\Sharing\Source\IShareSourceMetadata;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSource;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use Psr\Clock\ClockInterface;
use RuntimeException;

/**
 * @psalm-import-type SharingShare from Share
 */
final readonly class SharingBackend implements ISharingBackend {
	private IL10N $l10n;

	public function __construct(
		IFactory $factory,
		private IDBConnection $connection,
		private IUserManager $userManager,
		private IAppConfig $appConfig,
		private ISharingRegistry $registry,
		private IEventDispatcher $eventDispatcher,
		private ClassMapper $classMapper,
		private ClockInterface $clock,
	) {
		$this->l10n = $factory->get('sharing');
	}

	#[\Override]
	public function createShare(string $id, ShareUser $owner, \DateTimeImmutable $lastUpdated): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('sharing_share')
			->values([
				'id' => $qb->createNamedParameter($id),
				'owner_user_id' => $qb->createNamedParameter($owner->userId),
				'owner_instance' => $qb->createNamedParameter($owner->instance),
				'last_updated' => $qb->createNamedParameter(SharingManager::timeToMs($lastUpdated)),
				'state' => $qb->createNamedParameter(ShareState::Draft->value),
			])
			->executeStatement();
	}

	#[\Override]
	public function onOwnerDeleted(ShareUser $owner): array {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->selectDistinct('id')
			->from('sharing_share')
			->where($qb->expr()->eq('owner_user_id', $qb->createNamedParameter($owner->userId)));
		if ($owner->instance === null) {
			$qb->andWhere($qb->expr()->isNull('owner_instance'));
		} else {
			$qb->andWhere($qb->expr()->eq('owner_instance', $qb->createNamedParameter($owner->instance)));
		}

		$result = $qb->executeQuery();

		/** @var list<string|int> $ids */
		$ids = $result->fetchFirstColumn();
		if ($ids === []) {
			return [];
		}

		$ids = array_map(static fn (string|int $id): string => (string)$id, $ids);

		foreach (array_chunk($ids, 1000) as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->delete('sharing_share')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
				->executeStatement();
		}

		return $ids;
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
	public function updateShareUserStatus(string $id, string $userId, ShareUserStatus $userStatus): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_share_user_status')
			->set('status', $qb->createNamedParameter($userStatus->value))
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->executeStatement();
		if ($rowCount === 0) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->insert('sharing_share_user_status')
				->values([
					'share_id' => $qb->createNamedParameter($id),
					'user_id' => $qb->createNamedParameter($userId),
					'status' => $qb->createNamedParameter($userStatus->value),
				])
				->executeStatement();
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
					'source_class_id' => $qb->createNamedParameter($this->classMapper->getClassId($source->class), IQueryBuilder::PARAM_INT),
					'source_value' => $qb->createNamedParameter($source->value),
				])
				->executeStatement();
		} catch (Exception $exception) {
			if ($exception instanceof \OCP\DB\Exception && $exception->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new ShareInvalidException(
					'Tried to add share source that already exists: ' . $source->class . ' ' . $source->value,
					$this->l10n->t('The share already contains the source.'), previous: $exception
				);
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
			->andWhere($qb->expr()->eq('source_class_id', $qb->createNamedParameter($this->classMapper->getClassId($source->class), IQueryBuilder::PARAM_INT)))
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
			->where($qb->expr()->eq('source_class_id', $qb->createNamedParameter($this->classMapper->getClassId($source->class), IQueryBuilder::PARAM_INT)))
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
			->where($qb->expr()->eq('source_class_id', $qb->createNamedParameter($this->classMapper->getClassId($source->class), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('source_value', $qb->createNamedParameter($source->value)))
			->executeStatement();

		return $ids;
	}

	#[\Override]
	public function addShareRecipient(string $id, ShareRecipient $recipient): void {
		if ($recipient->secret === null) {
			throw new RuntimeException('The secret must not be null.');
		}

		if (!$recipient->initiator instanceof ShareUser) {
			throw new RuntimeException('The initiator must not be null.');
		}

		try {
			$qb = $this->connection->getQueryBuilder();

			$values = [
				'share_id' => $qb->createNamedParameter($id),
				'recipient_class_id' => $qb->createNamedParameter($this->classMapper->getClassId($recipient->class), IQueryBuilder::PARAM_INT),
				'recipient_value' => $qb->createNamedParameter($recipient->value),
				'recipient_instance' => $qb->createNamedParameter($recipient->instance),
				'recipient_secret' => $qb->createNamedParameter($recipient->secret),
				'initiator_user_id' => $qb->createNamedParameter($recipient->initiator->userId),
				'initiator_instance' => $qb->createNamedParameter($recipient->initiator->instance),
			];

			$qb
				->insert('sharing_share_recipients')
				->values($values)
				->executeStatement();
		} catch (Exception $exception) {
			if ($exception instanceof \OCP\DB\Exception && $exception->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new ShareInvalidException(
					'Tried to add share recipient that already exists: ' . $recipient->class . ' ' . $recipient->value . ' ' . ($recipient->instance ?? 'local'),
					$this->l10n->t('The share already contains the recipient.'), previous: $exception
				);
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
			->andWhere(
				$qb->expr()->eq('recipient_class_id', $qb->createNamedParameter($this->classMapper->getClassId($recipient->class), IQueryBuilder::PARAM_INT))
			)
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
			->where(
				$qb->expr()->eq('recipient_class_id', $qb->createNamedParameter($this->classMapper->getClassId($recipient->class), IQueryBuilder::PARAM_INT))
			)
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
			->where(
				$qb->expr()->eq('recipient_class_id', $qb->createNamedParameter($this->classMapper->getClassId($recipient->class), IQueryBuilder::PARAM_INT))
			)
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
	public function onInitiatorDeleted(ShareUser $initiator): array {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->selectDistinct('share_id')
			->from('sharing_share_recipients')
			->where($qb->expr()->eq('initiator_user_id', $qb->createNamedParameter($initiator->userId)));
		if ($initiator->instance === null) {
			$qb->andWhere($qb->expr()->isNull('initiator_instance'));
		} else {
			$qb->andWhere($qb->expr()->eq('initiator_instance', $qb->createNamedParameter($initiator->instance)));
		}

		$result = $qb->executeQuery();

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
				->andWhere($qb->expr()->eq('initiator_user_id', $qb->createNamedParameter($initiator->userId)));
			if ($initiator->instance === null) {
				$qb->andWhere($qb->expr()->isNull('initiator_instance'));
			} else {
				$qb->andWhere($qb->expr()->eq('initiator_instance', $qb->createNamedParameter($initiator->instance)));
			}

			$qb->executeStatement();
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
			->andWhere(
				$qb->expr()->eq('recipient_class_id', $qb->createNamedParameter($this->classMapper->getClassId($recipient->class), IQueryBuilder::PARAM_INT))
			)
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
	public function updateShareProperty(string $id, ShareProperty $property): ?string {
		$value = $property->value;

		$propertyType = $this->registry->getPropertyTypes()[$property->class];

		if ($propertyType instanceof ISharePropertyTypeModifyValue) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select('sp.property_value')
				->from('sharing_share_properties', 'sp')
				->where($qb->expr()->eq('sp.share_id', $qb->createNamedParameter($id)))
				->andWhere(
					$qb->expr()->eq(
						'sp.property_class_id', $qb->createNamedParameter($this->classMapper->getClassId($property->class), IQueryBuilder::PARAM_INT)
					)
				);

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
			->andWhere(
				$qb->expr()->eq('property_class_id', $qb->createNamedParameter($this->classMapper->getClassId($property->class), IQueryBuilder::PARAM_INT))
			)
			->executeStatement();
		if ($rowCount === 0) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->insert('sharing_share_properties')
				->values([
					'share_id' => $qb->createNamedParameter($id),
					'property_class_id' => $qb->createNamedParameter($this->classMapper->getClassId($property->class), IQueryBuilder::PARAM_INT),
					'property_value' => $qb->createNamedParameter($value),
				])
				->executeStatement();
		}

		return $value;
	}

	#[\Override]
	public function updateSharePermission(string $id, SharePermission $permission): void {
		$qb = $this->connection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_share_permissions')
			->set('permission_enabled', $qb->createNamedParameter($permission->enabled, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
			->andWhere(
				$qb->expr()->eq('permission_class_id', $qb->createNamedParameter($this->classMapper->getClassId($permission->class), IQueryBuilder::PARAM_INT))
			)
			->executeStatement();
		if ($rowCount === 0) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->insert('sharing_share_permissions')
				->values([
					'share_id' => $qb->createNamedParameter($id),
					'permission_class_id' => $qb->createNamedParameter($this->classMapper->getClassId($permission->class), IQueryBuilder::PARAM_INT),
					'permission_enabled' => $qb->createNamedParameter($permission->enabled, IQueryBuilder::PARAM_BOOL),
				])
				->executeStatement();
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
			$chunkIds = array_map($this->classMapper->getClassId(...), $chunk);
			// Some permissions might not be compatible with the share, just ignore it and update the ones that are present.
			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share_permissions')
				->set('permission_enabled', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
				->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->in('permission_class_id', $qb->createNamedParameter($chunkIds, IQueryBuilder::PARAM_INT_ARRAY)))
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
		$shares = $this->list($accessContext, $id, null, null, null, null, null, null);
		if (count($shares) !== 1) {
			throw new ShareNotFoundException();
		}

		return $shares[0];
	}

	#[\Override]
	public function getShares(
		ShareAccessContext $accessContext,
		?string $filterSourceTypeClass,
		?string $filterSourceTypeValue,
		?ShareState $filterState,
		?ShareUserStatus $filterUserStatus,
		?string $lastShareID,
		?int $limit,
	): array {
		return $this->list($accessContext, null, $filterSourceTypeClass, $filterSourceTypeValue, $filterState, $filterUserStatus, $lastShareID, $limit);
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
	public function setLastUpdated(array $ids, \DateTimeImmutable $lastUpdated): void {
		foreach (array_chunk($ids, 1000) as $chunk) {
			$qb = $this->connection->getQueryBuilder();

			$rowCount = $qb
				->update('sharing_share')
				->set('last_updated', $qb->createNamedParameter(SharingManager::timeToMs($lastUpdated)))
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
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
	 * @param ?non-empty-string $filterSourceTypeValue
	 * @return list<Share>
	 */
	private function list(
		ShareAccessContext $accessContext,
		?string $filterShareID,
		?string $filterSourceTypeClass,
		?string $filterSourceTypeValue,
		?ShareState $filterState,
		?ShareUserStatus $filterUserStatus,
		?string $lastShareID,
		?int $limit,
	): array {
		if ($filterSourceTypeClass) {
			$filterSourceType = $this->registry->getSourceTypes()[$filterSourceTypeClass] ?? null;
			if ($filterSourceType === null) {
				throw new RuntimeException('The source type is not registered: ' . $filterSourceTypeClass);
			}
		} else {
			$filterSourceType = null;
		}

		/** @var array<class-string<IShareRecipientType>, list<string>> $recipientTypeValues */
		$recipientTypeValues = [];

		/** @var list<IQueryBuilder> $queries */
		$queries = [];
		if ($accessContext->overrideChecks) {
			$queries[] = $this->connection->getQueryBuilder();
		} else {
			if ($accessContext->currentUser instanceof IUser) {
				$qb = $this->connection->getQueryBuilder();
				// if we're filtering by source or id, we need to also check for non-owned shares
				if ($filterSourceTypeValue === null && $filterShareID === null) {
					$qb->where($qb->expr()->eq('s.owner_user_id', $qb->createNamedParameter($accessContext->currentUser->getUID())));
				}

				$queries[] = $qb;
			}

			foreach ($this->registry->getRecipientTypes() as $recipientType) {
				$recipientValues = $recipientType->getRecipients($accessContext->currentUser, $accessContext->arguments[$recipientType::class] ?? null);
				if ($recipientValues !== []) {
					$recipientTypeValues[$recipientType::class] = $recipientValues;
				}
			}

			// Do not add a query if no recipients matched, otherwise all shares will be returned.
			// If the user has "direct" access, we already get all the shares, so no need to run an extra query for recipients
			if ($recipientTypeValues !== []) {
				$qb = $this->connection->getQueryBuilder();
				$qb->innerJoin(
					's', 'sharing_share_recipients', 'sr', $qb->expr()->andX(
						$qb->expr()->eq('s.state', $qb->createNamedParameter(ShareState::Active->value)),
						$qb->expr()->eq('s.id', 'sr.share_id'),
					)
				);

				foreach ($recipientTypeValues as $recipientTypeClass => $recipientValues) {
					$qb->orWhere(
						$qb->expr()->andX(
							$qb->expr()->eq(
								'sr.recipient_class_id',
								$qb->createNamedParameter($this->classMapper->getClassId($recipientTypeClass), IQueryBuilder::PARAM_INT)
							),
							// TODO: Add chunking
							$qb->expr()->in('sr.recipient_value', $qb->createNamedParameter($recipientValues, IQueryBuilder::PARAM_STR_ARRAY)),
							$qb->expr()->isNull('sr.recipient_instance'),
						)
					);
				}

				$queries[] = $qb;
			}

			if ($filterShareID !== null && $accessContext->secret !== null) {
				$qb = $this->connection->getQueryBuilder();
				$qb->innerJoin(
					's', 'sharing_share_recipients', 'sr', $qb->expr()->andX(
						$qb->expr()->eq('s.state', $qb->createNamedParameter(ShareState::Active->value)),
						$qb->expr()->eq('s.id', 'sr.share_id'),
						$qb->expr()->eq('sr.recipient_secret', $qb->createNamedParameter($accessContext->secret)),
					)
				);

				$queries[] = $qb;
			}
		}

		// The key type is array-key, because PHP will automatically cast the value. We can't type it as integer though, because we need to also support 32 bit systems and there the autocasting doesn't happen, if the value is too large.
		/** @var array<array-key, array{id: non-empty-string, owner: ShareUser, last_updated: numeric-string, state: ShareState, user_status: ShareUserStatus, sources: list<ShareSource>, recipients: list<ShareRecipient>, properties: array<class-string<ISharePropertyType>, ShareProperty>, permissions: array<class-string<ISharePermissionType>, SharePermission>}> $shares */
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

			if ($filterSourceType !== null && $filterSourceTypeClass !== null) {
				$sourceTypeFilters = [
					$qb->expr()->eq('s.id', 'ss.share_id'),
					$qb->expr()->eq(
						'ss.source_class_id', $qb->createNamedParameter($this->classMapper->getClassId($filterSourceTypeClass), IQueryBuilder::PARAM_INT)
					),
				];

				if ($filterSourceTypeValue !== null) {
					$sourceTypeFilters[] = $qb->expr()->eq('ss.source_value', $qb->createNamedParameter($filterSourceTypeValue));
				}

				$qb->innerJoin('s', 'sharing_share_sources', 'ss', $qb->expr()->andX(...$sourceTypeFilters));
			}

			if ($filterState instanceof \NCU\Sharing\ShareState) {
				$qb->andWhere($qb->expr()->eq('s.state', $qb->createNamedParameter($filterState->value)));
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
				/** @var non-empty-string $ownerUserId */
				$ownerUserId = $row['owner_user_id'];
				/** @var ?non-empty-string $ownerInstance */
				$ownerInstance = $row['owner_instance'];

				/** @psalm-suppress PossiblyNullReference The share is automatically deleted, when the owner is deleted. */
				if ($ownerInstance === null && !$accessContext->overrideChecks && $this->hideDisabledUserShares() && !$this->userManager->get(
					$ownerUserId
				)->isEnabled()) {
					continue;
				}

				/** @var non-empty-string $id */
				$id = (string)$row['id'];
				/** @var numeric-string $lastUpdated */
				$lastUpdated = (string)$row['last_updated'];
				/** @var string $state */
				$state = $row['state'];
				$shares[$id] ??= [
					'id' => $id,
					'owner' => new ShareUser($ownerUserId, $ownerInstance),
					'last_updated' => $lastUpdated,
					'state' => ShareState::from($state),
					'user_status' => null,
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

		if ($accessContext->currentUser instanceof IUser) {
			foreach ($chunks as $chunk) {
				// Only set a user status for shares where current user is not the owner and thus a recipient.
				$chunk = array_values(array_filter($chunk, static fn (string|int $id): bool => !$shares[$id]['owner']->isCurrentUser($accessContext)));

				$qb = $this->connection->getQueryBuilder();
				$qb
					->select('share_id', 'status')
					->from('sharing_share_user_status')
					->where($qb->expr()->eq('user_id', $qb->createNamedParameter($accessContext->currentUser->getUID())))
					->andWhere($qb->expr()->in('share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

				$result = $qb->executeQuery();
				/** @var list<array{share_id: int|string, status: string}> $rows */
				$rows = $result->fetchAll();
				foreach ($rows as $row) {
					$id = $row['share_id'];
					$userStatus = $row['status'];
					$shares[$id]['user_status'] = ShareUserStatus::from($userStatus);
				}

				foreach ($chunk as $id) {
					$shares[$id]['user_status'] ??= ShareUserStatus::Pending;
				}
			}

			// Filter cannot be applied in the query, because the default pending value might be missing for a user (see above).
			if ($filterUserStatus instanceof \NCU\Sharing\ShareUserStatus) {
				$shares = array_filter($shares, static fn (array $share): bool => $share['user_status'] === $filterUserStatus);

				if ($shares === []) {
					return [];
				}

				/** @var list<list<array-key>> $chunks */
				$chunks = array_chunk(array_keys($shares), 1000);
			}
		}

		$registrySourceTypes = $this->registry->getSourceTypes();
		/** @var array<non-empty-string, array<class-string<IShareSourceType>, bool>> $shareSourceTypeClasses */
		$shareSourceTypeClasses = [];
		foreach ($chunks as $chunk) {
			/** @var array<class-string<IShareSourceType>, non-empty-string[]> $shareSourceValues */
			$shareSourceValues = [];
			/** @var array<class-string<IShareSourceType>, array<non-empty-string, IShareSourceMetadata>> $shareSourceMetas */
			$shareSourceMetas = [];

			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'ss.share_id',
					'ss.source_class_id',
					'ss.source_value',
				)
				->from('sharing_share_sources', 'ss')
				->where($qb->expr()->in('ss.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			$result = $qb->executeQuery();
			/** @var array{source_class_id: mixed, source_value: non-empty-string, share_id: int|string}[] $rows */
			$rows = $result->fetchAll();

			foreach ($rows as $row) {
				$typeClass = $this->classMapper->getClassName((int)$row['source_class_id']);
				$value = $row['source_value'];

				$shareSourceValues[$typeClass] ??= [];
				$shareSourceValues[$typeClass][] = $value;
			}

			foreach ($shareSourceValues as $typeClass => $values) {
				if (($sourceType = ($this->registry->getSourceTypes()[$typeClass] ?? null)) === null) {
					throw new RuntimeException('The source type is not registered: ' . $typeClass);
				}

				$shareSourceMetas[$typeClass] = $sourceType->getSourcesMetadata($values);
			}

			foreach ($rows as $row) {
				/** @var class-string<IShareSourceType> $typeClass */
				$typeClass = $this->classMapper->getClassName((int)$row['source_class_id']);
				if (!isset($registrySourceTypes[$typeClass])) {
					// Skip sources that are currently not compatible, but don't remove them.
					continue;
				}

				$value = $row['source_value'];
				$id = $row['share_id'];
				$shares[$id]['sources'][] = new ShareSource(
					$typeClass,
					$value,
					$shareSourceMetas[$typeClass][$value] ?? null,
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
					'sr.recipient_class_id',
					'sr.recipient_value',
					'sr.recipient_instance',
					'sr.recipient_secret',
					'sr.initiator_user_id',
					'sr.initiator_instance',
				)
				->from('sharing_share_recipients', 'sr')
				->where($qb->expr()->in('sr.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			foreach ($qb->executeQuery()->fetchAll() as $row) {
				/** @var class-string<IShareRecipientType> $typeClass */
				$typeClass = $this->classMapper->getClassName((int)$row['recipient_class_id']);
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
				if ($initiatorInstance === null && !$accessContext->overrideChecks && !$shares[$id]['owner']->isCurrentUser(
					$accessContext
				) && $this->hideDisabledUserShares() && !$this->userManager->get($initiatorUserId)->isEnabled()) {
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
		/** @var array<string, bool> $hasRecipientAccess */
		$hasRecipientAccess = [];
		if (!$accessContext->overrideChecks) {
			foreach ($shares as &$share) {
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

				$hasRecipientAccess[$share['id']] = $isAnyMatchingRecipient && $share['state'] === ShareState::Active;
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
					'sp.property_class_id',
					'sp.property_value',
				)
				->from('sharing_share_properties', 'sp')
				->where($qb->expr()->in('sp.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				/** @var non-empty-string $id */
				$id = (string)$row['share_id'];
				if (!isset($shareSourceTypeClasses[$id], $shareRecipientTypeClasses[$id])) {
					continue;
				}

				/** @var class-string<ISharePropertyType> $propertyTypeClass */
				$propertyTypeClass = $this->classMapper->getClassName((int)$row['property_class_id']);
				if (!isset($registryPropertyTypeCompatibleSourceTypeClasses[$propertyTypeClass], $registryPropertyTypeCompatibleRecipientTypeClasses[$propertyTypeClass])) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				if (array_intersect($registryPropertyTypeCompatibleSourceTypeClasses[$propertyTypeClass], array_keys($shareSourceTypeClasses[$id])) === []) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				if (array_intersect(
					$registryPropertyTypeCompatibleRecipientTypeClasses[$propertyTypeClass], array_keys($shareRecipientTypeClasses[$id])
				) === []) {
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

		$shareCompatiblePermissionTypeClasses = array_map(
			fn (array $shareData): array => array_flip($this->getShareCompatiblePermissionTypeClasses($shareData['sources'])),
			$shares
		);

		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'sp.share_id',
					'sp.permission_class_id',
					'sp.permission_enabled',
				)
				->from('sharing_share_permissions', 'sp')
				->where($qb->expr()->in('sp.share_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (string)$row['share_id'];

				/** @var class-string<ISharePermissionType> $permissionTypeClass */
				$permissionTypeClass = $this->classMapper->getClassName((int)$row['permission_class_id']);
				if (!isset($shareCompatiblePermissionTypeClasses[$id][$permissionTypeClass])) {
					// Skip permissions that are currently not compatible, but don't remove them.
					continue;
				}

				$enabled = (bool)$row['permission_enabled'];
				$shares[$id]['permissions'][$permissionTypeClass] = new SharePermission($permissionTypeClass, $enabled);
			}
		}

		$shares = array_map(static fn (array $share): Share => new Share(
			$share['id'],
			$share['owner'],
			self::parseTimestamp($share['last_updated']),
			$share['state'],
			$share['user_status'],
			$share['sources'],
			$share['recipients'],
			$share['properties'],
			$share['permissions'],
		), $shares);

		// when listing shares for a source, we also return any non-owned share if the user has "direct" access to the source
		// but we do need to validate that the user has "direct" access to *all* of the sources in the share, not just one
		$hasSourceAccess = [];
		if (!$accessContext->overrideChecks && $accessContext->currentUser instanceof IUser) {
			foreach ($shares as $share) {
				if ($share->owner->isCurrentUser($accessContext)) {
					continue;
				}

				if ($hasRecipientAccess[$share->id]) {
					continue;
				}

				if ($share->sources === []) {
					$hasSourceAccess[$share->id] = false;
					continue;
				}

				$hasSourceAccess[$share->id] = true;
				foreach ($share->sources as $source) {
					$sourceType = $this->registry->getSourceTypes()[$source->class];
					if (!$sourceType->userHasDirectSharingAccessToSource($accessContext->currentUser, $source->value)) {
						$hasSourceAccess[$share->id] = false;
					}
				}
			}
		}

		if (!$accessContext->overrideChecks) {
			$shares = array_filter(
				$shares,
				fn (Share $share): bool => $share->owner->isCurrentUser($accessContext) || $hasRecipientAccess[$share->id] || $hasSourceAccess[$share->id]
			);
		}

		if (!$accessContext->overrideChecks) {
			$filterPropertyTypes = array_filter(
				$registryPropertyTypes, static fn (ISharePropertyType $propertyType): bool => $propertyType instanceof ISharePropertyTypeFilter
			);
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

		if ($shares !== []) {
			$shares = $this->ensureDefaults($shares);
		}

		return array_values($shares);
	}

	/**
	 * @param ShareSource[] $sources
	 * @return list<class-string<ISharePermissionType>>
	 */
	private function getShareCompatiblePermissionTypeClasses(array $sources): array {
		$shareSourceTypeClasses = array_map(fn (ShareSource $source): string => $source->class, $sources);
		return $this->registry->getCompatiblePermissionTypeClasses($shareSourceTypeClasses);
	}

	#[\Override]
	public function ensureDefaults(array $shares): array {
		$defaultSet = false;
		foreach ($shares as &$share) {
			$shareSourceTypeClasses = array_map(fn (ShareSource $source): string => $source->class, $share->sources);
			$shareRecipientTypeClasses = array_map(fn (ShareRecipient $recipient): string => $recipient->class, $share->recipients);
			$shareCompatiblePropertyClasses = $this->registry->getCompatiblePropertyTypeClasses($shareSourceTypeClasses, $shareRecipientTypeClasses);

			foreach ($shareCompatiblePropertyClasses as $propertyTypeClass) {
				if (!isset($share->properties[$propertyTypeClass])) {
					$share = $this->createSharePropertyDefaultValue($share, $propertyTypeClass);
					$defaultSet = true;
				}
			}
		}

		foreach ($shares as &$share) {
			$shareCompatiblePermissionTypeClasses = $this->getShareCompatiblePermissionTypeClasses($share->sources);

			foreach ($shareCompatiblePermissionTypeClasses as $permissionTypeClass) {
				if (!isset($share->permissions[$permissionTypeClass])) {
					$share = $this->createSharePermissionDefaultValue($share, $permissionTypeClass);
					$defaultSet = true;
				}
			}
		}

		if ($defaultSet) {
			$event = new SharesDefaultSetEvent($shares);
			$this->eventDispatcher->dispatchTyped($event);
			$shares = $event->getShares();
		}

		return $shares;
	}

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 */
	public function createSharePropertyDefaultValue(Share $share, string $propertyTypeClass): Share {
		$timestamp = $this->clock->now();
		$this->setLastUpdated([$share->id], $timestamp);

		if (($propertyType = $this->registry->getPropertyTypes()[$propertyTypeClass] ?? null) === null) {
			throw new RuntimeException('The property is not registered: ' . $propertyTypeClass);
		}

		$property = new ShareProperty($propertyTypeClass, $propertyType->getDefaultValue($share));

		$value = $this->updateShareProperty($share->id, $property);
		if ($propertyType instanceof ISharePropertyTypeModifyValue) {
			$value = $propertyType->modifyValueOnLoad($value);
		}

		$property = new ShareProperty(
			$property->class,
			$value,
		);

		$properties = $share->properties;
		$properties[$propertyTypeClass] = $property;

		return new Share(
			$share->id,
			$share->owner,
			$timestamp,
			$share->state,
			$share->userStatus,
			$share->sources,
			$share->recipients,
			$properties,
			$share->permissions,
		);
	}

	/**
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 */
	public function createSharePermissionDefaultValue(Share $share, string $permissionTypeClass): Share {
		$timestamp = $this->clock->now();
		$this->setLastUpdated([$share->id], $timestamp);

		if (($permissionType = $this->registry->getPermissionTypes()[$permissionTypeClass] ?? null) === null) {
			throw new RuntimeException('The permission is not registered: ' . $permissionTypeClass);
		}

		$permission = new SharePermission($permissionTypeClass, $permissionType->isEnabledByDefault());

		$this->updateSharePermission($share->id, $permission);

		$permissions = $share->permissions;
		$permissions[$permissionTypeClass] = $permission;

		return new Share(
			$share->id,
			$share->owner,
			$timestamp,
			$share->state,
			$share->userStatus,
			$share->sources,
			$share->recipients,
			$share->properties,
			$permissions,
		);
	}

	private static function parseTimestamp(string $timestampMs): \DateTimeImmutable {
		if (method_exists(\DateTimeImmutable::class, 'createFromTimestamp')) {
			// with php 8.3 the method doesn't exist and psalm doesn't know the return type
			/** @psalm-suppress MixedReturnStatement */
			return \DateTimeImmutable::createFromTimestamp((float)$timestampMs / 1000.0);
		}

		$time = \DateTimeImmutable::createFromFormat('U.u', number_format((float)$timestampMs / 1000.0, 3, '.', ''));
		if ($time === false) {
			throw new \RuntimeException('Invalid timestamp for share: ' . $timestampMs);
		}

		return $time;
	}
}
