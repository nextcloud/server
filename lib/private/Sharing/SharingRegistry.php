<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use OCP\Sharing\ISharingBackend;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\ISharePermissionPreset;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Source\IShareSourceType;
use RuntimeException;

// TODO: Maybe add validate method to run all checks before using the manager
final class SharingRegistry implements ISharingRegistry {
	/**
	 * @var array<class-string<ISharingBackend>, ISharingBackend>
	 */
	private array $backends = [];

	/** @var array<class-string<IShareSourceType>, IShareSourceType> */
	private array $sourceTypes = [];

	/** @var array<class-string<IShareRecipientType>, IShareRecipientType> */
	private array $recipientTypes = [];

	/** @var array<class-string<ISharePropertyType>, ISharePropertyType> */
	private array $propertyTypes = [];

	/** @var array<class-string<ISharePropertyType>, array<class-string<IShareSourceType>, true>> */
	private array $propertyTypeCompatibleSourceTypes = [];

	/** @var array<class-string<ISharePropertyType>, array<class-string<IShareRecipientType>, true>> */
	private array $propertyTypeCompatibleRecipientTypes = [];

	/** @var array<class-string<ISharePermissionType>, ISharePermissionType> */
	private array $permissionTypes = [];

	/** @var array<class-string<ISharePermissionType>, ?class-string<IShareSourceType>> */
	private array $permissionTypeSourceType = [];

	/** @var array<class-string<IShareSourceType>, list<class-string<ISharePermissionType>>> */
	private array $sourceTypePermissionTypes = [];

	/** @var list<class-string<ISharePermissionType>> */
	private array $genericPermissionTypes = [];

	/** @var array<class-string<ISharePermissionPreset>, ISharePermissionPreset> */
	private array $permissionPresets = [];

	/** @var array<class-string<ISharePermissionType>, array<class-string<ISharePermissionPreset>, true>> */
	private array $permissionTypeCompatiblePermissionPresets = [];

	/** @var array<class-string<ISharePermissionPreset>, array<class-string<ISharePermissionType>, true>> */
	private array $permissionPresetCompatiblePermissionTypes = [];

	#[\Override]
	public function clear(): void {
		$this->backends = [];
		$this->sourceTypes = [];
		$this->recipientTypes = [];
		$this->propertyTypes = [];
		$this->propertyTypeCompatibleSourceTypes = [];
		$this->propertyTypeCompatibleRecipientTypes = [];
		$this->permissionTypes = [];
		$this->permissionTypeSourceType = [];
		$this->sourceTypePermissionTypes = [];
		$this->genericPermissionTypes = [];
		$this->permissionPresets = [];
		$this->permissionTypeCompatiblePermissionPresets = [];
		$this->permissionPresetCompatiblePermissionTypes = [];
	}

	#[\Override]
	public function registerSharingBackend(ISharingBackend $backend): void {
		$class = $backend::class;

		if (isset($this->backends[$class])) {
			throw new RuntimeException('Sharing backend ' . $class . ' is already registered');
		}

		$this->backends[$class] = $backend;
	}

	/**
	 * @return array<class-string<ISharingBackend>, ISharingBackend>
	 */
	#[\Override]
	public function getSharingBackends(): array {
		return $this->backends;
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

		if ($propertyType->isRequired() && $propertyType->getDefaultValue() === null) {
			throw new RuntimeException('Share property type ' . $class . ' is required, but has no default value.');
		}

		if (isset($this->propertyTypes[$class])) {
			throw new RuntimeException('Share property type ' . $class . ' is already registered');
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
		foreach ($this->propertyTypes as $propertyType) {
			if (!isset($this->propertyTypeCompatibleSourceTypes[$propertyType::class])) {
				throw new RuntimeException('Share property type ' . $propertyType::class . ' has no compatible source types.');
			}

			if (!isset($this->propertyTypeCompatibleRecipientTypes[$propertyType::class])) {
				throw new RuntimeException('Share property type ' . $propertyType::class . ' has no compatible recipient types.');
			}
		}

		return $this->propertyTypes;
	}

	#[\Override]
	public function registerPermissionType(?string $sourceTypeClass, ISharePermissionType $permissionType): void {
		$class = $permissionType::class;
		if (isset($this->permissionTypes[$class])) {
			throw new RuntimeException('Share permission type ' . $class . ' is already registered');
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

	#[\Override]
	public function registerPermissionPreset(ISharePermissionPreset $permissionPreset): void {
		$class = $permissionPreset::class;
		if (isset($this->permissionPresets[$class])) {
			throw new RuntimeException('Share permission preset ' . $class . ' is already registered');
		}

		$this->permissionPresets[$class] = $permissionPreset;
	}

	/**
	 * @return array<class-string<ISharePermissionPreset>, ISharePermissionPreset>
	 */
	#[\Override]
	public function getPermissionPresets(): array {
		return $this->permissionPresets;
	}

	#[\Override]
	public function markPermissionTypeCompatibleWithPermissionPreset(string $permissionTypeClass, string $permissionPresetClass): void {
		// Because we can't control the order in which apps are booted, we can't ensure that the permission type and the permission preset are already registered.
		$this->permissionTypeCompatiblePermissionPresets[$permissionTypeClass] ??= [];
		$this->permissionTypeCompatiblePermissionPresets[$permissionTypeClass][$permissionPresetClass] = true;
		$this->permissionPresetCompatiblePermissionTypes[$permissionPresetClass] ??= [];
		$this->permissionPresetCompatiblePermissionTypes[$permissionPresetClass][$permissionTypeClass] = true;
	}

	/**
	 * @return array<class-string<ISharePermissionType>, list<class-string<ISharePermissionPreset>>>
	 */
	#[\Override]
	public function getPermissionTypeCompatiblePermissionPresetClasses(): array {
		return array_map(function (array $permissionPresetClasses): array {
			$permissionPresetClasses = array_keys($permissionPresetClasses);
			foreach ($permissionPresetClasses as $permissionPresetClass) {
				if (!isset($this->permissionPresets[$permissionPresetClass])) {
					// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
					throw new RuntimeException('Share permission preset ' . $permissionPresetClass . ' is not registered');
				}
			}

			return $permissionPresetClasses;
		}, $this->permissionTypeCompatiblePermissionPresets);
	}

	#[\Override]
	public function getPermissionPresetCompatiblePermissionTypeClasses(): array {
		$out = [];
		foreach ($this->permissionPresetCompatiblePermissionTypes as $permissionPresetClass => $permissionTypeClasses) {
			if ($permissionTypeClasses === []) {
				throw new RuntimeException('Share permission preset ' . $permissionPresetClass . ' has no compatibl permission types.');
			}

			$permissionTypeClasses = array_keys($permissionTypeClasses);
			foreach ($permissionTypeClasses as $permissionTypeClass) {
				if (!isset($this->permissionTypes[$permissionTypeClass])) {
					// Because we can't control the order in which apps are booted, we need to check now if it has been registered.
					throw new RuntimeException('Share permission type ' . $permissionTypeClass . ' is not registered');
				}
			}

			$out[$permissionPresetClass] = $permissionTypeClasses;
		}

		return $out;
	}
}
