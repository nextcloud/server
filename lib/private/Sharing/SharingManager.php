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
use Psr\Clock\ClockInterface;
use Random\Randomizer;
use RuntimeException;

// TODO: Add accept/reject
// TODO: Add permission masking (reshares)
// TODO: Test sharing to federated users, groups and circles
// TODO: Implement share transfers
// TODO: Cache share owner

/**
 * @psalm-import-type SharingShare from Share
 * @template-implements IEventListener<BeforeUserDeletedEvent|SharesDefaultSetEvent>
 */
final readonly class SharingManager implements ISharingManager, IEventListener {
	private Randomizer $randomizer;

	private IL10N $l10n;

	public function __construct(
		IEventDispatcher $eventDispatcher,
		private IUserManager $userManager,
		private IFactory $l10nFactory,
		private ISnowflakeGenerator $snowflakeGenerator,
		private IDBConnection $dbConnection,
		private ISharingRegistry $registry,
		private ISharingBackend $backend,
		private ClockInterface $clock,
	) {
		$this->randomizer = new Randomizer();
		$this->l10n = $l10nFactory->get('sharing');

		$eventDispatcher->addServiceListener(BeforeUserDeletedEvent::class, self::class);
	}

	#[\Override]
	public function searchRecipients(
		ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?Share $forShare = null,
	): array {
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
			$recipientTypes = array_values(
				array_filter(
					$recipientTypes,
					static fn (IShareRecipientType $recipientType): bool => $recipientType instanceof IShareRecipientTypeSearch,
				)
			);
		}

		$results = array_merge(
			...array_map(
				static fn (IShareRecipientTypeSearch $recipientType): array => $recipientType->searchRecipients($accessContext, $query, $limit, $offset),
				$recipientTypes,
			)
		);

		if ($forShare instanceof Share) {
			// Do not create a new access context with overridden checks, because it could leak the existence of shares and share recipients.
			$recipients = [];
			foreach ($forShare->recipients as $recipient) {
				$recipients[$recipient->class] ??= [];
				$recipients[$recipient->class][$recipient->instance ?? ''] ??= [];
				$recipients[$recipient->class][$recipient->instance ?? ''][$recipient->value] = true;
			}

			$results = array_values(
				array_filter(
					$results,
					static fn (ShareRecipient $recipient): bool => !isset($recipients[$recipient->class][$recipient->instance ?? ''][$recipient->value])
				)
			);
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
	public function getTime(): \DateTimeImmutable {
		return $this->clock->now();
	}

	#[\Override]
	public function createShare(ShareAccessContext $accessContext): Share {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No user present to create a share');
		}

		$this->assertInTransaction();

		$id = $this->snowflakeGenerator->nextId();
		$lastUpdated = $this->getTime();
		$this->backend->createShare($id, new ShareUser($currentUser->getUID(), null), $lastUpdated);

		[$share] = $this->processShareUpdates([$id]);

		return $share;
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
	public function updateShareState(ShareAccessContext $accessContext, Share $share, ShareState $state): Share {
		$this->assertInTransaction();

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

		$this->validateShareEditPermissions($accessContext, $share);

		if ($state === ShareState::Active) {
			$this->assertShareCanBeActive($share);
		}

		$this->backend->updateShareState($share->id, $state);

		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$state,
			$share->sources,
			$share->recipients,
			$share->properties,
			$share->permissions,
		);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function addShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): Share {
		$this->assertInTransaction();

		// only the owner can add sources, otherwise a user could add sources others don't have access to, which would remove their access
		$this->validateShareEditPermissions($accessContext, $share, true);

		if (($sourceType = $this->registry->getSourceTypes()[$source->class] ?? null) === null) {
			throw new RuntimeException('The source type is not registered: ' . $source->class);
		}

		if (!$sourceType->validateSource($source->value)) {
			throw new ShareInvalidException('Invalid source: ' . $source->value . ' ' . $source->class, $this->l10n->t('The source does not exist.'));
		}

		$time = $this->getTime();

		$sources = $share->sources;
		$sources[] = $source;
		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$sources,
			$share->recipients,
			$share->properties,
			$share->permissions,
		);

		if (!$accessContext->overrideChecks) {
			$this->validateInteraction($accessContext, $share);
		}

		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->addShareSource($share->id, $source);

		[$share] = $this->backend->ensureDefaults([$share]);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function removeShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): Share {
		$this->assertInTransaction();

		// only the owner can remove sources, to mirror the "add source" permissions
		$this->validateShareEditPermissions($accessContext, $share, true);

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->removeShareSource($share->id, $source);

		$sources = array_values(array_filter($share->sources, static fn (ShareSource $shareSource): bool => !$shareSource->equals($source)));

		$sourceClasses = array_map(static fn (ShareSource $source): string => $source->class, $sources);
		$recipientClasses = array_map(static fn (ShareRecipient $recipient): string => $recipient->class, $share->recipients);
		$compatiblePropertyClasses = $this->registry->getCompatiblePropertyTypeClasses($sourceClasses, $recipientClasses);
		$compatiblePermissionsClasses = $this->registry->getCompatiblePermissionTypeClasses($sourceClasses);

		$properties = array_intersect_key($share->properties, array_flip($compatiblePropertyClasses));
		$permissions = array_intersect_key($share->permissions, array_flip($compatiblePermissionsClasses));

		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$sources,
			$share->recipients,
			$properties,
			$permissions,
		);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function onSourceDeleted(ShareAccessContext $accessContext, ShareSource $source): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->getTime();

		$updatedIds = $this->backend->onSourceDeleted($source);
		if ($updatedIds === []) {
			return;
		}

		$this->backend->setLastUpdated($updatedIds, $timestamp);

		$this->processShareUpdates($updatedIds);
	}

	#[\Override]
	public function addShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): Share {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No current user provided in access context.');
		}

		$this->assertInTransaction();

		try {
			$this->validateShareEditPermissions($accessContext, $share);
		} catch (ShareOperationForbiddenException) {
			$this->validatePermission($share, ReshareSharePermissionType::class);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
		}

		if (!$recipientType->validateRecipient($recipient->value)) {
			throw new ShareInvalidException(
				'Invalid recipient: ' . $recipient->value . ' ' . $recipient->class . ' ' . ($recipient->instance ?? 'local'),
				$this->l10n->t('The recipient does not exist.')
			);
		}

		$recipients = $share->recipients;
		$recipients[] = $recipient;
		$validationShare = new Share(
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
			$this->validateInteraction($accessContext, $validationShare);
		}

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

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

		$this->backend->addShareRecipient($share->id, $recipient);

		$recipients = $share->recipients;
		$recipients[] = $recipient;
		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$share->sources,
			$recipients,
			$share->properties,
			$share->permissions,
		);

		[$share] = $this->backend->ensureDefaults([$share]);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function removeShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): Share {
		$this->assertInTransaction();

		$this->assertInTransaction();

		try {
			$this->validateShareEditPermissions($accessContext, $share);
		} catch (ShareOperationForbiddenException) {
			// This does not allow removing own recipients. A user can only reject a share, but not remove it for the recipient.
			$this->validateReshareOperation($accessContext, $share, $recipient);
		}

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->removeShareRecipient($share->id, $recipient);

		$recipients = array_values(array_filter($share->recipients, static fn (ShareRecipient $shareRecipient): bool => !$shareRecipient->equals($recipient)));

		$sourceClasses = array_map(static fn (ShareSource $source): string => $source->class, $share->sources);
		$recipientClasses = array_map(static fn (ShareRecipient $recipient): string => $recipient->class, $recipients);
		$compatiblePropertyClasses = $this->registry->getCompatiblePropertyTypeClasses($sourceClasses, $recipientClasses);

		$properties = array_intersect_key($share->properties, array_flip($compatiblePropertyClasses));

		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$share->sources,
			$recipients,
			$properties,
			$share->permissions,
		);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function onRecipientDeleted(ShareAccessContext $accessContext, ShareRecipient $recipient): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->getTime();

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

		$timestamp = $this->getTime();

		$updatedIds = $this->backend->onInitiatorDeleted($initiator);
		if ($updatedIds === []) {
			return;
		}

		$this->backend->setLastUpdated($updatedIds, $timestamp);

		$this->processShareUpdates($updatedIds);
	}

	/**
	 * @psalm-assert-if-true non-empty-string $secret
	 */
	private function validateShareSecret(string $secret): bool {
		return (bool)preg_match('/^[a-z0-9-]{1,32}$/i', $secret);
	}

	#[\Override]
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, string $secret): Share {
		$this->assertInTransaction();

		try {
			$this->validateShareEditPermissions($accessContext, $share);
		} catch (ShareOperationForbiddenException) {
			$this->validateReshareOperation($accessContext, $share, $recipient);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
		}

		if (!$recipientType instanceof IShareRecipientTypePublicSecret || !$recipientType->isSecretUpdatable($recipient->value)) {
			throw new ShareOperationForbiddenException();
		}

		if (!$this->validateShareSecret($secret)) {
			throw new ShareInvalidException(
				'Invalid secret: ' . $secret, $this->l10n->t('The value must be alphanumeric, 1 to 32 characters long and may contain dashes.')
			);
		}

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->updateShareRecipientSecret($share->id, $recipient, $secret);

		$recipients = $share->recipients;
		foreach ($recipients as &$shareRecipient) {
			if ($shareRecipient->equals($recipient)) {
				$shareRecipient = new ShareRecipient(
					$recipient->class,
					$recipient->value,
					$recipient->instance,
					$secret,
					$shareRecipient->initiator,
				);
			}
		}

		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$share->sources,
			$recipients,
			$share->properties,
			$share->permissions,
		);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function updateShareProperty(ShareAccessContext $accessContext, Share $share, ShareProperty $property): Share {
		$this->assertInTransaction();

		$this->validateShareEditPermissions($accessContext, $share);

		if (($propertyType = $this->registry->getPropertyTypes()[$property->class] ?? null) === null) {
			throw new RuntimeException('The property is not registered: ' . $property->class);
		}

		if ($property->value !== null && ($message = $propertyType->validateValue($this->l10nFactory, $share, $property->value)) !== true) {
			throw new ShareInvalidException('Invalid property value: ' . $property->value . ' ' . $property->class, $message);
		}

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->updateShareProperty($share->id, $property);

		$properties = $share->properties;
		$properties[$property->class] = $property;

		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$share->sources,
			$share->recipients,
			$properties,
			$share->permissions,
		);

		[$share] = $this->processShareUpdates([$share->id]);
		return $share;
	}

	#[\Override]
	public function updateSharePermission(ShareAccessContext $accessContext, Share $share, SharePermission $permission): Share {
		$this->assertInTransaction();

		$this->validateShareEditPermissions($accessContext, $share);

		if (!isset($this->registry->getPermissionTypes()[$permission->class])) {
			throw new RuntimeException('The permission type is not registered: ' . $permission->class);
		}

		$time = $this->getTime();

		$permissions = $share->permissions;
		$permissions[$permission->class] = $permission;
		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$share->sources,
			$share->recipients,
			$share->properties,
			$permissions,
		);

		if (!$accessContext->overrideChecks) {
			$this->validateInteraction($accessContext, $share);
		}

		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->updateSharePermission($share->id, $permission);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, Share $share, string $permissionPresetClass): Share {
		$this->assertInTransaction();

		$this->validateShareEditPermissions($accessContext, $share);

		if (($this->registry->getPermissionPresetCompatiblePermissionTypeClasses()[$permissionPresetClass] ?? null) === null) {
			throw new RuntimeException('The permission preset is not registered: ' . $permissionPresetClass);
		}

		$time = $this->getTime();
		$this->backend->setLastUpdated([$share->id], $time);

		$this->backend->selectSharePermissionPreset($share->id, $permissionPresetClass);

		$sourceClasses = array_map(static fn (ShareSource $source): string => $source->class, $share->sources);
		$allPermissionClasses = $this->registry->getCompatiblePermissionTypeClasses($sourceClasses);
		$permissionPresetCompatiblePermissionTypeClasses = $this->registry->getPermissionPresetCompatiblePermissionTypeClasses()[$permissionPresetClass];
		$presetPermissions = array_combine($allPermissionClasses, array_map(fn (string $class): SharePermission => new SharePermission(
			$class,
			in_array($class, $permissionPresetCompatiblePermissionTypeClasses),
		), $allPermissionClasses));

		$share = new Share(
			$share->id,
			$share->owner,
			$time,
			$share->state,
			$share->sources,
			$share->recipients,
			$share->properties,
			$presetPermissions,
		);

		[$share] = $this->processShareUpdates([$share]);
		return $share;
	}

	#[\Override]
	public function deleteShare(ShareAccessContext $accessContext, Share $share): void {
		$this->assertInTransaction();

		// No need to update the last updated timestamp, because the share will be deleted anyway.

		$this->validateShareEditPermissions($accessContext, $share);

		$this->backend->deleteShare($share->id);

		$legacyBackend = $this->registry->getLegacyBackend();
		if ($legacyBackend instanceof ISharingLegacyBackend) {
			$legacyBackend->deleteShare($share->id);
		}
	}

	#[\Override]
	public function getShare(ShareAccessContext $accessContext, string $id): Share {
		$this->assertInTransaction();

		return $this->backend->getShare($accessContext, $id);
	}

	#[\Override]
	public function getShares(
		ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit,
	): array {
		$this->assertInTransaction();

		return $this->backend->getShares($accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof SharesDefaultSetEvent) {
			$shares = $event->getShares();
			$keys = array_keys($shares);
			$shares = $this->processShareUpdates(array_values($shares));
			$event->setShares(array_combine($keys, $shares));
		}

		if ($event instanceof BeforeUserDeletedEvent) {
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
	}

	private function assertInTransaction(): void {
		if (!$this->dbConnection->inTransaction()) {
			throw new RuntimeException('The SharingManager can only be used inside a transaction.');
		}
	}

	/**
	 * @throws ShareOperationForbiddenException
	 */
	private function validateShareEditPermissions(ShareAccessContext $accessContext, Share $share, bool $onlyOwner = false): void {
		if ($accessContext->overrideChecks) {
			return;
		}

		if ($share->owner->instance !== null || !$accessContext->currentUser instanceof IUser) {
			throw new ShareOperationForbiddenException();
		}

		if ($share->owner->userId === $accessContext->currentUser->getUID()) {
			return;
		}

		if ($onlyOwner) {
			throw new ShareOperationForbiddenException();
		}

		foreach ($share->sources as $source) {
			$sourceType = $this->registry->getSourceTypes()[$source->class] ?? null;
			if (!$sourceType) {
				throw new ShareOperationForbiddenException();
			}

			if (!$sourceType->userHasDirectSharingAccessToSource($accessContext->currentUser, $source->value)) {
				throw new ShareOperationForbiddenException();
			}
		}
	}

	/**
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 * @throws ShareOperationForbiddenException
	 */
	private function validatePermission(Share $share, string $permissionTypeClass): void {
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
		$action = new ShareAction(
			null, array_values(array_map(static fn (SharePermission $permission): string => $permission->class, $share->getEnabledPermissions()))
		);

		$usersToCheck = [];
		if ($share->owner->instance === null && ($ownerUser = $this->userManager->get($share->owner->userId)) instanceof IUser) {
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

				$resources[] = $sourceType->getSourceInteractionResource($userToCheck, $source->value);
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
				throw new ShareInvalidException(
					'Missing value for required property: ' . $propertyTypeClass,
					$this->l10n->t('You need to set a value for the %s', [$propertyType->getDisplayName($this->l10nFactory)])
				);
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
						throw new RuntimeException(
							'The legacy backend ' . $legacyBackend::class . ' does not support this recipient type: ' . $recipient->class
						);
					}
				}

				$legacyBackend->updateShare($share);
			}

			$shares[] = $share;
		}

		return $shares;
	}

	/**
	 * @return numeric-string
	 */
	public static function timeToMs(\DateTimeImmutable $time): string {
		$micros = method_exists($time, 'getMicrosecond') ? (float)$time->getMicrosecond() : (float)$time->format('u');

		$time = (string)floor((float)$time->getTimestamp() * 1000.0 + $micros / 1000.0);
		if ((float)$time > 0) {
			return $time;
		}

		throw new \RuntimeException('invalid date-time');
	}
}
