<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Source\IShareSourceType;
use RuntimeException;

final class SharingRegistry implements ISharingRegistry {
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

	/** @var array<class-string<ISharePermissionType>, ISharePermissionType> */
	private array $permissionTypes = [];

	/** @var array<class-string<ISharePermissionType>, ?class-string<IShareSourceType>> */
	private array $permissionTypeSourceType = [];

	/** @var array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>> */
	private array $sourceTypePermissionTypes = [];

	/** @var list<class-string<ISharePermissionType>> */
	private array $genericPermissionTypes = [];

	#[\Override]
	public function clear(): void {
		$this->sourceTypes = [];
		$this->recipientTypes = [];
		$this->propertyTypes = [];
		$this->propertyTypeCompatibleSourceTypes = [];
		$this->propertyTypeCompatibleRecipientTypes = [];
		$this->permissionTypes = [];
		$this->permissionTypeSourceType = [];
		$this->sourceTypePermissionTypes = [];
		$this->genericPermissionTypes = [];
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

	/**
	 * @return array<class-string<ISharePropertyType>, list<class-string<IShareSourceType>>>
	 */
	#[\Override]
	public function getPropertyTypeCompatibleSourceTypeClasses(): array {
		return array_map(function (array $sourceTypeClasses): array {
			$sourceTypeClasses = array_keys($sourceTypeClasses);
			foreach ($sourceTypeClasses as $sourceTypeClass) {
				if (!isset($this->sourceTypes[$sourceTypeClass])) {
					// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
					throw new RuntimeException('Share source type ' . $sourceTypeClass . ' is not registered');
				}
			}

			return $sourceTypeClasses;
		}, $this->propertyTypeCompatibleSourceTypes);
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

	/**
	 * @return array<class-string<ISharePropertyType>, list<class-string<IShareRecipientType>>>
	 */
	#[\Override]
	public function getPropertyTypeCompatibleRecipientTypes(): array {
		return array_map(function (array $recipientTypeClasses): array {
			$recipientTypeClasses = array_keys($recipientTypeClasses);
			foreach ($recipientTypeClasses as $recipientTypeClass) {
				if (!isset($this->recipientTypes[$recipientTypeClass])) {
					// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
					throw new RuntimeException('Share recipient type ' . $recipientTypeClass . ' is not registered');
				}
			}

			return $recipientTypeClasses;
		}, $this->propertyTypeCompatibleRecipientTypes);
	}

	#[\Override]
	public function registerPropertyType(ISharePropertyType $propertyType): void {
		$class = $propertyType::class;

		if ($propertyType->getRequired() && $propertyType->getDefaultValue() === null) {
			throw new RuntimeException('Share property ' . $class . ' is required, but has no default value.');
		}

		if (isset($this->propertyTypes[$class])) {
			throw new RuntimeException('Share property ' . $class . ' is already registered');
		}

		$this->propertyTypes[$class] = $propertyType;
	}

	#[\Override]
	public function markPropertyTypeCompatibleWithSourceType(string $propertyTypeClass, string $sourceTypeClass): void {
		// Because we can't control the order in which apps are booted, we can't ensure that the source type is already registered.
		$this->propertyTypeCompatibleSourceTypes[$propertyTypeClass] ??= [];
		$this->propertyTypeCompatibleSourceTypes[$propertyTypeClass][$sourceTypeClass] = true;
	}

	#[\Override]
	public function markPropertyTypeCompatibleWithRecipientType(string $propertyTypeClass, string $recipientTypeClass): void {
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
	public function registerPermissionType(?string $sourceTypeClass, ISharePermissionType $permissionType): void {
		$class = $permissionType::class;
		if (isset($this->permissionTypes[$class])) {
			throw new RuntimeException('Share permission ' . $class . ' is already registered');
		}

		$this->permissionTypes[$class] = $permissionType;
		$this->permissionTypeSourceType[$class] = $sourceTypeClass;

		if ($sourceTypeClass !== null) {
			if (!isset($this->sourceTypes[$sourceTypeClass])) {
				throw new RuntimeException('Share source type ' . $sourceTypeClass . ' is not registered');
			}

			$this->sourceTypePermissionTypes[$sourceTypeClass] ??= [];
			$this->sourceTypePermissionTypes[$sourceTypeClass][] = $class;
		} else {
			$this->genericPermissionTypes[] = $class;
		}
	}

	/**
	 * @return array<class-string<ISharePermissionType>, ISharePermissionType>
	 */
	#[\Override]
	public function getPermissionTypes(): array {
		return $this->permissionTypes;
	}

	/**
	 * @return array<class-string<ISharePermissionType>, ?class-string<IShareSourceType>>
	 */
	#[\Override]
	public function getPermissionTypeSourceTypeClass(): array {
		return $this->permissionTypeSourceType;
	}

	/**
	 * @return array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>>
	 */
	#[\Override]
	public function getSourceTypePermissionTypeClasses(): array {
		return $this->sourceTypePermissionTypes;
	}

	/**
	 * @return list<class-string<ISharePermissionType>>
	 */
	#[\Override]
	public function getGenericPermissionTypeClasses(): array {
		return $this->genericPermissionTypes;
	}
}
