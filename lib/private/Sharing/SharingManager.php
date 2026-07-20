<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use Exception;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OCA\Sharing\SharingBackend;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareOperationForbiddenException;
use OCP\Sharing\ISharingBackend;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\IShareRecipientTypePublicSecret;
use OCP\Sharing\Recipient\IShareRecipientTypeSearch;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
use OCP\Sharing\Source\ShareSource;
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

	private ICache $backendCache;

	private IL10N $l10n;

	public function __construct(
		ICacheFactory $cacheFactory,
		IEventDispatcher $eventDispatcher,
		private IUserManager $userManager,
		private IFactory $l10nFactory,
		private IDBConnection $dbConnection,
		private ISharingRegistry $registry,
	) {
		$this->randomizer = new Randomizer();

		if ($cacheFactory->isAvailable()) {
			$this->backendCache = $cacheFactory->createDistributed('sharing');
		} elseif ($cacheFactory->isLocalCacheAvailable()) {
			$this->backendCache = $cacheFactory->createLocal('sharing');
		} else {
			$this->backendCache = $cacheFactory->createInMemory();
		}

		$eventDispatcher->addServiceListener(BeforeUserDeletedEvent::class, self::class);

		$this->l10n = $this->l10nFactory->get('sharing');
	}

	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, ?array $recipientTypeClasses, string $query, int $limit, int $offset): array {
		$recipientTypes = $this->registry->getRecipientTypes();

		if ($recipientTypeClasses !== null) {
			$filteredRecipientTypes = [];
			foreach (array_unique($recipientTypeClasses) as $recipientTypeClass) {
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

		$backend = $this->getBackend(null);
		$id = $backend->createShare($currentUser);
		$this->backendCache->set($id, $backend::class);

		return $id;
	}

	#[\Override]
	public function onOwnerDeleted(ShareAccessContext $accessContext, IUser $owner): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		// No need to update the last updated timestamp, because the share will be deleted anyway.

		foreach ($this->registry->getSharingBackends() as $backend) {
			$backend->onOwnerDeleted($owner);
		}
	}

	#[\Override]
	public function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if ($state === ShareState::Active) {
			$share = $this->getShare($accessContext, $id, $backend);
			$this->assertShareCanBeActive($share);
		}

		$backend->updateShareState($id, $state);
	}

	#[\Override]
	public function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (($sourceType = $this->registry->getSourceTypes()[$source->class] ?? null) === null) {
			throw new RuntimeException('The source type is not registered: ' . $source->class);
		}

		if (!$sourceType->validateSource($source->value)) {
			throw new ShareInvalidException('Invalid source: ' . $source->value . ' ' . $source->class, $this->l10n->t('The source does not exist.'));
		}

		$share = $this->getShare($accessContext, $id, $backend);
		$sources = $share->sources;
		$sources[] = $source;
		$this->validateInteraction($accessContext, $owner, $sources, $share->getEnabledPermissions(), $share->recipients);

		$backend->addShareSource($id, $source);
	}

	#[\Override]
	public function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		$backend->removeShareSource($id, $source);

		$this->makeSharesDraftIfNeeded([$id]);
	}

	#[\Override]
	public function onSourceDeleted(ShareAccessContext $accessContext, ShareSource $source): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();

		$ids = [];
		foreach ($this->registry->getSharingBackends() as $backend) {
			$updatedIds = $backend->onSourceDeleted($source);
			if ($updatedIds === []) {
				continue;
			}

			$backend->setLastUpdated($updatedIds, $timestamp);
			$ids[] = $updatedIds;
		}

		$this->makeSharesDraftIfNeeded(array_merge(...$ids));
	}

	#[\Override]
	public function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		if (!($currentUser = $accessContext->currentUser) instanceof IUser) {
			throw new RuntimeException('No current user provided in access context.');
		}

		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $owner);
			$share = null;
		} catch (ShareOperationForbiddenException) {
			$share = $this->getShare($accessContext, $id, $backend);
			$this->validatePermission($share, ReshareSharePermissionType::class);
		}

		if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
		}

		if (!$recipientType->validateRecipient($recipient->value)) {
			throw new ShareInvalidException('Invalid recipient: ' . $recipient->value . ' ' . $recipient->class . ' ' . ($recipient->instance ?? 'local'), $this->l10n->t('The recipient does not exist.'));
		}

		$share ??= $this->getShare($accessContext, $id, $backend);
		$recipients = $share->recipients;
		$recipients[] = $recipient;
		$this->validateInteraction($accessContext, $owner, $share->sources, $share->getEnabledPermissions(), $recipients);

		$backend->addShareRecipient($id, $currentUser, $recipient);
	}

	#[\Override]
	public function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): void {
		$this->assertInTransaction();

		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $owner);
		} catch (ShareOperationForbiddenException) {
			$share = $this->getShare($accessContext, $id, $backend);
			// This does not allow removing own recipients. A user can only reject a share, but not remove it for the recipient.
			$this->validateReshareOperation($accessContext, $share, $recipient);
		}

		$backend->removeShareRecipient($id, $recipient);

		$this->makeSharesDraftIfNeeded([$id]);
	}

	#[\Override]
	public function onRecipientDeleted(ShareAccessContext $accessContext, ShareRecipient $recipient): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();

		$ids = [];
		foreach ($this->registry->getSharingBackends() as $backend) {
			$updatedIds = $backend->onRecipientDeleted($recipient);
			if ($updatedIds === []) {
				continue;
			}

			$backend->setLastUpdated($updatedIds, $timestamp);
			$ids[] = $updatedIds;
		}

		$this->makeSharesDraftIfNeeded(array_merge(...$ids));
	}

	#[\Override]
	public function onInitiatorDeleted(ShareAccessContext $accessContext, IUser $initiator): void {
		if (!$accessContext->overrideChecks) {
			throw new RuntimeException('Only possible if checks are overridden.');
		}

		$this->assertInTransaction();

		$timestamp = $this->generateTimestamp();

		foreach ($this->registry->getSharingBackends() as $backend) {
			$updatedIds = $backend->onInitiatorDeleted($initiator);
			if ($updatedIds === []) {
				continue;
			}

			$backend->setLastUpdated($updatedIds, $timestamp);
		}

		// No need to make shares draft, because promoting a reshare to the owner doesn't remove recipients.
	}

	#[\Override]
	public function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);

		try {
			$this->validateShareOwnerOperation($accessContext, $owner);
		} catch (ShareOperationForbiddenException) {
			$share = $this->getShare($accessContext, $id, $backend);
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

		$backend->updateShareRecipientSecret($id, $recipient, $secret);
	}

	#[\Override]
	public function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (($propertyType = $this->registry->getPropertyTypes()[$property->class] ?? null) === null) {
			throw new RuntimeException('The property is not registered: ' . $property->class);
		}

		if ($property->value !== null && ($message = $propertyType->validateValue($this->l10nFactory, $property->value)) !== true) {
			throw new ShareInvalidException('Invalid property value: ' . $property->value . ' ' . $property->class, $message);
		}

		$backend->updateShareProperty($id, $property);

		$this->makeSharesDraftIfNeeded([$id]);
	}

	#[\Override]
	public function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (!isset($this->registry->getPermissionTypes()[$permission->class])) {
			throw new RuntimeException('The permission type is not registered: ' . $permission->class);
		}

		$share = $this->getShare($accessContext, $id, $backend);

		$permissions = $share->permissions;
		$permissions[$permission->class] = $permission;

		$this->validateInteraction($accessContext, $owner, $share->sources, array_filter($permissions, static fn (SharePermission $permission): bool => $permission->enabled), $share->recipients);

		$backend->updateSharePermission($id, $permission);

		$this->makeSharesDraftIfNeeded([$id]);
	}

	#[\Override]
	public function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, string $permissionPresetClass): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$backend->setLastUpdated([$id], $this->generateTimestamp());

		$owner = $backend->getShareOwner($id);
		$this->validateShareOwnerOperation($accessContext, $owner);

		if (($this->registry->getPermissionPresetCompatiblePermissionTypeClasses()[$permissionPresetClass] ?? null) === null) {
			throw new RuntimeException('The permission preset is not registered: ' . $permissionPresetClass);
		}

		$backend->selectSharePermissionPreset($id, $permissionPresetClass);
	}

	#[\Override]
	public function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$this->assertInTransaction();

		$backend = $this->getBackend($id);
		$owner = $backend->getShareOwner($id);

		// No need to update the last updated timestamp, because the share will be deleted anyway.

		$this->validateShareOwnerOperation($accessContext, $owner);

		$backend->deleteShare($id);
	}

	#[\Override]
	public function getShare(ShareAccessContext $accessContext, string $id, ?ISharingBackend $backend = null): Share {
		$this->assertInTransaction();

		return ($backend ?? $this->getBackend($id))->getShare($accessContext, $id);
	}

	#[\Override]
	public function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array {
		$this->assertInTransaction();

		$shares = [];

		// TODO: Deal with more results than limit?
		foreach ($this->registry->getSharingBackends() as $backend) {
			$backendShares = $backend->getShares($accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
			$shares[] = $backendShares;

			foreach ($backendShares as $share) {
				if ($this->backendCache->get($share->id) === null) {
					$this->backendCache->set($share->id, $backend::class);
				}
			}
		}

		return array_merge(...$shares);
	}

	#[\Override]
	public function handle(Event $event): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->onOwnerDeleted(new ShareAccessContext(overrideChecks: true), $event->getUser());
			$this->onInitiatorDeleted(new ShareAccessContext(overrideChecks: true), $event->getUser());
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

	private function getBackend(?string $id): ISharingBackend {
		$availableBackends = $this->registry->getSharingBackends();
		if ($availableBackends === []) {
			throw new RuntimeException('No sharing backends registered');
		}

		$selectedBackend = null;
		if ($id === null) {
			// For new shares we only use the backend from the sharing app.
			$selectedBackend = $availableBackends[SharingBackend::class] ?? null;
		} else {
			/** @var ?class-string<ISharingBackend> $cachedBackendClass */
			$cachedBackendClass = $this->backendCache->get($id);
			if ($cachedBackendClass !== null) {
				$selectedBackend = $availableBackends[$cachedBackendClass] ?? null;
			} else {
				foreach ($availableBackends as $backend) {
					if ($backend->hasShare($id)) {
						$selectedBackend = $backend;
						$this->backendCache->set($id, $backend::class);
						break;
					}
				}
			}
		}

		if ($selectedBackend === null) {
			throw new RuntimeException('No sharing backend selected');
		}

		return $selectedBackend;
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
	 * @param list<ShareSource> $sources
	 * @param array<class-string<ISharePermissionType>, SharePermission> $enabledPermissions
	 * @param list<ShareRecipient> $recipients
	 * @throws ShareInvalidException
	 */
	private function validateInteraction(ShareAccessContext $accessContext, ShareUser $owner, array $sources, array $enabledPermissions, array $recipients): void {
		$action = new ShareAction(null, array_values(array_map(static fn (SharePermission $permission): string => $permission->class, $enabledPermissions)));

		$usersToCheck = [];
		if ($owner->instance === null && ($ownerUser = $this->userManager->get($owner->userId)) !== null) {
			$usersToCheck[] = $ownerUser;
		}

		if ($accessContext->currentUser instanceof IUser && !$owner->isCurrentUser($accessContext)) {
			$usersToCheck[] = $accessContext->currentUser;
		}

		$recipientTypes = $this->registry->getRecipientTypes();
		$sourceTypes = $this->registry->getSourceTypes();

		$receivers = [];
		foreach ($recipients as $recipient) {
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
			foreach ($sources as $source) {
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
			if ($property->value === null && $propertyType->isRequired()) {
				throw new ShareInvalidException('Missing value for required property: ' . $propertyTypeClass, $this->l10n->t('You need to set a value for the %s', [$propertyType->getDisplayName($this->l10nFactory)]));
			}
		}
	}

	/**
	 * @param list<string> $ids
	 */
	private function makeSharesDraftIfNeeded(array $ids): void {
		foreach ($ids as $id) {
			$backend = $this->getBackend($id);
			$share = $backend->getShare(new ShareAccessContext(overrideChecks: true), $id);
			if ($share->state !== ShareState::Active) {
				continue;
			}

			try {
				$this->assertShareCanBeActive($share);
			} catch (ShareInvalidException) {
				$backend->updateShareState($id, ShareState::Draft);
			}
		}
	}
}
