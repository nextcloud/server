<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Sharing\Permission\ISharePermissionPreset;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Source\IShareSourceType;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISharingRegistry {
	/**
	 * @since 35.0.0
	 */
	public function clear(): void;

	/**
	 * @since 35.0.0
	 */
	public function registerSharingBackend(ISharingBackend $backend): void;

	/**
	 * @return array<class-string<ISharingBackend>, ISharingBackend>
	 * @since 35.0.0
	 */
	public function getSharingBackends(): array;

	/**
	 * @since 35.0.0
	 */
	public function registerSourceType(IShareSourceType $sourceType): void;

	/**
	 * @return array<class-string<IShareSourceType>, IShareSourceType>
	 * @since 35.0.0
	 */
	public function getSourceTypes(): array;

	/**
	 * @return array<class-string<ISharePropertyType>, list<class-string<IShareSourceType>>>
	 * @since 35.0.0
	 */
	public function getPropertyTypeCompatibleSourceTypeClasses(): array;

	/**
	 * @since 35.0.0
	 */
	public function registerRecipientType(IShareRecipientType $recipientType): void;

	/**
	 * @return array<class-string<IShareRecipientType>, IShareRecipientType>
	 * @since 35.0.0
	 */
	public function getRecipientTypes(): array;

	/**
	 * @return array<class-string<ISharePropertyType>, list<class-string<IShareRecipientType>>>
	 * @since 35.0.0
	 */
	public function getPropertyTypeCompatibleRecipientTypes(): array;

	/**
	 * @since 35.0.0
	 */
	public function registerPropertyType(ISharePropertyType $propertyType): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @param class-string<IShareSourceType> $sourceTypeClass
	 * @since 35.0.0
	 */
	public function markPropertyTypeCompatibleWithSourceType(string $propertyTypeClass, string $sourceTypeClass): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @param class-string<IShareRecipientType> $recipientTypeClass
	 * @since 35.0.0
	 */
	public function markPropertyTypeCompatibleWithRecipientType(string $propertyTypeClass, string $recipientTypeClass): void;

	/**
	 * @return array<class-string<ISharePropertyType>, ISharePropertyType>
	 * @since 35.0.0
	 */
	public function getPropertyTypes(): array;

	/**
	 * @param class-string<IShareSourceType> $sourceTypeClass
	 * @since 35.0.0
	 */
	public function registerPermissionType(?string $sourceTypeClass, ISharePermissionType $permissionType): void;

	/**
	 * @return array<class-string<ISharePermissionType>, ISharePermissionType>
	 * @since 35.0.0
	 */
	public function getPermissionTypes(): array;

	/**
	 * @return array<class-string<ISharePermissionType>, ?class-string<IShareSourceType>>
	 * @since 35.0.0
	 */
	public function getPermissionTypeSourceTypeClass(): array;

	/**
	 * @return array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>>
	 * @since 35.0.0
	 */
	public function getSourceTypePermissionTypeClasses(): array;

	/**
	 * @return list<class-string<ISharePermissionType>>
	 * @since 35.0.0
	 */
	public function getGenericPermissionTypeClasses(): array;

	/**
	 * @since 35.0.0
	 */
	public function registerPermissionPreset(ISharePermissionPreset $permissionPreset): void;

	/**
	 * @return array<class-string<ISharePermissionPreset>, ISharePermissionPreset>
	 * @since 35.0.0
	 */
	public function getPermissionPresets(): array;

	/**
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @since 35.0.0
	 */
	public function markPermissionTypeCompatibleWithPermissionPreset(string $permissionTypeClass, string $permissionPresetClass): void;

	/**
	 * @return array<class-string<ISharePermissionType>, list<class-string<ISharePermissionPreset>>>
	 * @since 35.0.0
	 */
	public function getPermissionTypeCompatiblePermissionPresetClasses(): array;

	/**
	 * @return array<class-string<ISharePermissionPreset>, non-empty-list<class-string<ISharePermissionType>>>
	 * @since 35.0.0
	 */
	public function getPermissionPresetCompatiblePermissionTypeClasses(): array;
}
