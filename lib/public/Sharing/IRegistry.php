<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Sharing\Permission\ISharePermissionCategoryType;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Source\IShareSourceType;

/**
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
interface IRegistry {

	public function clear(): void;

	public function registerSourceType(IShareSourceType $sourceType): void;

	/**
	 * @return array<class-string<IShareSourceType>, IShareSourceType>
	 */
	public function getSourceTypes(): array;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @return list<class-string<IShareSourceType>>
	 */
	public function getSourceTypesCompatibleWithPropertyType(string $propertyTypeClass): array;

	public function registerRecipientType(IShareRecipientType $recipientType): void;


	/**
	 * @return array<class-string<IShareRecipientType>, IShareRecipientType>
	 */
	public function getRecipientTypes(): array;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @return list<class-string<IShareRecipientType>>
	 */
	public function getRecipientTypesCompatibleWithPropertyType(string $propertyTypeClass): array;

	public function registerPropertyType(ISharePropertyType $propertyType): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @param class-string<IShareSourceType> $sourceTypeClass
	 */
	public function registerPropertyTypeCompatibleWithSourceType(string $propertyTypeClass, string $sourceTypeClass): void;

	/**
	 * @param class-string<ISharePropertyType> $propertyTypeClass
	 * @param class-string<IShareRecipientType> $recipientTypeClass
	 */
	public function registerPropertyTypeCompatibleWithRecipientType(string $propertyTypeClass, string $recipientTypeClass): void;

	/**
	 * @return array<class-string<ISharePropertyType>, ISharePropertyType>
	 */
	public function getPropertyTypes(): array;

	public function registerPermissionCategoryType(ISharePermissionCategoryType $permissionCategoryType): void;

	/**
	 * @return array<class-string<ISharePermissionCategoryType>, ISharePermissionCategoryType>
	 */
	public function getPermissionCategoryTypes(): array;

	/**
	 * @param class-string<IShareSourceType> $sourceTypeClass
	 */
	public function registerPermissionType(string $sourceTypeClass, ISharePermissionType $permissionType): void;

	/**
	 * @return array<class-string<ISharePermissionType>, ISharePermissionType>
	 */
	public function getPermissionTypes(): array;

	/**
	 * @return array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>>
	 */
	public function getSourceTypePermissionTypes(): array;

	/**
	 * @return array<class-string<ISharePermissionType>, class-string<IShareSourceType>>
	 */
	public function getPermissionTypeSourceType(): array;
}
