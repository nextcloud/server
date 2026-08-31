<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use NCU\Sharing\Permission\ISharePermissionPreset;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSource;
use OC\Sharing\SharingManager;
use OCP\AppFramework\Attribute\Consumable;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;

/**
 * Keep the following types in sync with apps/sharing/lib/ResponseDefinitions.php:
 *
 * @psalm-type SharingIconSVG = array{
 *     // An SVG using the currentColor value for dynamic theming.
 *     svg: non-empty-string,
 * }
 *
 * @psalm-type SharingIconURL = array{
 *     // An absolute URL to an image suitable for light theme.
 *     light: non-empty-string,
 *     // An absolute URL to an image suitable for dark theme.
 *     dark: non-empty-string,
 * }
 *
 * @psalm-type SharingIcon = SharingIconSVG|SharingIconURL
 *
 * @psalm-type SharingSource = array{
 *     class: class-string<IShareSourceType>,
 *     value: non-empty-string,
 *     display_name: non-empty-string,
 *     icon: ?SharingIcon,
 * }
 *
 * @psalm-type SharingUser = array{
 *     user_id: non-empty-string,
 *     instance: ?non-empty-string,
 *     display_name: non-empty-string,
 *     icon: SharingIcon,
 * }
 *
 * @psalm-type SharingPermission = array{
 *     class: class-string<ISharePermissionType>,
 *     source_class: ?class-string<IShareSourceType>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 *     priority: int<1, 100>,
 *     presets: list<class-string<ISharePermissionPreset>>,
 *     enabled: bool,
 * }
 *
 * @psalm-type SharingRecipient = array{
 *     class: class-string<IShareRecipientType>,
 *     value: non-empty-string,
 *     instance: ?non-empty-string,
 *     display_name: non-empty-string,
 *     icon: ?SharingIcon,
 *     secret: array{
 *         updatable: bool,
 *         value?: non-empty-string,
 *         url?: non-empty-string,
 *     },
 *     initiator: ?SharingUser,
 *     permissions: list<SharingPermission>
 * }
 *
 * @psalm-type SharingState = 'active'|'draft'|'deleted'
 *
 * @psalm-type SharingUserStatus = 'pending'|'accepted'|'rejected'
 *
 * @psalm-type SharingProperty = array{
 *     class: class-string<ISharePropertyType>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 *     priority: int<1, 100>,
 *     required: bool,
 *     advanced: bool,
 *     value: ?string,
 * }
 *
 * @psalm-type SharingPropertyBoolean = SharingProperty&array{
 *     type: 'boolean',
 * }
 *
 * @psalm-type SharingPropertyDate = SharingProperty&array{
 *     type: 'date',
 *     // ISO 8601
 *     min_date: ?non-empty-string,
 *     // ISO 8601
 *     max_date: ?non-empty-string,
 * }
 *
 * @psalm-type SharingPropertyEnum = SharingProperty&array{
 *     type: 'enum',
 *     valid_values: non-empty-list<string>,
 * }
 *
 * @psalm-type SharingPropertyPassword = SharingProperty&array{
 *     type: 'password',
 * }
 *
 * @psalm-type SharingPropertyString = SharingProperty&array{
 *     type: 'string',
 *     min_length: ?positive-int,
 *     max_length: ?positive-int,
 * }
 *
 * @psalm-type SharingPermissionPreset = array{
 *     class: class-string<ISharePermissionPreset>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 * }
 *
 * @psalm-type SharingSourceType = array{
 *     class: class-string<IShareSourceType>,
 * }
 *
 * @psalm-type SharingShare = array{
 *     id: non-empty-string,
 *     owner: SharingUser,
 *     // Unix time in milliseconds
 *     last_updated: numeric-string,
 *     state: SharingState,
 *     user_status: ?SharingUserStatus,
 *     sources: list<SharingSource>,
 *     recipients: list<SharingRecipient>,
 *     properties: list<SharingPropertyDate|SharingPropertyEnum|SharingPropertyBoolean|SharingPropertyPassword|SharingPropertyString>,
 *     permissions: list<SharingPermission>,
 *     permission_preset: ?class-string<ISharePermissionPreset>,
 * }
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class Share {
	/** @var array<string, list<ShareRecipient>> $recipientsCache */
	private array $recipientsCache = [];

	/** @var array<string, array<class-string<ISharePermissionType>, SharePermission>> $enabledPermissionsCache */
	private array $enabledPermissionsCache = [];

	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		/** @var non-empty-string $id */
		public readonly string $id,
		public readonly ShareUser $owner,
		public readonly \DateTimeImmutable $lastUpdated,
		public readonly ShareState $state,
		public readonly ?ShareUserStatus $userStatus,
		/** @var list<ShareSource> $sources */
		public readonly array $sources,
		/** @var list<ShareRecipient> $recipients */
		public readonly array $recipients,
		/** @var array<class-string<ISharePropertyType>, ShareProperty> $properties */
		public readonly array $properties,
		/** @var array<class-string<ISharePermissionType>, SharePermission> $permissions */
		public readonly array $permissions,
	) {
	}

	/**
	 * @return list<ShareRecipient>
	 * @experimental 35.0.0
	 */
	public function getEffectiveRecipients(ShareAccessContext $accessContext): array {
		$hash = $accessContext->getHash();
		if (($recipients = $this->recipientsCache[$hash] ?? null) !== null) {
			return $recipients;
		}

		$registry = Server::get(ISharingRegistry::class);

		/** @var array<class-string<IShareRecipientType>, list<string>> $recipientTypeValues */
		$recipientTypeValues = [];
		foreach ($registry->getRecipientTypes() as $recipientType) {
			$recipientValues = $recipientType->getRecipients($accessContext->currentUser, $accessContext->arguments[$recipientType::class] ?? null);
			if ($recipientValues !== []) {
				$recipientTypeValues[$recipientType::class] = $recipientValues;
			}
		}

		/** @var list<ShareRecipient> $recipients */
		$recipients = [];
		foreach ($this->recipients as $recipient) {
			if (
				// Remote recipient can only be accessed through their secret
				($accessContext->secret !== null && $accessContext->secret === $recipient->secret)
				// Recipient values are only valid for local recipients
				|| ($recipient->instance === null && in_array($recipient->value, $recipientTypeValues[$recipient->class] ?? [], true))) {
				$recipients[] = $recipient;
			}
		}

		return $this->recipientsCache[$hash] = $recipients;
	}

	/**
	 * @return array<class-string<ISharePermissionType>, SharePermission>
	 * @experimental 35.0.0
	 */
	public function getEffectiveEnabledPermissions(ShareAccessContext $accessContext): array {
		$hash = $accessContext->getHash();
		if (($enabledPermissions = $this->enabledPermissionsCache[$hash] ?? null) !== null) {
			return $enabledPermissions;
		}

		$enabledPermissions = array_filter($this->permissions, static fn (SharePermission $permission): bool => $permission->enabled);

		if (!$accessContext->overrideChecks && !$this->owner->isCurrentUser($accessContext)) {
			/** @var array<class-string<ISharePermissionType>, SharePermission> $recipientsEnabledPermissions */
			$recipientsEnabledPermissions = [];
			foreach ($this->getEffectiveRecipients($accessContext) as $recipient) {
				foreach ($recipient->permissions as $permission) {
					// If any recipient has the permission enabled, we grant it.
					if (!isset($recipientsEnabledPermissions[$permission->class]) || ($permission->enabled && !$recipientsEnabledPermissions[$permission->class]->enabled)) {
						$recipientsEnabledPermissions[$permission->class] = $permission;
					}
				}
			}

			foreach ($recipientsEnabledPermissions as $permission) {
				// If the permission was disabled by any recipient and not enabled by another, we deny it.
				if (!$permission->enabled) {
					unset($enabledPermissions[$permission->class]);
				}
			}
		}

		return $this->enabledPermissionsCache[$hash] = $enabledPermissions;
	}

	/**
	 * @return SharingShare
	 * @experimental 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory, IURLGenerator $urlGenerator, IUserManager $userManager, ShareAccessContext $accessContext): array {
		$registrySourceTypePermissionTypeClasses = $registry->getSourceTypePermissionTypeClasses();
		$registryGenericPermissionTypeClasses = $registry->getGenericPermissionTypeClasses();
		$registryPermissionTypeCompatiblePermissionPresetClasses = $registry->getPermissionTypeCompatiblePermissionPresetClasses();

		/** @var array<class-string<ISharePermissionType>, bool> $compatiblePermissionTypeClasses */
		$compatiblePermissionTypeClasses = [];
		foreach ($registryGenericPermissionTypeClasses as $permissionTypeClass) {
			$compatiblePermissionTypeClasses[$permissionTypeClass] = true;
		}

		foreach ($this->sources as $source) {
			if (isset($registrySourceTypePermissionTypeClasses[$source->class])) {
				foreach ($registrySourceTypePermissionTypeClasses[$source->class] as $permissionTypeClass) {
					$compatiblePermissionTypeClasses[$permissionTypeClass] = true;
				}
			}
		}

		$selectedPermissionPresetClass = null;

		$enabledPermissionTypeClasses = array_values(array_map(static fn (SharePermission $permission): string => $permission->class, $this->getEffectiveEnabledPermissions($accessContext)));
		sort($enabledPermissionTypeClasses);

		$requiredPermissionTypeClasses = [];
		foreach ($registry->getPermissionTypes() as $permissionType) {
			// Only consider permissions that are compatible with the sources.
			if (!isset($compatiblePermissionTypeClasses[$permissionType::class])) {
				continue;
			}

			foreach ($registryPermissionTypeCompatiblePermissionPresetClasses[$permissionType::class] ?? [] as $permissionPresetClass) {
				$requiredPermissionTypeClasses[$permissionPresetClass][] = $permissionType::class;
			}
		}

		foreach ($requiredPermissionTypeClasses as $permissionPresetClass => $requiredPermissions) {
			if (count($enabledPermissionTypeClasses) !== count($requiredPermissions)) {
				continue;
			}

			sort($requiredPermissions);

			if ($enabledPermissionTypeClasses === $requiredPermissions) {
				$selectedPermissionPresetClass = $permissionPresetClass;
				break;
			}
		}

		return [
			'id' => $this->id,
			'owner' => $this->owner->format($userManager),
			'last_updated' => SharingManager::timeToMs($this->lastUpdated),
			'state' => $this->state->value,
			'user_status' => $this->userStatus?->value,
			'sources' => ShareSource::formatMultiple($registry, $l10nFactory, $this->sources),
			'recipients' => ShareRecipient::formatMultiple($registry, $l10nFactory, $urlGenerator, $userManager, $this->recipients),
			'properties' => ShareProperty::formatMultiple($registry, $l10nFactory, $this, array_values($this->properties)),
			'permissions' => SharePermission::formatMultiple($registry, $l10nFactory, array_values($this->permissions)),
			'permission_preset' => $selectedPermissionPresetClass,
		];
	}

	/**
	 * @param list<self> $shares
	 * @return list<SharingShare>
	 * @experimental 35.0.0
	 */
	public static function formatMultiple(ISharingRegistry $registry, IFactory $l10nFactory, IURLGenerator $urlGenerator, IUserManager $userManager, ShareAccessContext $accessContext, array $shares): array {
		// First sort by number of enabled permissions and then sort by share id to get a stable order regardless of the DB order
		usort($shares, static fn (Share $a, Share $b): int => 2 * (count($b->getEffectiveEnabledPermissions($accessContext)) <=> count($a->getEffectiveEnabledPermissions($accessContext))) + ($a->id <=> $b->id));
		return array_map(static fn (Share $share): array => $share->format($registry, $l10nFactory, $urlGenerator, $userManager, $accessContext), $shares);
	}
}
