<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use Exception;
use NCU\Sharing\Exception\ShareInvalidException;
use NCU\Sharing\Exception\ShareOperationForbiddenException;
use NCU\Sharing\ISharingBackend;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\IShareRecipientTypePublicSecret;
use NCU\Sharing\Recipient\IShareRecipientTypeSearch;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Snowflake\ISnowflakeGenerator;
use OCP\User\Events\BeforeUserDeletedEvent;
use Random\Randomizer;
use RuntimeException;

// TODO: Add accept/reject
// TODO: Add permission masking (reshares)
// TODO: Test sharing to federated users, groups and circles
// TODO: Implement share transfers
// TODO: Cache share owner

/**
 * @psalm-import-type SharingShare from Share
 * @template-implements IEventListener<BeforeUserDeletedEvent>
 */
final readonly class SharingManager implements ISharingManager, IEventListener {
	private Randomizer $randomizer;

	private IL10N $l10n;

	private ISharingBackend $backend;

	public function __construct(
		IEventDispatcher $eventDispatcher,
		private IUserManager $userManager,
		private IFactory $l10nFactory,
		private ISnowflakeGenerator $snowflakeGenerator,
		private IDBConnection $dbConnection,
		private ISharingRegistry $registry,
		IAppConfig $appConfig,
	) {
		$this->randomizer = new Randomizer();
		$this->l10n = $l10nFactory->get('sharing');
		$this->backend = new SharingBackend(
			$l10nFactory,
			$dbConnection,
			$userManager,
			$appConfig,
			$registry,
			$this,
		);

		$eventDispatcher->addServiceListener(BeforeUserDeletedEvent::class, self::class);
	}

	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?string $id = null): array {
		$recipientTypes = $this->registry->getRecipientTypes();

		if ($filterRecipientTypeClasses !== null) {
			$filteredRecipientTypes = [];
			foreach (array_unique($filterRecipientTypeClasses) as $recipientTypeClass) {
				if (($recipientType = $recipientTypes[$recipientTypeClass] ?? null) === null) {
					throw new RuntimeException('The recipient type is not registered: ' . $recipientTypeClass);
				}

				if (!$recipientType instanceof IShareRecipientTypeSearch) {
					throw new RuntimeException('The recipient type is not searchable: ' . $recipientTypeClass);
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

		$results = array_merge(...array_map(
			static fn (IShareRecipientTypeSearch $recipientType): array => $recipientType->searchRecipients($accessContext, $query, $limit, $offset),
			$recipientTypes,
		));

		if ($id !== null) {
			// Do not create a new access context with overridden checks, because it could leak the existence of shares and share recipients.
			$share = $this->getShare($accessContext, $id);
			$recipients = [];
			foreach ($share->recipients as $recipient) {
				$recipients[$recipient->class] ??= [];
				$recipients[$recipient->class][$recipient->instance ?? ''] ??= [];
				$recipients[$recipient->class][$recipient->instance ?? ''][$recipient->value] = true;
			}

			$results = array_values(array_filter($results, static fn (ShareRecipient $recipient): bool => !isset($recipients[$recipient->class][$recipient->instance ?? ''][$recipient->value])));
		}

		return $results;
	}

	#[\Override]
	public function generateSecret(): string {
		/** @var non-empty-string $secret */
		$secret = $this->randomizer->getBytesFromString(ISecureRandom::CHAR_ALPHANUMERIC, 32);
		return $secret;
	}

	#[\Override]
	public function generateTimestamp(): int {
		$time = (int)(microtime(true) * 1000.0);
		if ($time < 0) {
			throw new RuntimeException('Have you invented time travel?');
		}

		return $time;
	}

	#[\Override]
	public function createShare(ShareAccessContext $accessContext): string {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No user present to create a share');
		}

		$this->assertInTransaction();

		$id = $this->snowflakeGenerator->nextId();
		$lastUpdated = $this->generateTimestamp();
		$this->backend->createShare($id, new ShareUser($currentUser->getUID(), null), $lastUpdated);

		$this->processShareUpdates([$id]);

		return $id;
	}

	#[\Override]
	public function onOwnerDeleted(ShareAccessContext $accessContext, ShareUser $owner): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		// No need to update the last updated timestamp, because the share will be deleted anyway.

		$ids = $this->backend->onOwnerDeleted($owner);

		$legacyBackend = $this->registry->getLegacyBackend();
		if ($legacyBackend instanceof ISharingLegacyBackend) {
			foreach ($ids as $id) {
				$legacyBackend->deleteShare($id);
			}
		}
	}

	#[\Override]
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if ($state === ShareState::Active) {
			$share = $this->getShare($accessContext, $id);
			$this->assertShareCanBeActive($share);
		}

		$this->backend->updateShareState($id, $state);

		$this->processShareUpdates([$id]);
	}

	#[\Override]
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (($sourceType = $this->registry->getSourceTypes()[$source->class] ?? null) === null) {
			throw new RuntimeException('The source type is not registered: ' . $source->class);
		}

		if (!$sourceType->validateSource($source->value)) {
			throw new ShareInvalidException('Invalid source: ' . $source->value . ' ' . $source->class, $this->l10n->t('The source does not exist.'));
		}

		$share = $this->getShare($accessContext, $id);
		$sources = $share->sources;
		$sources[] = $source;
		$share = new Share(
			$share->id,
			$share->owner,
			$share->lastUpdated,
			$share->state,
			$sources,
			$share->recipients,
			$share->properties,
			$share->permissions,
		);

		if (!$accessContext->overrideChecks) {
			$this->validateInteraction($accessContext, $share);
		}

		$this->backend->addShareSource($id, $source);

		// The modified share object has to be used instead of fetching the share again, because it would trigger the insertion of default values prematurely.
		$this->processShareUpdates([$share]);
	}

	#[\Override]
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		$this->backend->removeShareSource($id, $source);

		$this->processShareUpdates([$id]);
	}

	#[\Override]
	public function onSourceDeleted(ShareAccessContext $accessContext, ShareSource $source): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();

		$updatedIds = $this->backend->onSourceDeleted($source);
		if ($updatedIds === []) {
			return;
		}

		$this->backend->setLastUpdated($updatedIds, $timestamp);

		$this->processShareUpdates($updatedIds);
	}

	#[\Override]
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No current user provided in access context.');
		}

		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $owner);
			$share = null;
		} catch (ShareOperationForbiddenException) {
			$share = $this->getShare($accessContext, $id);
			$this->validatePermission($share, ReshareSharePermissionType::class);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
		}

		if (!$recipientType->validateRecipient($recipient->value)) {
			throw new ShareInvalidException('Invalid recipient: ' . $recipient->value . ' ' . $recipient->class . ' ' . ($recipient->instance ?? 'local'), $this->l10n->t('The recipient does not exist.'));
		}

		$share ??= $this->getShare($accessContext, $id);
		$recipients = $share->recipients;
		$recipients[] = $recipient;
		$share = new Share(
			$share->id,
			$share->owner,
			$share->lastUpdated,
			$share->state,
			$share->sources,
			$recipients,
			$share->properties,
			$share->permissions,
		);

		if (!$accessContext->overrideChecks) {
			$this->validateInteraction($accessContext, $share);
		}

		if ($recipient->secret === null || !$recipient->initiator instanceof ShareUser) {
			$secret = $recipient->secret ?? $this->generateSecret();
			$initiator = $recipient->initiator ?? new ShareUser($currentUser->getUID(), null);

			$recipient = new ShareRecipient(
				$recipient->class,
				$recipient->value,
				$recipient->instance,
				$secret,
				$initiator,
			);
		}

		$this->backend->addShareRecipient($id, $recipient);

		// The modified share object has to be used instead of fetching the share again, because it would trigger the insertion of default values prematurely.
		$this->processShareUpdates([$share]);
	}

	#[\Override]
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		$this->assertInTransaction();

		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $owner);
		} catch (ShareOperationForbiddenException) {
			$share = $this->getShare($accessContext, $id);
			// This does not allow removing own recipients. A user can only reject a share, but not remove it for the recipient.
			$this->validateReshareOperation($accessContext, $share, $recipient);
		}

		$this->backend->removeShareRecipient($id, $recipient);

		$this->processShareUpdates([$id]);
	}

	#[\Override]
	public function onRecipientDeleted(ShareAccessContext $accessContext, ShareRecipient $recipient): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();

		$updatedIds = $this->backend->onRecipientDeleted($recipient);
		if ($updatedIds === []) {
			return;
		}

		$this->backend->setLastUpdated($updatedIds, $timestamp);

		$this->processShareUpdates($updatedIds);
	}

	#[\Override]
	public function onInitiatorDeleted(ShareAccessContext $accessContext, ShareUser $initiator): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();

		$updatedIds = $this->backend->onInitiatorDeleted($initiator);
		if ($updatedIds === []) {
			return;
		}

		$this->backend->setLastUpdated($updatedIds, $timestamp);

		$this->processShareUpdates($updatedIds);
	}

	#[\Override]
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $owner);
		} catch (ShareOperationForbiddenException) {
			$share = $this->getShare($accessContext, $id);
			$this->validateReshareOperation($accessContext, $share, $recipient);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
		}

		if (!$recipientType instanceof IShareRecipientTypePublicSecret || !$recipientType->isSecretUpdatable($recipient->value)) {
			throw new ShareOperationForbiddenException();
		}

		if (!preg_match('/^[a-z0-9-]{1,32}$/i', $secret)) {
			throw new ShareInvalidException('Invalid secret: ' . $secret, $this->l10n->t('The value must be alphanumeric, 1 to 32 characters long and may contain dashes.'));
		}

		$this->backend->updateShareRecipientSecret($id, $recipient, $secret);

		$this->processShareUpdates([$id]);
	}

	#[\Override]
	public function createSharePropertyDefaultValue(Share $share, string $propertyTypeClass): Share {
		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();
		$this->backend->setLastUpdated([$share->id], $timestamp);

		if (($propertyType = $this->registry->getPropertyTypes()[$propertyTypeClass] ?? null) === null) {
			throw new RuntimeException('The property is not registered: ' . $propertyTypeClass);
		}

		$property = new ShareProperty($propertyTypeClass, $propertyType->getDefaultValue($share));

		$this->backend->createShareProperty($share->id, $property);

		$properties = $share->properties;
		$properties[$propertyTypeClass] = $property;

		$share = new Share(
			$share->id,
			$share->owner,
			$timestamp,
			$share->state,
			$share->sources,
			$share->recipients,
			$properties,
			$share->permissions,
		);

		[$share] = $this->processShareUpdates([$share]);

		return $share;
	}

	#[\Override]
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (($propertyType = $this->registry->getPropertyTypes()[$property->class] ?? null) === null) {
			throw new RuntimeException('The property is not registered: ' . $property->class);
		}

		if ($property->value !== null) {
			$share = $this->getShare($accessContext, $id);
			if (($message = $propertyType->validateValue($this->l10nFactory, $share, $property->value)) !== true) {
				throw new ShareInvalidException('Invalid property value: ' . $property->value . ' ' . $property->class, $message);
			}
		}

		$this->backend->updateShareProperty($id, $property);

		$this->processShareUpdates([$id]);
	}

	#[\Override]
	public function createSharePermissionDefaultValue(Share $share, string $permissionTypeClass): Share {
		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();
		$this->backend->setLastUpdated([$share->id], $timestamp);

		if (($permissionType = $this->registry->getPermissionTypes()[$permissionTypeClass] ?? null) === null) {
			throw new RuntimeException('The permission is not registered: ' . $permissionTypeClass);
		}

		$permission = new SharePermission($permissionTypeClass, $permissionType->isEnabledByDefault());

		$this->backend->createSharePermission($share->id, $permission);

		$permissions = $share->permissions;
		$permissions[$permissionTypeClass] = $permission;

		$share = new Share(
			$share->id,
			$share->owner,
			$timestamp,
			$share->state,
			$share->sources,
			$share->recipients,
			$share->properties,
			$permissions,
		);

		[$share] = $this->processShareUpdates([$share]);

		return $share;
	}

	#[\Override]
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (!isset($this->registry->getPermissionTypes()[$permission->class])) {
			throw new RuntimeException('The permission type is not registered: ' . $permission->class);
		}

		$share = $this->getShare($accessContext, $id);
		$permissions = $share->permissions;
		$permissions[$permission->class] = $permission;
		$share = new Share(
			$share->id,
			$share->owner,
			$share->lastUpdated,
			$share->state,
			$share->sources,
			$share->recipients,
			$share->properties,
			$permissions,
		);

		if (!$accessContext->overrideChecks) {
			$this->validateInteraction($accessContext, $share);
		}

		$this->backend->updateSharePermission($id, $permission);

		// The modified share object has to be used instead of fetching the share again, because it would trigger the insertion of default values prematurely.
		$this->processShareUpdates([$share]);
	}

	#[\Override]
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, string $permissionPresetClass): void {
		$this->assertInTransaction();

		$this->backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $this->backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (($this->registry->getPermissionPresetCompatiblePermissionTypeClasses()[$permissionPresetClass] ?? null) === null) {
			throw new RuntimeException('The permission preset is not registered: ' . $permissionPresetClass);
		}

		$this->backend->selectSharePermissionPreset($id, $permissionPresetClass);

		$this->processShareUpdates([$id]);
	}

	#[\Override]
	public function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$this->assertInTransaction();

		$owner = $this->backend->getShareOwner($id);

		// No need to update the last updated timestamp, because the share will be deleted anyway.

		$this->validateShareOwnerOperation($accessContext, $owner);

		$this->backend->deleteShare($id);

		$legacyBackend = $this->registry->getLegacyBackend();
		if ($legacyBackend instanceof ISharingLegacyBackend) {
			$legacyBackend->deleteShare($id);
		}
	}

	#[\Override]
	public function getShare(ShareAccessContext $accessContext, string $id): Share {
		$this->assertInTransaction();

		return $this->backend->getShare($accessContext, $id);
	}

	#[\Override]
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array {
		$this->assertInTransaction();

		return $this->backend->getShares($accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
	}

	#[\Override]
	public function handle(Event $event): void {
		$shareUser = new ShareUser($event->getUser()->getUID(), null);

		try {
			$this->dbConnection->beginTransaction();
			$this->onOwnerDeleted(new ShareAccessContext(overrideChecks: true), $shareUser);
			$this->onInitiatorDeleted(new ShareAccessContext(overrideChecks: true), $shareUser);
			$this->dbConnection->commit();
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}

	private function assertInTransaction(): void {
		if (!$this->dbConnection->inTransaction()) {
			throw new RuntimeException('The SharingManager can only be used inside a transaction.');
		}
	}

	// TODO: Support IShareOwnerlessMount

	/**
	 * @throws ShareOperationForbiddenException
	 */
	private function validateShareOwnerOperation(ShareAccessContext $accessContext, ShareUser $owner): void {
		if ($accessContext->overrideChecks) {
			return;
		}

		if ($owner->instance !== null || !$accessContext->currentUser instanceof IUser || $owner->userId !== $accessContext->currentUser->getUID()) {
			throw new ShareOperationForbiddenException();
		}
	}

	/**
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 * @throws ShareOperationForbiddenException
	 */
	private function validatePermission(Share $share, string $permissionTypeClass): void {
		// TODO: Only fetch permisions
		if ((($permission = $share->permissions[$permissionTypeClass] ?? null) !== null) && $permission->enabled) {
			return;
		}

		throw new ShareOperationForbiddenException();
	}

	/**
	 * @throws ShareOperationForbiddenException
	 */
	private function validateReshareOperation(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): void {
		$this->validatePermission($share, ReshareSharePermissionType::class);

		// TODO: Only fetch recipients
		foreach ($share->recipients as $shareRecipient) {
			if (
				$recipient->class === $shareRecipient->class
				&& $recipient->value === $shareRecipient->value
				&& $recipient->instance === $shareRecipient->instance
				&& $shareRecipient->initiator !== null
				&& $shareRecipient->initiator->isCurrentUser($accessContext)) {
				return;
			}
		}

		// We're only allowed to remove or update recipients, if we're the initiator.
		throw new ShareOperationForbiddenException();
	}

	/**
	 * @throws ShareInvalidException
	 */
	private function validateInteraction(ShareAccessContext $accessContext, Share $share): void {
		$action = new ShareAction(null, array_values(array_map(static fn (SharePermission $permission): string => $permission->class, $share->getEnabledPermissions())));

		$usersToCheck = [];
		if ($share->owner->instance === null && ($ownerUser = $this->userManager->get($share->owner->userId)) !== null) {
			$usersToCheck[] = $ownerUser;
		}

		if ($accessContext->currentUser instanceof IUser && !$share->owner->isCurrentUser($accessContext)) {
			$usersToCheck[] = $accessContext->currentUser;
		}

		$recipientTypes = $this->registry->getRecipientTypes();
		$sourceTypes = $this->registry->getSourceTypes();

		$receivers = [];
		foreach ($share->recipients as $recipient) {
			if (($recipientType = $recipientTypes[$recipient->class] ?? null) === null) {
				throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
			}

			if (!$recipientType->validateRecipient($recipient->value)) {
				continue;
			}

			$receivers[] = $recipientType->getRecipientInteractionReceiver($recipient->value);
		}

		foreach ($usersToCheck as $userToCheck) {
			$resources = [];
			foreach ($share->sources as $source) {
				if (($sourceType = $sourceTypes[$source->class] ?? null) === null) {
					throw new RuntimeException('The source type is not registered: ' . $source->class);
				}

				if (!$sourceType->validateSource($source->value)) {
					continue;
				}

				$resources[] = $sourceType->getSourceInteractionResource($userToCheck->getUID(), $source->value);
			}

			$event = new RestrictInteractionEvent($userToCheck->getUID(), $userToCheck, $resources, $action, $receivers);
			$isRestricted = $event->isInteractionRestricted();
			if ($isRestricted !== false) {
				throw new ShareInvalidException('Share interaction restricted.', $isRestricted);
			}
		}
	}

	/**
	 * @throws ShareInvalidException
	 */
	private function assertShareCanBeActive(Share $share): void {
		if ($share->sources === []) {
			throw new ShareInvalidException('No source set.', $this->l10n->t('You need to add at least one source to make the share available.'));
		}

		if ($share->recipients === []) {
			throw new ShareInvalidException('No recipient set.', $this->l10n->t('You need to add at least one recipient to make the share available.'));
		}

		if ($share->getEnabledPermissions() === []) {
			throw new ShareInvalidException('No permission given.', $this->l10n->t('You need to allow at least one permission to make the share available.'));
		}

		$propertyTypes = $this->registry->getPropertyTypes();
		foreach ($share->properties as $propertyTypeClass => $property) {
			$propertyType = $propertyTypes[$propertyTypeClass];
			if ($property->value === null && $propertyType->isRequired($share)) {
				throw new ShareInvalidException('Missing value for required property: ' . $propertyTypeClass, $this->l10n->t('You need to set a value for the %s', [$propertyType->getDisplayName($this->l10nFactory)]));
			}
		}
	}

	/**
	 * @param non-empty-list<Share|string> $sharesOrIds
	 * @return non-empty-list<Share>
	 */
	private function processShareUpdates(array $sharesOrIds): array {
		$shares = [];

		foreach ($sharesOrIds as $shareOrId) {
			if ($shareOrId instanceof Share) {
				$share = $shareOrId;
			} else {
				$share = $this->backend->getShare(new ShareAccessContext(overrideChecks: true), $shareOrId);
			}

			if ($share->state === ShareState::Active) {
				try {
					$this->assertShareCanBeActive($share);
				} catch (ShareInvalidException) {
					$this->backend->updateShareState($share->id, ShareState::Draft);

					$share = new Share(
						$share->id,
						$share->owner,
						$share->lastUpdated,
						ShareState::Draft,
						$share->sources,
						$share->recipients,
						$share->properties,
						$share->permissions,
					);
				}
			}

			$legacyBackend = $this->registry->getLegacyBackend();
			if ($legacyBackend instanceof ISharingLegacyBackend) {
				$compatibleSourceTypes = array_fill_keys($legacyBackend->getCompatibleSourceTypes(), true);
				foreach ($share->sources as $source) {
					if (!isset($compatibleSourceTypes[$source->class])) {
						throw new RuntimeException('The legacy backend ' . $legacyBackend::class . ' does not support this source type: ' . $source->class);
					}
				}

				$compatibleRecipientTypes = array_fill_keys($legacyBackend->getCompatibleRecipientTypes(), true);
				foreach ($share->recipients as $recipient) {
					if (!isset($compatibleRecipientTypes[$recipient->class])) {
						throw new RuntimeException('The legacy backend ' . $legacyBackend::class . ' does not support this recipient type: ' . $recipient->class);
					}
				}

				$legacyBackend->updateShare($share);
			}

			$shares[] = $share;
		}

		return $shares;
	}
}
