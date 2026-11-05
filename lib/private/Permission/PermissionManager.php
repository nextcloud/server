<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Permission;

use NCU\Permission\IPermission;
use NCU\Permission\IPermissionManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PermissionManager implements IPermissionManager {
	private const string ID_PATTERN ='/^[a-z0-9_]+:[a-z0-9_.]+$/';

	/** @var array<class-string<IPermission>, true> */
	private array $permissionClasses = [];

	/** @var ?array<non-empty-string, IPermission> */
	private ?array $permissions = null;

	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function registerPermission(string $class): void {
		$this->permissionClasses[$class] = true;
		$this->permissions = null;
	}

	#[\Override]
	public function getPermissions(): array {
		return $this->permissions ??= $this->loadPermissions();
	}

	#[\Override]
	public function getPermission(string $id): ?IPermission {
		return $this->getPermissions()[$id] ?? null;
	}

	/**
	 * @return array<non-empty-string, IPermission>
	 */
	private function loadPermissions(): array {
		$permissions = [];
		foreach (array_keys($this->permissionClasses) as $class) {
			$permission = $this->instantiate($class);
			if ($permission === null) {
				continue;
			}

			$id = $permission->getId();
			if (preg_match(self::ID_PATTERN, $id) !== 1) {
				$this->logger->error('The permission {class} has an invalid ID {id}, the ID must have the format appid:name', ['class' => $class, 'id' => $id]);
				continue;
			}

			if (isset($permissions[$id])) {
				$this->logger->error('The permission {class} uses the ID {id} which is already registered by {existingClass}', ['class' => $class, 'id' => $id, 'existingClass' => $permissions[$id]::class]);
				continue;
			}

			$defaultValueValid = $permission->validateValue($permission->getDefaultValue());
			if ($defaultValueValid !== true) {
				$this->logger->error('The permission {id} has an invalid default value: {error}', ['id' => $id, 'error' => $defaultValueValid]);
				continue;
			}

			$permissions[$id] = $permission;
		}

		foreach ($permissions as $id => $permission) {
			foreach ($permission->getRequiredPermissionIds() as $requiredId) {
				if ($requiredId === $id || !isset($permissions[$requiredId])) {
					$this->logger->warning('The permission {id} requires itself or the unknown permission {requiredId}', ['id' => $id, 'requiredId' => $requiredId]);
				}
			}
		}

		uasort(
			$permissions,
			static fn (IPermission $a, IPermission $b): int
				=> ($b->getPriority() <=> $a->getPriority())
				?: ($a->getId() <=> $b->getId()),
		);

		return $permissions;
	}

	/**
	 * @param class-string<IPermission> $class
	 */
	private function instantiate(string $class): ?IPermission {
		try {
			$permission = $this->container->get($class);
		} catch (ContainerExceptionInterface $e) {
			$this->logger->error('Could not instantiate the permission {class}', ['class' => $class, 'exception' => $e]);
			return null;
		}

		if (!$permission instanceof IPermission) {
			$this->logger->error('{class} does not implement {interface}', ['class' => $class, 'interface' => IPermission::class]);
			return null;
		}

		return $permission;
	}
}
