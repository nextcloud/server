<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use Closure;
use Exception;
use OCA\Sharing\ResponseDefinitions;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\Exception\ShareForbiddenException;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\IManager;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Property\ISharePropertyTypeModifyValue;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\IShareRecipientTypeSearch;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareOwner;
use OCP\Sharing\ShareState;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;
use OCP\Snowflake\ISnowflakeGenerator;
use RuntimeException;

// TODO: Add reshares
// TODO: Add listeners to remove recipients and sources when they are deleted
// TODO: Pass on full share to features
// TODO: Add accept/reject

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 */
final readonly class Manager implements IManager {
	public function __construct(
		private IDBConnection $connection,
		private IUserManager $userManager,
		private Registry $registry,
	) {
	}

	/**
	 * For some reason rector always tries to add ShareRecipient[] as the return type and there is no way to stop it.
	 * @param ?class-string<IShareRecipientType> $recipientTypeClass
	 * @param non-empty-string $query
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @throws ShareInvalidException
	 */
	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, ?string $recipientTypeClass, string $query, int $limit, int $offset): array {
		$recipientTypes = $this->registry->getRecipientTypes();

		if ($recipientTypeClass !== null) {
			if (!isset($recipientTypes[$recipientTypeClass])) {
				throw new ShareInvalidException('The recipient type is not registered: ' . $recipientTypeClass);
			}

			$recipientTypes = [$recipientTypeClass => $recipientTypes[$recipientTypeClass]];
		}

		$searchableRecipientTypes = array_values(array_filter(
			$recipientTypes,
			static fn (IShareRecipientType $recipientType): bool => $recipientType instanceof IShareRecipientTypeSearch,
		));

		return array_merge(...array_map(
			static fn (IShareRecipientTypeSearch $recipientType): array => $recipientType->searchRecipients($accessContext, $query, $limit, $offset),
			$searchableRecipientTypes,
		));
	}

	#[\Override]
	public function createShare(ShareAccessContext $accessContext): string {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No user present to create a share');
		}

		$id = Server::get(ISnowflakeGenerator::class)->nextId();
		$lastUpdated = $this->generateLastUpdated();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('sharing_share')
			->values([
				'id' => $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT),
				'owner' => $qb->createNamedParameter($currentUser->getUID()),
				'last_updated' => $qb->createNamedParameter($lastUpdated),
				'state' => $qb->createNamedParameter(ShareState::Draft->value),
			])
			->executeStatement();

		return $id;
	}

	#[\Override]
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		// TODO: When changing the state to active, make sure at least one source and one recipient are set, one permission is enabled and all required properties have values.

		$this->wrapUpdate($id, function () use ($state, $id): void {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share')
				->set('state', $qb->createNamedParameter($state->value))
				->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
				->executeStatement();
		});
	}

	#[\Override]
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		if (($sourceType = $this->registry->getSourceTypes()[$source->class] ?? null) === null) {
			throw new ShareInvalidException('The source type is not registered: ' . $source->class);
		}

		$owner = $this->getShareOwner($id);

		if (!$sourceType->validateSource($owner, $source->value)) {
			throw new ShareInvalidException('The source ' . $source->value . ' for ' . $source->class . ' is not valid.');
		}

		$this->wrapUpdate($id, function () use ($id, $source): void {
			try {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->insert('sharing_share_sources')
					->values([
						'id' => $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT),
						'source_class' => $qb->createNamedParameter($source->class),
						'source_value' => $qb->createNamedParameter($source->value),
					])
					->executeStatement();

				// TODO: Maybe trigger insertion of default property values
				// TODO: Maybe trigger insertion of default permission enabled
			} catch (Exception $exception) {
				if (!$exception instanceof \OCP\DB\Exception || $exception->getReason() !== \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $exception;
				}
			}
		});
	}

	#[\Override]
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		$this->wrapUpdate($id, function () use ($id, $source): void {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->delete('sharing_share_sources')
				->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->eq('source_class', $qb->createNamedParameter($source->class)))
				->andWhere($qb->expr()->eq('source_value', $qb->createNamedParameter($source->value)))
				->executeStatement();
		});
	}

	#[\Override]
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new ShareInvalidException('The recipient type is not registered: ' . $recipient->class);
		}

		$owner = $this->getShareOwner($id);

		if (!$recipientType->validateRecipient($owner, $recipient->value)) {
			throw new ShareInvalidException('The recipient ' . $recipient->value . ' for ' . $recipient->class . ' is not valid.');
		}

		$this->wrapUpdate($id, function () use ($id, $recipient): void {
			try {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->insert('sharing_share_recipients')
					->values([
						'id' => $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT),
						'recipient_class' => $qb->createNamedParameter($recipient->class),
						'recipient_value' => $qb->createNamedParameter($recipient->value),
					])
					->executeStatement();

				// TODO: Maybe trigger insertion of default property values
			} catch (Exception $exception) {
				if (!$exception instanceof \OCP\DB\Exception || $exception->getReason() !== \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $exception;
				}
			}
		});
	}

	#[\Override]
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		$this->wrapUpdate($id, function () use ($id, $recipient): void {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->delete('sharing_share_recipients')
				->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->eq('recipient_class', $qb->createNamedParameter($recipient->class)))
				->andWhere($qb->expr()->eq('recipient_value', $qb->createNamedParameter($recipient->value)))
				->executeStatement();
		});
	}

	#[\Override]
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		if (($propertyType = $this->registry->getPropertyTypes()[$property->class] ?? null) === null) {
			throw new ShareInvalidException('The property is not registered: ' . $property->class);
		}

		if ($property->value !== null && ($message = $propertyType->validateValue($property->value)) !== true) {
			throw new ShareInvalidException($message);
		}

		$this->wrapUpdate($id, function () use ($propertyType, $id, $property): void {
			$value = $property->value;

			if ($propertyType instanceof ISharePropertyTypeModifyValue) {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->select('property_value')
					->from('sharing_share_properties')
					->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
					->andWhere($qb->expr()->eq('property_class', $qb->createNamedParameter($property->class)));

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
				->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->eq('property_class', $qb->createNamedParameter($property->class)))
				->executeStatement();
			if ($rowCount === 0) {
				throw new ShareInvalidException('Tried to update a property that does not exist: ' . $property->class);
			}
		});
	}

	#[\Override]
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		if (!isset($this->registry->getPermissionTypes()[$permission->class])) {
			throw new ShareInvalidException('The permission type is not registered: ' . $permission->class);
		}

		$this->wrapUpdate($id, function () use ($id, $permission): void {
			$qb = $this->connection->getQueryBuilder();
			$rowCount = $qb
				->update('sharing_share_permissions')
				->set('permission_enabled', $qb->createNamedParameter($permission->enabled, IQueryBuilder::PARAM_BOOL))
				->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->eq('permission_class', $qb->createNamedParameter($permission->class)))
				->executeStatement();
			if ($rowCount === 0) {
				throw new ShareInvalidException('Tried to update a permission that does not exist: ' . $permission->class);
			}
		});
	}


	#[\Override]
	public function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$this->validateShareOwnerOperation($accessContext, $id);

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		// The other tables are cleared by their foreign key constraints and on delete cascade.
	}

	#[\Override]
	public function getShare(ShareAccessContext $accessContext, string $id): Share {
		$shares = $this->list($accessContext, $id, null, null, null);
		if (count($shares) !== 1) {
			throw new ShareNotFoundException($id);
		}

		return $shares[0];
	}

	#[\Override]
	public function listShares(ShareAccessContext $accessContext, ?string $sourceTypeClass, ?string $lastShareID, ?int $limit): array {
		return $this->list($accessContext, null, $sourceTypeClass, $lastShareID, $limit);
	}

	/**
	 * @return non-negative-int
	 */
	private function generateLastUpdated(): int {
		$time = (int)(microtime(true) * 1000.0);
		if ($time < 0) {
			throw new RuntimeException('Have you invented time travel?');
		}

		return $time;
	}

	/**
	 * @return non-negative-int
	 */
	private function wrapUpdate(string $id, Closure $closure): int {
		try {
			$lastUpdated = $this->generateLastUpdated();

			$this->connection->beginTransaction();

			// First update the row to get a lock on it
			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share')
				->set('last_updated', $qb->createNamedParameter($lastUpdated, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)))
				->executeStatement();

			$closure();

			$this->connection->commit();
			return $lastUpdated;
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	/**
	 * @throws ShareNotFoundException
	 * @throws \OCP\DB\Exception
	 */
	private function getShareOwner(string $id): IUser {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->select('owner')
			->from('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$id, IQueryBuilder::PARAM_INT)));

		/** @var non-empty-string|false $uid */
		$uid = $qb->executeQuery()->fetchOne();
		if ($uid === false) {
			throw new ShareNotFoundException($id);
		}

		$user = $this->userManager->get($uid);
		if ($user === null) {
			throw new ShareNotFoundException($id);
		}

		return $user;
	}

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 */
	private function validateShareOwnerOperation(ShareAccessContext $accessContext, string $id): void {
		// Even if we don't use the owner, fetch it here to ensure the share exists in the first place.
		$owner = $this->getShareOwner($id);

		if ($accessContext->force) {
			return;
		}

		if (!$accessContext->currentUser instanceof IUser) {
			throw new ShareForbiddenException($id);
		}

		if ($owner->getUID() !== $accessContext->currentUser->getUID()) {
			throw new ShareForbiddenException($id);
		}
	}

	/**
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @return list<Share>
	 * @throws ShareInvalidException
	 */
	private function list(ShareAccessContext $accessContext, ?string $filterShareID, ?string $filterSourceTypeClass, ?string $lastShareID, ?int $limit): array {
		$queries = [];
		if ($accessContext->force) {
			$queries[] = $this->connection->getQueryBuilder();
		} else {
			// Because doctrine has no UNION support, individual queries have to be used

			if ($accessContext->currentUser instanceof IUser) {
				$qb = $this->connection->getQueryBuilder();
				$qb->where($qb->expr()->eq('s.owner', $qb->createNamedParameter($accessContext->currentUser->getUID())));
				$queries[] = $qb;
			}

			$recipients = [];
			foreach ($this->registry->getRecipientTypes() as $recipientType) {
				$recipientValues = $recipientType->getRecipients($accessContext->currentUser, $accessContext->arguments[$recipientType::class] ?? null);
				if ($recipientValues !== []) {
					$recipients[$recipientType::class] = $recipientValues;
				}
			}

			// Do not add a query if no recipients matched, otherwise all shares will be returned.
			if ($recipients !== []) {
				$qb = $this->connection->getQueryBuilder();
				$qb->innerJoin('s', 'sharing_share_recipients', 'sr', $qb->expr()->andX(
					$qb->expr()->eq('sr.id', 's.id'),
					$qb->expr()->eq('s.state', $qb->createNamedParameter(ShareState::Active->value)),
				));

				foreach ($recipients as $recipientTypeClass => $recipientValues) {
					$qb->orWhere($qb->expr()->andX(
						$qb->expr()->eq('sr.recipient_class', $qb->createNamedParameter($recipientTypeClass)),
						// TODO: Add chunking
						$qb->expr()->in('sr.recipient_value', $qb->createNamedParameter($recipientValues, IQueryBuilder::PARAM_STR_ARRAY)),
					));
				}

				$queries[] = $qb;
			}
		}

		$shares = [];
		foreach ($queries as $qb) {
			$qb
				->select(
					's.id',
					's.owner',
					's.last_updated',
					's.state',
				)
				->from('sharing_share', 's')
				->orderBy('s.id', 'ASC');

			if ($filterShareID !== null) {
				$qb->andWhere($qb->expr()->eq('s.id', $qb->createNamedParameter((int)$filterShareID, IQueryBuilder::PARAM_INT)));
			}

			if ($filterSourceTypeClass !== null) {
				$qb->innerJoin('s', 'sharing_share_sources', 'ss', $qb->expr()->andX(
					$qb->expr()->eq('s.id', 'ss.id'),
					$qb->expr()->eq('ss.source_class', $qb->createNamedParameter($filterSourceTypeClass)),
				));
			}

			if ($lastShareID !== null) {
				if (!ctype_digit($lastShareID)) {
					throw new ShareInvalidException('The lastShareId is invalid.');
				}

				$qb->andWhere($qb->expr()->gt('s.id', $qb->createNamedParameter((int)$lastShareID, IQueryBuilder::PARAM_INT)));
			}

			if ($limit !== null) {
				$qb->setMaxResults($limit);
			}

			$result = $qb->executeQuery();
			$rows = $result->fetchAll();
			foreach ($rows as $row) {
				// Because Snowflake IDs are numeric-strings, PHP converts them to integers automatically when used as array keys.
				// We'll just accept that here, as we only need them for constant time lookups and discard them later anyway.
				$id = (int)$row['id'];
				/** @var non-negative-int $lastUpdated */
				$lastUpdated = (int)$row['last_updated'];
				/** @var non-empty-string $owner */
				$owner = (string)$row['owner'];
				$shares[$id] ??= [
					'id' => (string)$id,
					'owner' => new ShareOwner($owner),
					'last_updated' => $lastUpdated,
					'state' => ShareState::from((string)$row['state']),
					'sources' => [],
					'recipients' => [],
					'properties' => [],
					'permissions' => [],
				];
			}
		}

		// If multiple queries are used the shares are not automatically sorted already.
		if (count($queries) > 1) {
			ksort($shares);
		}

		// The queries are limited already, but could return more results in total, so discard them here.
		if ($limit !== null) {
			$shares = array_slice($shares, 0, $limit, true);
		}

		$chunks = array_chunk(array_keys($shares), 1000);

		$registrySourceTypes = $this->registry->getSourceTypes();
		$shareSourceTypes = [];
		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'source_class',
					'source_value',
				)
				->from('sharing_share_sources')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				/** @var class-string<IShareSourceType> $type */
				$type = (string)$row['source_class'];
				if (!isset($registrySourceTypes[$type])) {
					// Skip sources that are currently not compatible, but don't remove them.
					continue;
				}

				/** @var non-empty-string $value */
				$value = (string)$row['source_value'];
				$id = (int)$row['id'];
				$shares[$id]['sources'][] = new ShareSource(
					$type,
					$value,
				);

				$shareSourceTypes[$id] ??= [];
				$shareSourceTypes[$id][$type] = true;
			}
		}

		/** @var array<int, list<class-string<IShareSourceType>>> $shareSourceTypes */
		$shareSourceTypes = array_map(array_keys(...), $shareSourceTypes);

		$registryRecipientTypes = $this->registry->getRecipientTypes();
		$shareRecipientTypes = [];
		foreach ($chunks as $chunk) {
			// TODO: As a recipient the other recipients should not be leaked to me
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'recipient_class',
					'recipient_value',
				)
				->from('sharing_share_recipients')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				/** @var class-string<IShareRecipientType> $type */
				$type = (string)$row['recipient_class'];
				if (!isset($registryRecipientTypes[$type])) {
					// Skip recipients that are currently not compatible, but don't remove them.
					continue;
				}

				/** @var non-empty-string $value */
				$value = (string)$row['recipient_value'];
				$id = (int)$row['id'];
				$shares[$id]['recipients'][] = new ShareRecipient(
					$type,
					$value,
				);

				$shareRecipientTypes[$id] ??= [];
				$shareRecipientTypes[$id][$type] = true;
			}
		}

		/** @var array<int, list<class-string<IShareRecipientType>>> $shareRecipientTypes */
		$shareRecipientTypes = array_map(array_keys(...), $shareRecipientTypes);

		$propertyTypeCompatibleSourceTypes = [];
		$propertyTypeCompatibleRecipientTypes = [];
		$registryProperties = $this->registry->getPropertyTypes();
		foreach (array_keys($registryProperties) as $propertyTypeClass) {
			$propertyTypeCompatibleSourceTypes[$propertyTypeClass] = $this->registry->getSourceTypesCompatibleWithPropertyType($propertyTypeClass);
			$propertyTypeCompatibleRecipientTypes[$propertyTypeClass] = $this->registry->getRecipientTypesCompatibleWithPropertyType($propertyTypeClass);
		}

		$sharePropertyTypes = [];
		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'property_class',
					'property_value',
				)
				->from('sharing_share_properties')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (int)$row['id'];
				if (!isset($shareSourceTypes[$id], $shareRecipientTypes[$id])) {
					continue;
				}

				/** @var class-string<ISharePropertyType> $propertyTypeClass */
				$propertyTypeClass = (string)$row['property_class'];
				if (!isset($propertyTypeCompatibleSourceTypes[$propertyTypeClass], $propertyTypeCompatibleRecipientTypes[$propertyTypeClass])) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				if (array_intersect($propertyTypeCompatibleSourceTypes[$propertyTypeClass], $shareSourceTypes[$id]) === []) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				if (array_intersect($propertyTypeCompatibleRecipientTypes[$propertyTypeClass], $shareRecipientTypes[$id]) === []) {
					// Skip properties that are currently not compatible, but don't remove them.
					continue;
				}

				$value = $row['property_value'] !== null ? (string)$row['property_value'] : null;

				$propertyType = $registryProperties[$propertyTypeClass];
				if ($propertyType instanceof ISharePropertyTypeModifyValue) {
					$value = $propertyType->modifyValueOnLoad($value);
				}

				$shares[$id]['properties'][] = new ShareProperty($propertyTypeClass, $value);
				$sharePropertyTypes[$id] ??= [];
				$sharePropertyTypes[$id][$propertyTypeClass] = true;
			}
		}

		foreach (array_keys($shares) as $id) {
			foreach ($registryProperties as $propertyTypeClass => $propertyType) {
				if (
					!isset($sharePropertyTypes[$id][$propertyTypeClass])
					&& isset($shareSourceTypes[$id], $shareRecipientTypes[$id])
					&& array_intersect($propertyTypeCompatibleSourceTypes[$propertyTypeClass], $shareSourceTypes[$id]) !== []
					&& array_intersect($propertyTypeCompatibleRecipientTypes[$propertyTypeClass], $shareRecipientTypes[$id]) !== []) {
					$value = $propertyType->getDefaultValue();

					$lastUpdated = $this->wrapUpdate((string)$id, function () use ($id, $propertyTypeClass, $value): void {
						$qb = $this->connection->getQueryBuilder();
						$qb
							->insert('sharing_share_properties')
							->values([
								'id' => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
								'property_class' => $qb->createNamedParameter($propertyTypeClass),
								'property_value' => $qb->createNamedParameter($value),
							])
							->executeStatement();
					});

					$shares[$id]['properties'][] = new ShareProperty($propertyTypeClass, $value);
					$shares[$id]['last_updated'] = $lastUpdated;
				}
			}
		}

		$permissionTypes = $this->registry->getPermissionTypes();
		$sourceTypePermissionTypes = array_map(
			static fn (array $permissionTypeClasses): array => array_map(
				static fn (string $permissionTypeClass) => $permissionTypes[$permissionTypeClass],
				$permissionTypeClasses,
			),
			$this->registry->getSourceTypePermissionTypes(),
		);
		$permissionTypeSourceType = $this->registry->getPermissionTypeSourceType();
		$permissionCategoryTypes = $this->registry->getPermissionCategoryTypes();

		$sharePermissionTypes = [];
		foreach ($chunks as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->select(
					'id',
					'permission_class',
					'permission_enabled',
				)
				->from('sharing_share_permissions')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $qb->executeQuery();
			foreach ($result->fetchAll() as $row) {
				$id = (int)$row['id'];
				if (!isset($shareSourceTypes[$id])) {
					continue;
				}

				/** @var class-string<ISharePermissionType> $permissionTypeClass */
				$permissionTypeClass = (string)$row['permission_class'];
				if (!isset($permissionTypeSourceType[$permissionTypeClass])) {
					// Skip permissions that are currently not compatible, but don't remove them.
					continue;
				}

				if (!in_array($permissionTypeSourceType[$permissionTypeClass], $shareSourceTypes[$id], true)) {
					// Skip permissions that are currently not compatible, but don't remove them.
					continue;
				}

				$shares[$id]['permissions'][] = new SharePermission($permissionTypeClass, (bool)$row['permission_enabled']);
				$sharePermissionTypes[$id] ??= [];
				$sharePermissionTypes[$id][$permissionTypeClass] = true;
			}
		}

		foreach (array_keys($shares) as $id) {
			if (!isset($shareSourceTypes[$id])) {
				continue;
			}

			foreach ($shareSourceTypes[$id] as $sourceTypeClass) {
				foreach (($sourceTypePermissionTypes[$sourceTypeClass] ?? []) as $permissionType) {
					if (!isset($sharePermissionTypes[$id][$permissionType::class])) {
						$enabled = $permissionType->getDefault() ?? (($category = $permissionType->getCategory()) !== null && $permissionCategoryTypes[$category]->getDefault());

						$lastUpdated = $this->wrapUpdate((string)$id, function () use ($id, $permissionType, $enabled): void {
							$qb = $this->connection->getQueryBuilder();
							$qb
								->insert('sharing_share_permissions')
								->values([
									'id' => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
									'permission_class' => $qb->createNamedParameter($permissionType::class),
									'permission_enabled' => $qb->createNamedParameter($enabled, IQueryBuilder::PARAM_BOOL),
								])
								->executeStatement();
						});

						$shares[$id]['permissions'][] = new SharePermission($permissionType::class, $enabled);
						$shares[$id]['last_updated'] = $lastUpdated;
					}
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

		if (!$accessContext->force) {
			$filterPropertyTypes = array_filter($this->registry->getPropertyTypes(), static fn (ISharePropertyType $propertyType): bool => $propertyType instanceof ISharePropertyTypeFilter);
			if ($filterPropertyTypes !== []) {
				// TODO: This could become expensive for many shares, so maybe cache the filtering results.
				$shares = array_filter($shares, static function (Share $share) use ($accessContext, $filterPropertyTypes): bool {
					if ($accessContext->currentUser?->getUID() === $share->owner->userId) {
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
