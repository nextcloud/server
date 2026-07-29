<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use NCU\Sharing\Permission\ISharePermissionPreset;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Source\IShareSourceType;
use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISharingRegistry {
	/**
	 * @experimental 35.0.0
	 */
	public function clear(): void;

	/**
	 * @experimental 35.0.0
	 */
	public function registerSharingBackend(ISharingBackend $backend): void;

	/**
	 * @return array<class-string<ISharingBackend>, ISharingBackend>
	 * @experimental 35.0.0
	 */
	public function getSharingBackends(): array;

	/**
	 * @experimental 35.0.0
	 */
	public function registerSourceType(IShareSourceType $sourceType): void;

	/**
	 * @return array<class-string<IShareSourceType>, IShareSourceType>
	 * @experimental 35.0.0
	 */
	public function getSourceTypes(): array;

	/**
	 * @return array<class-string<ISharePropertyType>, list<class-string<IShareSourceType>>>
	 * @experimental 35.0.0
	 */
	public function getPropertyTypeCompatibleSourceTypeClasses(): array;

	/**
	 * @experimental 35.0.0
	 */
	public function registerRecipientType(IShareRecipientType $recipientType): void;

	/**
	 * @return array<class-string<IShareRecipientType>, IShareRecipientType>
	 * @experimental 35.0.0
	 */
	public function getRecipientTypes(): array;

	/**
	 * @return array<class-string<ISharePropertyType>, list<class-string<IShareRecipientType>>>
	 * @experimental 35.0.0
	 */
	public function getPropertyTypeCompatibleRecipientTypes(): array;

	/**
	 * @experimental 35.0.0
	 */
	public function registerPropertyType(ISharePropertyType $propertyType): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @param class-string<IShareSourceType> $sourceTypeClass
	 * @experimental 35.0.0
	 */
	public function markPropertyTypeCompatibleWithSourceType(string $propertyTypeClass, string $sourceTypeClass): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @param class-string<IShareRecipientType> $recipientTypeClass
	 * @experimental 35.0.0
	 */
	public function markPropertyTypeCompatibleWithRecipientType(string $propertyTypeClass, string $recipientTypeClass): void;

	/**
	 * @return array<class-string<ISharePropertyType>, ISharePropertyType>
	 * @experimental 35.0.0
	 */
	public function getPropertyTypes(): array;

	/**
	 * @param class-string<IShareSourceType> $sourceTypeClass
	 * @experimental 35.0.0
	 */
	public function registerPermissionType(?string $sourceTypeClass, ISharePermissionType $permissionType): void;

	/**
	 * @return array<class-string<ISharePermissionType>, ISharePermissionType>
	 * @experimental 35.0.0
	 */
	public function getPermissionTypes(): array;

	/**
	 * @return array<class-string<ISharePermissionType>, ?class-string<IShareSourceType>>
	 * @experimental 35.0.0
	 */
	public function getPermissionTypeSourceTypeClass(): array;

	/**
	 * @return array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>>
	 * @experimental 35.0.0
	 */
	public function getSourceTypePermissionTypeClasses(): array;

	/**
	 * @return list<class-string<ISharePermissionType>>
	 * @experimental 35.0.0
	 */
	public function getGenericPermissionTypeClasses(): array;

	/**
	 * @experimental 35.0.0
	 */
	public function registerPermissionPreset(ISharePermissionPreset $permissionPreset): void;

	/**
	 * @return array<class-string<ISharePermissionPreset>, ISharePermissionPreset>
	 * @experimental 35.0.0
	 */
	public function getPermissionPresets(): array;

	/**
	 * @param class-string<ISharePermissionType> $permissionTypeClass
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass
	 * @experimental 35.0.0
	 */
	public function markPermissionTypeCompatibleWithPermissionPreset(string $permissionTypeClass, string $permissionPresetClass): void;

	/**
	 * @return array<class-string<ISharePermissionType>, list<class-string<ISharePermissionPreset>>>
	 * @experimental 35.0.0
	 */
	public function getPermissionTypeCompatiblePermissionPresetClasses(): array;

	/**
	 * @return array<class-string<ISharePermissionPreset>, non-empty-list<class-string<ISharePermissionType>>>
	 * @experimental 35.0.0
	 */
	public function getPermissionPresetCompatiblePermissionTypeClasses(): array;
}
