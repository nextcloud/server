<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Permission;

use NCU\Permission\IPermission;
use NCU\Permission\IPermissionManager;
use NCU\Permission\IPermissionResourceType;
use NCU\Permission\PermissionBinding;
use NCU\Permission\ResolvedPermission;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PermissionManager implements IPermissionManager {
	private const ID_PATTERN = '/^[a-z0-9_]+:[a-z0-9_.]+$/';

	/** @var array<class-string<IPermission>, true> */
	private array $permissionClasses = [];

	/** @var array<class-string<IPermissionResourceType>, true> */
	private array $resourceTypeClasses = [];

	private bool $loaded = false;

	/** @var array<non-empty-string, IPermission> */
	private array $permissions = [];

	/** @var array<non-empty-string, IPermissionResourceType> */
	private array $resourceTypes = [];

	/** @var array<non-empty-string, list<ResolvedPermission>> */
	private array $resolvedPermissions = [];

	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function registerPermission(string $class): void {
		$this->permissionClasses[$class] = true;
		$this->loaded = false;
	}

	#[\Override]
	public function registerResourceType(string $class): void {
		$this->resourceTypeClasses[$class] = true;
		$this->loaded = false;
	}

	#[\Override]
	public function getPermissions(): array {
		$this->load();
		return $this->permissions;
	}

	#[\Override]
	public function getPermission(string $id): ?IPermission {
		$this->load();
		return $this->permissions[$id] ?? null;
	}

	#[\Override]
	public function getResourceTypes(): array {
		$this->load();
		return $this->resourceTypes;
	}

	#[\Override]
	public function getResourceType(string $id): ?IPermissionResourceType {
		$this->load();
		return $this->resourceTypes[$id] ?? null;
	}

	#[\Override]
	public function getPermissionsForResourceType(string $resourceTypeId): array {
		$this->load();
		return $this->resolvedPermissions[$resourceTypeId] ?? [];
	}

	#[\Override]
	public function getResourceTypesForPermission(string $permissionId): array {
		$this->load();

		$result = [];
		foreach ($this->resolvedPermissions as $resolvedPermissions) {
			foreach ($resolvedPermissions as $resolvedPermission) {
				if ($resolvedPermission->permission->getId() === $permissionId) {
					$result[] = $resolvedPermission;
				}
			}
		}

		return $result;
	}

	private function load(): void {
		if ($this->loaded) {
			return;
		}
		$this->loaded = true;

		$this->permissions = $this->loadPermissions();
		$this->resourceTypes = $this->loadResourceTypes();

		$this->resolvedPermissions = [];
		foreach ($this->resourceTypes as $resourceTypeId => $resourceType) {
			$this->resolvedPermissions[$resourceTypeId] = $this->resolvePermissions($resourceType);
		}
	}

	/**
	 * @return array<non-empty-string, IPermission>
	 */
	private function loadPermissions(): array {
		$permissions = [];
		foreach (array_keys($this->permissionClasses) as $class) {
			$permission = $this->instantiate($class, IPermission::class);
			if ($permission === null) {
				continue;
			}

			$id = $permission->getId();
			if (!$this->isValidId($id, 'permission', $class) || !$this->isUnique($id, $permissions, 'permission', $class)) {
				continue;
			}

			$defaultValueValid = $permission->validateValue($permission->getDefaultValue());
			if ($defaultValueValid !== true) {
				$this->logger->error('Permission {id} has an invalid default value: {error}', ['id' => $id, 'error' => $defaultValueValid]);
				continue;
			}

			$permissions[$id] = $permission;
		}

		foreach ($permissions as $id => $permission) {
			foreach ($permission->getRequiredPermissionIds() as $requiredId) {
				if (!isset($permissions[$requiredId])) {
					$this->logger->warning('Permission {id} requires the unknown permission {requiredId}', ['id' => $id, 'requiredId' => $requiredId]);
				}
			}
		}

		uasort($permissions, self::comparePermissions(...));
		return $permissions;
	}

	/**
	 * @return array<non-empty-string, IPermissionResourceType>
	 */
	private function loadResourceTypes(): array {
		$resourceTypes = [];
		foreach (array_keys($this->resourceTypeClasses) as $class) {
			$resourceType = $this->instantiate($class, IPermissionResourceType::class);
			if ($resourceType === null) {
				continue;
			}

			$id = $resourceType->getId();
			if (!$this->isValidId($id, 'resource type', $class) || !$this->isUnique($id, $resourceTypes, 'resource type', $class)) {
				continue;
			}

			$resourceTypes[$id] = $resourceType;
		}

		ksort($resourceTypes, SORT_STRING);
		return $resourceTypes;
	}

	/**
	 * @return list<ResolvedPermission>
	 */
	private function resolvePermissions(IPermissionResourceType $resourceType): array {
		$resourceTypeId = $resourceType->getId();

		/** @var array<non-empty-string, array{IPermission, PermissionBinding, string}> $bound */
		$bound = [];
		foreach ($resourceType->getPermissionBindings() as $binding) {
			$context = ['resourceTypeId' => $resourceTypeId, 'permissionId' => $binding->permissionId];

			$permission = $this->permissions[$binding->permissionId] ?? null;
			if ($permission === null) {
				$this->logger->warning('Resource type {resourceTypeId} binds the unknown permission {permissionId}', $context);
				continue;
			}

			if (isset($bound[$binding->permissionId])) {
				$this->logger->error('Resource type {resourceTypeId} binds the permission {permissionId} multiple times', $context);
				continue;
			}

			$defaultValue = $binding->defaultValue ?? $permission->getDefaultValue();
			$defaultValueValid = $permission->validateValue($defaultValue);
			if ($defaultValueValid !== true) {
				$this->logger->error('Resource type {resourceTypeId} binds the permission {permissionId} with an invalid default value: {error}', $context + ['error' => $defaultValueValid]);
				continue;
			}

			$bound[$binding->permissionId] = [$permission, $binding, $defaultValue];
		}

		$resolvedPermissions = [];
		foreach ($bound as $permissionId => [$permission, $binding, $defaultValue]) {
			$requiredPermissionIds = array_values(array_filter(
				$permission->getRequiredPermissionIds(),
				static fn (string $requiredId): bool => $requiredId !== $permissionId && isset($bound[$requiredId]),
			));

			$resolvedPermissions[] = new ResolvedPermission(
				$permission,
				$resourceTypeId,
				$binding->displayName ?? $permission->getDisplayName(),
				$binding->hint ?? $permission->getHint(),
				$defaultValue,
				$requiredPermissionIds,
			);
		}

		usort(
			$resolvedPermissions,
			static fn (ResolvedPermission $a, ResolvedPermission $b): int => self::comparePermissions($a->permission, $b->permission),
		);

		return $resolvedPermissions;
	}

	/**
	 * @template T of object
	 * @param class-string $class
	 * @param class-string<T> $interface
	 * @return ?T
	 */
	private function instantiate(string $class, string $interface): ?object {
		try {
			$instance = $this->container->get($class);
		} catch (ContainerExceptionInterface $e) {
			$this->logger->error('Could not instantiate {class}', ['class' => $class, 'exception' => $e]);
			return null;
		}

		if (!$instance instanceof $interface) {
			$this->logger->error('{class} does not implement {interface}', ['class' => $class, 'interface' => $interface]);
			return null;
		}

		return $instance;
	}

	/**
	 * @psalm-assert-if-true non-empty-string $id
	 */
	private function isValidId(string $id, string $kind, string $class): bool {
		if (preg_match(self::ID_PATTERN, $id) === 1) {
			return true;
		}

		$this->logger->error('The {kind} {class} has an invalid ID {id}, the ID must have the format appid:name', ['kind' => $kind, 'class' => $class, 'id' => $id]);
		return false;
	}

	private function isUnique(string $id, array $registered, string $kind, string $class): bool {
		if (!isset($registered[$id])) {
			return true;
		}

		$this->logger->error('The {kind} {class} uses the ID {id} which is already registered by {existingClass}', ['kind' => $kind, 'class' => $class, 'id' => $id, 'existingClass' => $registered[$id]::class]);
		return false;
	}

	private static function comparePermissions(IPermission $a, IPermission $b): int {
		return ($b->getPriority() <=> $a->getPriority()) ?: ($a->getId() <=> $b->getId());
	}
}
