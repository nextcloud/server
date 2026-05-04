<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use Closure;
use Exception;
use OC\Sharing\Permission\ReshareSharePermissionType;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Sharing\Exception\ShareForbiddenException;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Permission\SharePermissionPreset;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Property\ISharePropertyTypeModifyValue;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\IShareRecipientTypePublicSecret;
use OCP\Sharing\Recipient\IShareRecipientTypeSearch;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;
use OCP\Snowflake\ISnowflakeGenerator;
use Random\Randomizer;
use RuntimeException;

// TODO: Make backends nested, to allow plugging in legacy files_sharing backend.
// TODO: Add listeners to remove recipients and sources when they are deleted
// TODO: Add accept/reject
// TODO: Add permission masking (reshares)
// TODO: Add mapping table for class names in sources, recipients, permissions and properties
// TODO: Sort/group shares on list by view/edit/custom permissions
// TODO: Check permission presets current behavior

/**
 * @psalm-import-type SharingShare from Share
 */
final readonly class SharingManager implements ISharingManager {
	private Randomizer $randomizer;

	public function __construct(
		private IDBConnection $connection,
		private IUserManager $userManager,
		private ISnowflakeGenerator $snowflakeGenerator,
		private IFactory $l10nFactory,
		private SharingRegistry $registry,
	) {
		$this->randomizer = new Randomizer();

		$this->registry->registerPermissionType(null, new ReshareSharePermissionType());
	}

	/**
	 * For some reason rector always tries to add ShareRecipient[] as the return type and there is no way to stop it.
	 *
	 * @param ?list<class-string<IShareRecipientType>> $recipientTypeClasses
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @throws ShareInvalidException
	 */
	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, ?array $recipientTypeClasses, string $query, int $limit, int $offset): array {
		$recipientTypes = $this->registry->getRecipientTypes();

		if ($recipientTypeClasses !== null) {
			$filteredRecipientTypes = [];
			foreach (array_unique($recipientTypeClasses) as $recipientTypeClass) {
				if (($recipientType = $recipientTypes[$recipientTypeClass] ?? null) === null) {
					throw new ShareInvalidException('The recipient type is not registered: ' . $recipientTypeClass);
				}

				if (!$recipientType instanceof IShareRecipientTypeSearch) {
					throw new ShareInvalidException('The recipient type is not searchable: ' . $recipientTypeClass);
				}

				$filteredRecipientTypes[] = $recipientType;
			}

			$recipientTypes = $filteredRecipientTypes;
		} else {
			$recipientTypes = array_values(array_filter(
				$recipientTypes,
				static fn (IShareRecipientType $recipientType): bool => $recipientType instanceof IShareRecipientTypeSearch,
			));
		}

		return array_merge(...array_map(
			static fn (IShareRecipientTypeSearch $recipientType): array => $recipientType->searchRecipients($accessContext, $query, $limit, $offset),
			$recipientTypes,
		));
	}

	#[\Override]
	public function generateSecret(): string {
		/** @var non-empty-string $secret */
		$secret = $this->randomizer->getBytesFromString(ISecureRandom::CHAR_ALPHANUMERIC, 32);
		return $secret;
	}

	#[\Override]
	public function createShare(ShareAccessContext $accessContext): string {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No user present to create a share');
		}

		$id = $this->snowflakeGenerator->nextId();
		$lastUpdated = $this->generateLastUpdated();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('sharing_share')
			->values([
				'id' => $qb->createNamedParameter($id),
				'owner_user_id' => $qb->createNamedParameter($currentUser->getUID()),
				'last_updated' => $qb->createNamedParameter($lastUpdated),
				'state' => $qb->createNamedParameter(ShareState::Draft->value),
			])
			->executeStatement();

		return $id;
	}

	#[\Override]
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		if ($state === ShareState::Active) {
			$share = $this->getShare($accessContext, $id);

			if ($share->sources === []) {
				throw new ShareInvalidException('No source set.');
			}

			if ($share->recipients === []) {
				throw new ShareInvalidException('No recipient set.');
			}

			if (!array_any($share->permissions, static fn (SharePermission $permission): bool => $permission->enabled)) {
				throw new ShareInvalidException('No permission given.');
			}

			$propertyTypes = $this->registry->getPropertyTypes();
			foreach ($share->properties as $propertyTypeClass => $property) {
				$propertyType = $propertyTypes[$propertyTypeClass];
				if ($property->value === null && $propertyType->getRequired()) {
					throw new ShareInvalidException('Missing value for required property: ' . $propertyTypeClass);
				}
			}
		}

		$this->wrapUpdate($id, function () use ($state, $id): void {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share')
				->set('state', $qb->createNamedParameter($state->value))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeStatement();
		});
	}

	#[\Override]
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		if (($sourceType = $this->registry->getSourceTypes()[$source->class] ?? null) === null) {
			throw new ShareInvalidException('The source type is not registered: ' . $source->class);
		}

		if (!$sourceType->validateSource($owner, $source->value)) {
			throw new ShareInvalidException('The source ' . $source->value . ' for ' . $source->class . ' is not valid.');
		}

		$this->wrapUpdate($id, function () use ($id, $source): void {
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
					throw new ShareInvalidException('Tried to add share source that already exists: ' . $source->class . ' ' . $source->value);
				}

				throw $exception;
			}
		});
	}

	#[\Override]
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		$this->wrapUpdate($id, function () use ($accessContext, $id, $source): void {
			$qb = $this->connection->getQueryBuilder();
			$rowCount = $qb
				->delete('sharing_share_sources')
				->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->eq('source_class', $qb->createNamedParameter($source->class)))
				->andWhere($qb->expr()->eq('source_value', $qb->createNamedParameter($source->value)))
				->executeStatement();
			if ($rowCount === 0) {
				throw new ShareInvalidException('Tried to remove share source that does not exist: ' . $source->class . ' ' . $source->value);
			}

			$share = $this->getShare($accessContext, $id);
			if ($share->state === ShareState::Active && $share->sources === []) {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->update('sharing_share')
					->set('state', $qb->createNamedParameter(ShareState::Draft->value))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
					->executeStatement();
			}
		});
	}

	#[\Override]
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new ShareInvalidException('No current user provided in access context.');
		}

		$owner = $this->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $id, $owner);
		} catch (ShareForbiddenException) {
			$share = $this->getShare($accessContext, $id);
			$this->validatePermission($share, ReshareSharePermissionType::class);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new ShareInvalidException('The recipient type is not registered: ' . $recipient->class);
		}

		// TODO: Check \OCP\Share\IManager::outgoingServer2ServerSharesAllowed and \OCP\Share\IManager::outgoingServer2ServerGroupSharesAllowed
		if (!$recipientType->validateRecipient($owner, $recipient->value)) {
			throw new ShareInvalidException('The recipient ' . $recipient->value . ' for ' . $recipient->class . ' is not valid.');
		}

		$this->wrapUpdate($id, function () use ($currentUser, $id, $recipient): void {
			try {
				$qb = $this->connection->getQueryBuilder();

				$values = [
					'share_id' => $qb->createNamedParameter($id),
					'recipient_class' => $qb->createNamedParameter($recipient->class),
					'recipient_value' => $qb->createNamedParameter($recipient->value),
					'recipient_instance' => $qb->createNamedParameter($recipient->instance),
					'recipient_secret' => $qb->createNamedParameter($this->generateSecret()),
					'initiator_user_id' => $qb->createNamedParameter($currentUser->getUID(), IQueryBuilder::PARAM_STR),
				];

				$qb
					->insert('sharing_share_recipients')
					->values($values)
					->executeStatement();
			} catch (Exception $exception) {
				if ($exception instanceof \OCP\DB\Exception && $exception->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw new ShareInvalidException('Tried to add share recipient that already exists: ' . $recipient->class . ' ' . $recipient->value . ' ' . ($recipient->instance ?? ''));
				}

				throw $exception;
			}
		});
	}

	#[\Override]
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		$owner = $this->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $id, $owner);
		} catch (ShareForbiddenException) {
			if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
				throw new ShareInvalidException('No current user provided in access context.');
			}

			$share = $this->getShare($accessContext, $id);
			// This does not allow removing own recipients. A user can only reject a share, but not remove it for the recipient.
			$this->validateReshareOperation($currentUser, $share, $recipient);
		}

		$this->wrapUpdate($id, function () use ($id, $recipient): void {
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
				throw new ShareInvalidException('Tried to remove share recipient that does not exist: ' . $recipient->class . ' ' . $recipient->value . ' ' . ($recipient->instance ?? ''));
			}

			// Do not use the current share access context, as it might not be able to see all recipients!
			$share = $this->getShare(new ShareAccessContext(overrideChecks: true), $id);
			if ($share->state === ShareState::Active && $share->recipients === []) {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->update('sharing_share')
					->set('state', $qb->createNamedParameter(ShareState::Draft->value))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
					->executeStatement();
			}
		});
	}

	#[\Override]
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): void {
		$owner = $this->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $id, $owner);
		} catch (ShareForbiddenException) {
			if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
				throw new ShareInvalidException('No current user provided in access context.');
			}

			$share = $this->getShare($accessContext, $id);
			$this->validateReshareOperation($currentUser, $share, $recipient);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new ShareInvalidException('The recipient type is not registered: ' . $recipient->class);
		}

		if (!$recipientType instanceof IShareRecipientTypePublicSecret || !$recipientType->isSecretUpdatable($recipient->value)) {
			throw new ShareForbiddenException($id);
		}

		if (!preg_match('/^[a-z0-9-]+$/i', $secret)) {
			throw new ShareInvalidException('The secret is not valid, it must be alphanumeric and may contain dashes.');
		}

		$this->wrapUpdate($id, function () use ($id, $recipient, $secret): void {
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
				throw new ShareInvalidException('Tried to update a share recipient that does not exist: ' . $recipient->class . ' ' . $recipient->value . ' ' . ($recipient->instance ?? ''));
			}
		});
	}

	#[\Override]
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		if (($propertyType = $this->registry->getPropertyTypes()[$property->class] ?? null) === null) {
			throw new ShareInvalidException('The property is not registered: ' . $property->class);
		}

		if ($property->value !== null && ($message = $propertyType->validateValue($this->l10nFactory, $property->value)) !== true) {
			throw new ShareInvalidException($message);
		}

		$this->wrapUpdate($id, function () use ($accessContext, $id, $property, $propertyType): void {
			$value = $property->value;

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
				throw new ShareInvalidException('Tried to update a property that does not exist: ' . $property->class);
			}

			if ($property->value === null && $propertyType->getRequired()) {
				$share = $this->getShare($accessContext, $id);
				if ($share->state === ShareState::Active) {
					$qb = $this->connection->getQueryBuilder();
					$qb
						->update('sharing_share')
						->set('state', $qb->createNamedParameter(ShareState::Draft->value))
						->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
						->executeStatement();
				}
			}
		});
	}

	#[\Override]
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		if (!isset($this->registry->getPermissionTypes()[$permission->class])) {
			throw new ShareInvalidException('The permission type is not registered: ' . $permission->class);
		}

		$this->wrapUpdate($id, function () use ($accessContext, $id, $permission): void {
			$qb = $this->connection->getQueryBuilder();
			$rowCount = $qb
				->update('sharing_share_permissions')
				->set('permission_enabled', $qb->createNamedParameter($permission->enabled, IQueryBuilder::PARAM_BOOL))
				->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->eq('permission_class', $qb->createNamedParameter($permission->class)))
				->executeStatement();

			if ($rowCount === 0) {
				throw new ShareInvalidException('Tried to update a permission that does not exist: ' . $permission->class);
			}

			$share = $this->getShare($accessContext, $id);
			if ($share->state === ShareState::Active && !array_any($share->permissions, static fn (SharePermission $permission): bool => $permission->enabled)) {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->update('sharing_share')
					->set('state', $qb->createNamedParameter(ShareState::Draft->value))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
					->executeStatement();
			}
		});
	}

	#[\Override]
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, SharePermissionPreset $permissionPreset): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		$permissionsToEnable = [];
		foreach ($this->registry->getPermissionTypes() as $permissionType) {
			if (in_array($permissionPreset, $permissionType->getPresets(), true)) {
				$permissionsToEnable[] = $permissionType::class;
			}
		}

		$this->wrapUpdate($id, function () use ($id, $permissionsToEnable): void {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->update('sharing_share_permissions')
				->set('permission_enabled', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
				->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
				->executeStatement();

			foreach (array_chunk($permissionsToEnable, 1000) as $chunk) {
				// Some permissions might not be compatible with the share, just ignore it and update the ones that are present.
				$qb = $this->connection->getQueryBuilder();
				$qb
					->update('sharing_share_permissions')
					->set('permission_enabled', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
					->where($qb->expr()->eq('share_id', $qb->createNamedParameter($id)))
					->andWhere($qb->expr()->in('permission_class', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
					->executeStatement();
			}

			// We don't check if at least one permission is enabled and otherwise change the share state to draft, because we assume every preset has at least one permission belonging to it.
		});
	}

	#[\Override]
	public function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$owner = $this->getShareOwner($id);

		$this->validateShareOwnerOperation($accessContext, $id, $owner);

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
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
	 * @param Closure():void $closure
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
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeStatement();

			$closure();

			$this->connection->commit();
			return $lastUpdated;
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	// TODO: Return ShareOwner and validate instance === null

	/**
	 * @throws ShareNotFoundException
	 */
	private function getShareOwner(string $id): IUser {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->select('s.owner_user_id')
			->from('sharing_share', 's')
			->where($qb->expr()->eq('s.id', $qb->createNamedParameter($id)));

		/** @var non-empty-string|false $uid */
		$uid = $qb->executeQuery()->fetchOne();
		if ($uid === false) {
			throw new ShareNotFoundException($id);
		}

		// TODO: Delete share if owner was deleted. Also listen for BeforeUserDeletedEvent
		if (($user = $this->userManager->get($uid)) === null) {
			throw new ShareNotFoundException($id);
		}

		return $user;
	}

	/**
	 * @throws ShareForbiddenException
	 * @throws ShareNotFoundException
	 */
	private function validateShareOwnerOperation(ShareAccessContext $accessContext, string $id, IUser $owner): void {
		if ($accessContext->overrideChecks) {
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
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 * @throws ShareForbiddenException
	 */
	private function validatePermission(Share $share, string $permissionTypeClass): void {
		if ((($permission = $share->permissions[$permissionTypeClass] ?? null) !== null) && $permission->enabled) {
			return;
		}

		throw new ShareForbiddenException($share->id);
	}

	/**
	 * @throws ShareForbiddenException
	 */
	private function validateReshareOperation(IUser $currentUser, Share $share, ShareRecipient $recipient): void {
		$this->validatePermission($share, ReshareSharePermissionType::class);

		foreach ($share->recipients as $shareRecipient) {
			if ($shareRecipient->initiator === null) {
				throw new RuntimeException('Share recipient initiator is not set.');
			}

			if (
				$recipient->class === $shareRecipient->class
				&& $recipient->value === $shareRecipient->value
				&& $recipient->instance === $shareRecipient->instance
				// TODO: Deal with federated shares
				&& $currentUser->getUID() === $shareRecipient->initiator->userId) {
				return;
			}
		}

		// We're only allowed to remove or update recipients, if we're the initiator.
		throw new ShareForbiddenException($share->id);
	}

	// TODO: Split up the method
	// TODO: Allow filter by source value

	/**
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass
	 * @return list<Share>
	 * @throws ShareInvalidException
	 */
	private function list(ShareAccessContext $accessContext, ?string $filterShareID, ?string $filterSourceTypeClass, ?string $lastShareID, ?int $limit): array {
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
				$qb->innerJoin('s', 'sharing_share_sources', 'ss', $qb->expr()->andX(
					$qb->expr()->eq('s.id', 'ss.share_id'),
					$qb->expr()->eq('ss.source_class', $qb->createNamedParameter($filterSourceTypeClass)),
				));
			}

			if ($lastShareID !== null) {
				if (!ctype_digit($lastShareID)) {
					throw new ShareInvalidException('The lastShareId is invalid.');
				}

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
				/** @var non-negative-int $lastUpdated */
				$lastUpdated = $row['last_updated'];
				// TODO: Skip shares where owner is disabled hide_disabled_user_shares
				/** @var non-empty-string $ownerUserId */
				$ownerUserId = $row['owner_user_id'];
				/** @var ?non-empty-string $ownerInstance */
				$ownerInstance = $row['owner_instance'];
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

		// If multiple queries are used the shares are not automatically sorted already.
		if (count($queries) > 1) {
			ksort($shares);
		}

		// The queries are limited already, but could return more results in total, so discard them here.
		if ($limit !== null) {
			$shares = array_slice($shares, 0, $limit, true);
		}

		/** @var list<list<int>> $chunks */
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

				/** @var non-empty-string $shareId */
				$shareId = (string)$row['share_id'];
				/** @var non-empty-string $value */
				$value = $row['recipient_value'];
				/** @var ?non-empty-string $instance */
				$instance = $row['recipient_instance'];
				// TODO: Skip shares where initiator is disabled hide_disabled_user_shares
				/** @var non-empty-string $initiatorUserId */
				$initiatorUserId = $row['initiator_user_id'];
				/** @var ?non-empty-string $initiatorInstance */
				$initiatorInstance = $row['initiator_instance'];

				$initiator = new ShareUser($initiatorUserId, $initiatorInstance);

				$secret = null;
				if ($accessContext->overrideChecks || $shares[$shareId]['owner']->isCurrentUser($accessContext) || $initiator->isCurrentUser($accessContext)) {
					/** @var non-empty-string $secret */
					$secret = $row['recipient_secret'];
				} elseif ($instance === null) {
					foreach ($recipientTypeValues as $recipientTypeClass => $recipientValues) {
						if ($typeClass === $recipientTypeClass && in_array($value, $recipientValues, true)) {
							/** @var non-empty-string $secret */
							$secret = $row['recipient_secret'];
							break;
						}
					}
				}

				$shares[$shareId]['recipients'][] = new ShareRecipient(
					$typeClass,
					$value,
					$instance,
					$secret,
					$initiator,
				);

				$shareRecipientTypeClasses[$shareId] ??= [];
				$shareRecipientTypeClasses[$shareId][$typeClass] = true;
			}
		}

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

					$lastUpdated = $this->wrapUpdate((string)$id, function () use ($id, $propertyTypeClass, $value): void {
						$qb = $this->connection->getQueryBuilder();
						$qb
							->insert('sharing_share_properties')
							->values([
								'share_id' => $qb->createNamedParameter($id),
								'property_class' => $qb->createNamedParameter($propertyTypeClass),
								'property_value' => $qb->createNamedParameter($value),
							])
							->executeStatement();
					});

					$shares[$id]['properties'][$propertyTypeClass] = new ShareProperty($propertyTypeClass, $value);
					$shares[$id]['last_updated'] = $lastUpdated;
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
					$enabled = $permissionType->getDefault();

					$lastUpdated = $this->wrapUpdate((string)$id, function () use ($id, $permissionTypeClass, $enabled): void {
						$qb = $this->connection->getQueryBuilder();
						$qb
							->insert('sharing_share_permissions')
							->values([
								'share_id' => $qb->createNamedParameter($id),
								'permission_class' => $qb->createNamedParameter($permissionTypeClass),
								'permission_enabled' => $qb->createNamedParameter($enabled, IQueryBuilder::PARAM_BOOL),
							])
							->executeStatement();
					});

					$shares[$id]['permissions'][$permissionTypeClass] = new SharePermission($permissionTypeClass, $enabled);
					$shares[$id]['last_updated'] = $lastUpdated;
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
