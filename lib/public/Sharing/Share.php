<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Permission\SharePermissionPreset;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

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
 * }
 *
 * @psalm-type SharingState = 'active'|'draft'|'deleted'
 *
 * @psalm-type SharingProperty = array{
 *     class: class-string<ISharePropertyType>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 *     priority: int<1, 100>,
 *     required: bool,
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
 * @psalm-type SharingPermissionPreset = 'view'|'edit'
 *
 * @psalm-type SharingPermission = array{
 *     class: class-string<ISharePermissionType>,
 *     source_class: ?class-string<IShareSourceType>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 *     presets: list<SharingPermissionPreset>,
 *     enabled: bool,
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
 *     last_updated: non-negative-int,
 *     state: SharingState,
 *     sources: list<SharingSource>,
 *     recipients: list<SharingRecipient>,
 *     properties: list<SharingPropertyDate|SharingPropertyEnum|SharingPropertyBoolean|SharingPropertyPassword|SharingPropertyString>,
 *     permissions: list<SharingPermission>,
 *     permission_preset: ?SharingPermissionPreset,
 * }
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class Share {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		/** @var non-empty-string $id */
		public string $id,
		public ShareUser $owner,
		/** @var non-negative-int $lastUpdated Unix time in milliseconds */
		public int $lastUpdated,
		public ShareState $state,
		/** @var list<ShareSource> $sources */
		public array $sources,
		/** @var list<ShareRecipient> $recipients */
		public array $recipients,
		/** @var array<class-string<ISharePropertyType>, ShareProperty> $properties */
		public array $properties,
		/** @var array<class-string<ISharePermissionType>, SharePermission> $permissions */
		public array $permissions,
	) {
	}

	/**
	 * @return SharingShare
	 * @since 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory, IURLGenerator $urlGenerator, IUserManager $userManager): array {
		$properties = array_map(static fn (ShareProperty $property): array => $property->format($registry, $l10nFactory), array_values($this->properties));
		usort($properties, static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

		$registrySourceTypePermissionTypeClasses = $registry->getSourceTypePermissionTypeClasses();
		$registryGenericPermissionTypeClasses = $registry->getGenericPermissionTypeClasses();

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

		$permissionPreset = null;

		$enabledPermissionTypeClasses = array_values(array_map(static fn (SharePermission $permission): string => $permission->class, array_filter($this->permissions, static fn (SharePermission $permission): bool => $permission->enabled)));
		sort($enabledPermissionTypeClasses);

		$requiredPermissionTypeClasses = [
			SharePermissionPreset::Edit->value => [],
			SharePermissionPreset::View->value => [],
		];
		foreach ($registry->getPermissionTypes() as $permissionType) {
			// Only consider permissions that are compatible with the sources.
			if (!isset($compatiblePermissionTypeClasses[$permissionType::class])) {
				continue;
			}

			foreach ($permissionType->getPresets() as $preset) {
				$requiredPermissionTypeClasses[$preset->value][] = $permissionType::class;
			}
		}

		foreach ($requiredPermissionTypeClasses as $preset => $requiredPermissions) {
			// None of the required permissions for this preset are compatible with this share.
			if ($requiredPermissions === []) {
				continue;
			}

			// Don't even compare the permissions if less are enabled anyway.
			if (count($this->permissions) < count($requiredPermissions)) {
				continue;
			}

			sort($requiredPermissions);

			if ($enabledPermissionTypeClasses === $requiredPermissions) {
				$permissionPreset = $preset;
				break;
			}
		}

		return [
			'id' => $this->id,
			'owner' => $this->owner->format($userManager),
			'last_updated' => $this->lastUpdated,
			'state' => $this->state->value,
			'sources' => ShareSource::formatMultiple($registry, $l10nFactory, $this->sources),
			'recipients' => ShareRecipient::formatMultiple($registry, $l10nFactory, $urlGenerator, $userManager, $this->recipients),
			'properties' => $properties,
			'permissions' => array_map(static fn (SharePermission $permission): array => $permission->format($registry, $l10nFactory), array_values($this->permissions)),
			'permission_preset' => $permissionPreset,
		];
	}
}
