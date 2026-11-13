<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Resource\Permission;

use InvalidArgumentException;
use NCU\Resource\IResourceType;
use NCU\Resource\Permission\AccessEntry;
use NCU\Resource\Permission\CorePermission;
use NCU\Resource\Permission\EffectivePermissions;
use NCU\Resource\Permission\IPermission;
use NCU\Resource\Permission\IPermissionManager;
use NCU\Resource\Permission\IRestrictionProvider;
use NCU\Resource\Permission\ResourceAccess;
use NCU\Resource\ResourceReference;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OCP\Actor\Actor;
use OCP\Cache\CappedMemoryCache;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class PermissionManager implements IPermissionManager {
	private const string NAME_PATTERN = '/^[a-z0-9_.-]+$/';

	/** @var ?array<non-empty-string, IPermission> */
	private ?array $permissions = null;

	/** @var array<string, list<non-empty-string>> IDs of the permissions that are a kind of the core permission used as key */
	private array $kinds = [];

	/** @var ?array<non-empty-string, IResourceType> */
	private ?array $resourceTypes = null;

	/** @var ?list<IRestrictionProvider> */
	private ?array $restrictionProviders = null;

	/** @var CappedMemoryCache<EffectivePermissions> results by actor key, resource key and hash of the access entries */
	private CappedMemoryCache $cache;

	public function __construct(
		private readonly Coordinator $coordinator,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
		private readonly AccessManager $accessManager,
	) {
		$this->cache = new CappedMemoryCache(2048);
	}

	#[\Override]
	public function getPermissions(): array {
		if ($this->permissions !== null) {
			return $this->permissions;
		}

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			// Not cached: nothing is registered yet this early in boot.
			return [];
		}

		$permissions = [];
		$kinds = [];
		foreach ($context->getResourcePermissions() as $registration) {
			$permission = $this->instantiate($registration, IPermission::class);
			if ($permission === null) {
				continue;
			}

			$id = $permission->getId();
			if (!$this->isValidId($id, $registration->getAppId()) || isset($permissions[$id])) {
				$this->logger->error('The permission {id} of the app {app} has an invalid or already registered ID', ['id' => $id, 'app' => $registration->getAppId()]);
				continue;
			}

			$kindOf = $permission->getKindOf();
			if (($kindOf === null) !== (CorePermission::tryFrom($id) !== null)) {
				$this->logger->error('The permission {id} has to be a kind of a core permission, and a core permission cannot be a kind of another one', ['id' => $id]);
				continue;
			}

			$permissions[$id] = $permission;
			if ($kindOf !== null) {
				$kinds[$kindOf->value][] = $id;
			}
		}

		$this->permissions = $permissions;
		$this->kinds = $kinds;

		return $permissions;
	}

	#[\Override]
	public function getPermission(string $id): ?IPermission {
		return $this->getPermissions()[$id] ?? null;
	}

	#[\Override]
	public function getResourceTypes(): array {
		if ($this->resourceTypes !== null) {
			return $this->resourceTypes;
		}

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		$resourceTypes = [];
		foreach ($context->getResourceTypes() as $registration) {
			$resourceType = $this->instantiate($registration, IResourceType::class);
			if ($resourceType === null) {
				continue;
			}

			$id = $resourceType->getId();
			if (!$this->isValidId($id, $registration->getAppId()) || isset($resourceTypes[$id])) {
				$this->logger->error('The resource type {id} of the app {app} has an invalid or already registered ID', ['id' => $id, 'app' => $registration->getAppId()]);
				continue;
			}

			$resourceTypes[$id] = $resourceType;
		}

		$this->resourceTypes = $resourceTypes;

		return $resourceTypes;
	}

	#[\Override]
	public function getResourceType(string $id): ?IResourceType {
		return $this->getResourceTypes()[$id] ?? null;
	}

	#[\Override]
	public function check(Actor $actor, ResourceReference $resource, ?array $entries = null): EffectivePermissions {
		return $this->checkAll($actor, [new ResourceAccess($resource, $entries)])[$resource->key()];
	}

	#[\Override]
	public function checkAll(Actor $actor, array $accesses): array {
		$actorKey = $actor->key();
		$nothing = new EffectivePermissions([]);
		/** @var array<non-empty-string, EffectivePermissions> $results in the order of the accesses */
		$results = [];
		$unresolved = [];
		foreach ($accesses as $access) {
			$resourceKey = $access->resource->key();
			if ($this->getResourceType($access->resource->type) === null) {
				throw new InvalidArgumentException('The resource type ' . $access->resource->type . ' is not registered');
			}
			if (isset($results[$resourceKey])) {
				throw new InvalidArgumentException('The resource ' . $resourceKey . ' is passed more than once');
			}

			$results[$resourceKey] = $nothing;
			if ($access->entries === null) {
				$unresolved[] = $access->resource;
			}
		}
		$resolved = $unresolved === [] ? [] : $this->accessManager->resolve($actor, $unresolved);

		/** @var array<non-empty-string, ResourceAccess> $misses */
		$misses = [];
		$cacheKeys = [];
		foreach ($accesses as $access) {
			$resourceKey = $access->resource->key();
			// No entries are left if the resource type failed, this is not cached as it may succeed on the next check.
			$entries = $access->entries ?? $resolved[$resourceKey] ?? [];
			if ($entries === []) {
				continue;
			}

			$access = new ResourceAccess($access->resource, $entries);
			$cacheKeys[$resourceKey] = $actorKey . "\0" . $resourceKey . "\0" . $this->hashEntries($entries);
			$cached = $this->cache->get($cacheKeys[$resourceKey]);
			if ($cached !== null) {
				$results[$resourceKey] = $cached;
			} else {
				$misses[$resourceKey] = $access;
			}
		}

		if ($misses === []) {
			return $results;
		}

		$restrictions = $this->collectRestrictions($actor, array_values($misses));
		$applicable = [];
		foreach ($misses as $resourceKey => $access) {
			if ($restrictions === null) {
				// Not cached: the provider that failed may succeed on the next check.
				continue;
			}

			$applicable[$access->resource->type] ??= $this->getApplicablePermissionIds($access->resource->type);
			$result = $this->evaluate($access, $applicable[$access->resource->type], $restrictions[$resourceKey] ?? []);
			$this->cache->set($cacheKeys[$resourceKey], $result);
			$results[$resourceKey] = $result;
		}

		return $results;
	}

	#[\Override]
	public function clearCache(): void {
		$this->cache->clear();
		$this->accessManager->clearCache();
	}

	/**
	 * Get the restricted permission IDs by resource key and recipient,
	 * the empty recipient is the resource as a whole.
	 *
	 * @param list<ResourceAccess> $accesses with the access entries set
	 * @return ?array<string, array<string, array<string, true>>> null if a provider failed
	 */
	private function collectRestrictions(Actor $actor, array $accesses): ?array {
		$providers = $this->getRestrictionProviders();
		if ($providers === null) {
			return null;
		}

		$restrictions = [];
		foreach ($providers as $provider) {
			try {
				foreach ($provider->getRestrictions($actor, $accesses) as $restriction) {
					$recipient = $restriction->recipientKind !== null && $restriction->recipientId !== null
						? $this->recipientKey($restriction->recipientKind, $restriction->recipientId)
						: '';
					foreach ($this->expand($restriction->permissionIds) as $permissionId) {
						$restrictions[$restriction->resource->key()][$recipient][$permissionId] = true;
					}
				}
			} catch (Throwable $e) {
				$this->logger->error('The restriction provider {class} failed, all permissions are restricted', ['class' => $provider::class, 'exception' => $e]);
				return null;
			}
		}

		return $restrictions;
	}

	/**
	 * Get the registered permissions that apply to the resource type.
	 *
	 * @return array<string, true>
	 */
	private function getApplicablePermissionIds(string $resourceType): array {
		return array_fill_keys(
			array_intersect($this->getResourceTypes()[$resourceType]->getPermissionIds(), array_keys($this->getPermissions())),
			true,
		);
	}

	/**
	 * @param array<string, true> $applicable permission IDs that apply to the type of the resource
	 * @param array<string, array<string, true>> $restrictions restricted permission IDs by recipient
	 */
	private function evaluate(ResourceAccess $access, array $applicable, array $restrictions): EffectivePermissions {
		$allowed = [];
		foreach ($access->entries ?? [] as $entry) {
			$granted = array_fill_keys($entry->permissionIds, true);
			$notApplicable = array_diff_key($granted, $applicable);
			if ($notApplicable !== []) {
				$this->logger->warning('Ignoring permissions that do not apply to the resource type {type}: {permissions}', ['type' => $access->resource->type, 'permissions' => implode(', ', array_keys($notApplicable))]);
			}

			$allowed += array_diff_key(
				array_intersect_key($granted, $applicable),
				$restrictions[$this->recipientKey($entry->recipientKind, $entry->recipientId)] ?? [],
			);
		}

		return new EffectivePermissions(array_keys(array_diff_key($allowed, $restrictions[''] ?? [])));
	}

	/**
	 * Add the permissions that are a kind of one of the permissions.
	 *
	 * @param list<string> $permissionIds
	 * @return list<string>
	 */
	private function expand(array $permissionIds): array {
		$this->getPermissions();
		$expanded = $permissionIds;
		foreach ($permissionIds as $permissionId) {
			array_push($expanded, ...($this->kinds[$permissionId] ?? []));
		}

		return $expanded;
	}

	/**
	 * @return ?list<IRestrictionProvider> null if a provider could not be loaded
	 */
	private function getRestrictionProviders(): ?array {
		if ($this->restrictionProviders !== null) {
			return $this->restrictionProviders;
		}

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		$providers = [];
		foreach ($context->getResourceRestrictionProviders() as $registration) {
			$provider = $this->instantiate($registration, IRestrictionProvider::class);
			if ($provider === null) {
				// Not cached: the provider may be loadable on the next check.
				return null;
			}
			$providers[] = $provider;
		}

		$this->restrictionProviders = $providers;

		return $providers;
	}

	/**
	 * @template T of object
	 * @param ServiceRegistration<T> $registration
	 * @param class-string<T> $interface
	 * @return ?T
	 */
	private function instantiate(ServiceRegistration $registration, string $interface): ?object {
		try {
			$instance = $this->container->get($registration->getService());
		} catch (Throwable $e) {
			$this->logger->error('Could not load {class} of the app {app}', ['class' => $registration->getService(), 'app' => $registration->getAppId(), 'exception' => $e]);
			return null;
		}

		if (!$instance instanceof $interface) {
			$this->logger->error('{class} of the app {app} does not implement {interface}', ['class' => $registration->getService(), 'app' => $registration->getAppId(), 'interface' => $interface]);
			return null;
		}

		return $instance;
	}

	private function isValidId(string $id, string $appId): bool {
		$prefix = $appId . ':';
		return str_starts_with($id, $prefix)
			&& preg_match(self::NAME_PATTERN, substr($id, strlen($prefix))) === 1;
	}

	private function recipientKey(string $kind, string $id): string {
		return $kind . '/' . $id;
	}

	/**
	 * @param non-empty-list<AccessEntry> $entries
	 */
	private function hashEntries(array $entries): string {
		return hash('xxh128', json_encode(
			array_map(static fn (AccessEntry $entry): array => [$entry->recipientKind, $entry->recipientId, $entry->permissionIds], $entries),
			JSON_THROW_ON_ERROR,
		));
	}
}
