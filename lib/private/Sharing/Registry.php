<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use OCP\Sharing\IRegistry;
use OCP\Sharing\Permission\ISharePermissionCategoryType;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Source\IShareSourceType;
use RuntimeException;

final class Registry implements IRegistry {
	/** @var array<class-string<IShareSourceType>, IShareSourceType> */
	private array $sourceTypes = [];

	/** @var array<class-string<IShareRecipientType>, IShareRecipientType> */
	private array $recipientTypes = [];

	/** @var array<class-string<ISharePropertyType>, ISharePropertyType> */
	private array $propertyTypes = [];

	/** @var array<class-string<ISharePropertyType>, array<class-string<IShareSourceType>, bool>> */
	private array $propertyTypeCompatibleSourceTypes = [];

	/** @var array<class-string<ISharePropertyType>, array<class-string<IShareRecipientType>, bool>> */
	private array $propertyTypeCompatibleRecipientTypes = [];

	/** @var array<class-string<ISharePermissionCategoryType>, ISharePermissionCategoryType> */
	private array $permissionCategories = [];

	/** @var array<class-string<ISharePermissionType>, ISharePermissionType> */
	private array $permissions = [];

	/** @var array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>> */
	private array $sourceTypePermissions = [];

	/** @var array<class-string<ISharePermissionType>, class-string<IShareSourceType>> */
	private array $permissionSourceType = [];

	#[\Override]
	public function clear(): void {
		$this->sourceTypes = [];
		$this->recipientTypes = [];
		$this->propertyTypes = [];
		$this->propertyTypeCompatibleSourceTypes = [];
		$this->propertyTypeCompatibleRecipientTypes = [];
		$this->permissionCategories = [];
		$this->permissions = [];
		$this->sourceTypePermissions = [];
		$this->permissionSourceType = [];
	}

	#[\Override]
	public function registerSourceType(IShareSourceType $sourceType): void {
		$class = $sourceType::class;

		if (isset($this->sourceTypes[$class])) {
			throw new RuntimeException('Share source type ' . $class . ' is already registered');
		}

		$this->sourceTypes[$class] = $sourceType;
	}

	/**
	 * @return array<class-string<IShareSourceType>, IShareSourceType>
	 */
	#[\Override]
	public function getSourceTypes(): array {
		return $this->sourceTypes;
	}

	#[\Override]
	public function getSourceTypesCompatibleWithPropertyType(string $propertyTypeClass): array {
		$sourceTypeClasses = array_keys($this->propertyTypeCompatibleSourceTypes[$propertyTypeClass] ?? []);
		foreach ($sourceTypeClasses as $sourceTypeClass) {
			if (!isset($this->sourceTypes[$sourceTypeClass])) {
				// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
				throw new RuntimeException('Share source type ' . $sourceTypeClass . ' is not registered');
			}
		}

		return $sourceTypeClasses;
	}

	#[\Override]
	public function registerRecipientType(IShareRecipientType $recipientType): void {
		$class = $recipientType::class;

		if (isset($this->recipientTypes[$class])) {
			throw new RuntimeException('Share recipient type ' . $class . ' is already registered');
		}

		$this->recipientTypes[$class] = $recipientType;
	}

	/**
	 * @return array<class-string<IShareRecipientType>, IShareRecipientType>
	 */
	#[\Override]
	public function getRecipientTypes(): array {
		return $this->recipientTypes;
	}

	#[\Override]
	public function getRecipientTypesCompatibleWithPropertyType(string $propertyTypeClass): array {
		$recipientTypeClasses = array_keys($this->propertyTypeCompatibleRecipientTypes[$propertyTypeClass] ?? []);
		foreach ($recipientTypeClasses as $recipientTypeClass) {
			if (!isset($this->recipientTypes[$recipientTypeClass])) {
				// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
				throw new RuntimeException('Share recipient type ' . $recipientTypeClass . ' is not registered');
			}
		}

		return $recipientTypeClasses;
	}

	#[\Override]
	public function registerPropertyType(ISharePropertyType $propertyType): void {
		$class = $propertyType::class;

		if (isset($this->propertyTypes[$class])) {
			throw new RuntimeException('Share property ' . $class . ' is already registered');
		}

		$this->propertyTypes[$class] = $propertyType;
	}

	#[\Override]
	public function registerPropertyTypeCompatibleWithSourceType(string $propertyTypeClass, string $sourceTypeClass): void {
		// Because we can't control the order in which apps are booted, we can't ensure that the source type is already registered.
		$this->propertyTypeCompatibleSourceTypes[$propertyTypeClass] ??= [];
		$this->propertyTypeCompatibleSourceTypes[$propertyTypeClass][$sourceTypeClass] = true;
	}

	#[\Override]
	public function registerPropertyTypeCompatibleWithRecipientType(string $propertyTypeClass, string $recipientTypeClass): void {
		// Because we can't control the order in which apps are booted, we can't ensure that the source type is already registered.
		$this->propertyTypeCompatibleRecipientTypes[$propertyTypeClass] ??= [];
		$this->propertyTypeCompatibleRecipientTypes[$propertyTypeClass][$recipientTypeClass] = true;
	}

	/**
	 * @return array<class-string<ISharePropertyType>, ISharePropertyType>
	 */
	#[\Override]
	public function getPropertyTypes(): array {
		return $this->propertyTypes;
	}

	#[\Override]
	public function registerPermissionCategoryType(ISharePermissionCategoryType $permissionCategoryType): void {
		$class = $permissionCategoryType::class;

		if (isset($this->permissionCategories[$class])) {
			throw new RuntimeException('Share permission category ' . $class . ' is already registered');
		}

		$this->permissionCategories[$class] = $permissionCategoryType;
	}

	/**
	 * @return array<class-string<ISharePermissionCategoryType>, ISharePermissionCategoryType>
	 */
	#[\Override]
	public function getPermissionCategoryTypes(): array {
		return $this->permissionCategories;
	}

	#[\Override]
	public function registerPermissionType(string $sourceTypeClass, ISharePermissionType $permissionType): void {
		if (!isset($this->sourceTypes[$sourceTypeClass])) {
			throw new RuntimeException('Share source type ' . $sourceTypeClass . ' is not registered');
		}

		$class = $permissionType::class;
		if (isset($this->permissions[$class])) {
			throw new RuntimeException('Share permission ' . $class . ' is already registered');
		}

		$permissionCategoryClass = $permissionType->getCategory();
		if ($permissionCategoryClass !== null && !isset($this->permissionCategories[$permissionCategoryClass])) {
			throw new RuntimeException('Share permission category ' . $permissionCategoryClass . ' is not registered');
		}

		$this->permissions[$class] = $permissionType;
		$this->sourceTypePermissions[$sourceTypeClass] ??= [];
		$this->sourceTypePermissions[$sourceTypeClass][] = $class;
		$this->permissionSourceType[$class] = $sourceTypeClass;
	}

	/**
	 * @return array<class-string<ISharePermissionType>, ISharePermissionType>
	 */
	#[\Override]
	public function getPermissionTypes(): array {
		return $this->permissions;
	}

	/**
	 * @return array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>>
	 */
	#[\Override]
	public function getSourceTypePermissionTypes(): array {
		return $this->sourceTypePermissions;
	}

	/**
	 * @return array<class-string<ISharePermissionType>, class-string<IShareSourceType>>
	 */
	#[\Override]
	public function getPermissionTypeSourceType(): array {
		return $this->permissionSourceType;
	}
}
