<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework\Utility;

use ArrayAccess;
use Closure;
use OCP\AppFramework\Attribute\PersistAcrossRequests;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\IContainer;
use Pimple\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use function class_exists;

/**
 * SimpleContainer is a simple implementation of a container on basis of Pimple
 */
class SimpleContainer implements ArrayAccess, ContainerInterface, IContainer {
	/** @psalm-suppress ImpureStaticProperty A static property is the only way to pass the information from config to autoload */
	public static bool $useLazyObjects = false;

	/** @psalm-suppress ImpureStaticProperty Set once when a long-running worker (e.g. FrankenPHP) starts */
	public static bool $keepPersistentServices = false;

	/** A kept instance is rebuilt after this many seconds even without an invalidation, as a safety net */
	private const MAX_PERSISTENT_AGE_SECONDS = 3600;

	/**
	 * Guards against re-entering a generation check: PersistentServiceInvalidator's own
	 * dependency chain (via Memcache\Factory::getGlobalPrefix()) can resolve another persisted
	 * class, which would otherwise recurse into checking generations forever. While true, a class
	 * with the attribute is resolved as if it didn't have it, rather than looping.
	 *
	 * @psalm-suppress ImpureStaticProperty This class has a reset method
	 */
	private static bool $checkingGenerations = false;

	/**
	 * @internal
	 */
	public static function resetPersistentInstances(): void {
		self::$keepPersistentServices = false;
		self::$checkingGenerations = false;
	}

	protected Container $container;

	/** @var array<string,string> */
	private array $aliases = [];

	/**
	 * The invalidation generations each kept instance was built against (keyed by group name),
	 * plus when it was built. Only ever holds entries for classes still sitting in $container.
	 *
	 * @var array<string, array{groups: array<string, int>, builtAt: int}>
	 */
	private array $persistentMeta = [];

	/** @var array<string, true> ids registered as a Pimple factory: never cached, nothing to evict on reset */
	private array $factoryIds = [];

	/**
	 * Generation lookups memoized for the current resetForNextRequest() pass, so that several
	 * persisted classes sharing a group (e.g. PersistentServiceGroup::Apps) don't each hit the
	 * distributed cache separately for the same "is generation N still current" question.
	 *
	 * @var array<string, int>
	 */
	private array $generationCache = [];

	/**
	 * @var array<string, true> ids whose only container entry is query()'s own memoization of an
	 * autowired instance (as opposed to a real service definition registered through
	 * registerService()/registerAlias()). There is nothing to rebuild such an entry from other
	 * than dropping it outright and letting the next query() re-resolve it.
	 */
	private array $autoResolvedIds = [];

	public function __construct() {
		$this->container = new Container();
	}

	/**
	 * @template T
	 * @param class-string<T>|string $id
	 * @return ($id is class-string<T> ? T : mixed)
	 */
	#[\Override]
	public function get(string $id): mixed {
		return $this->query($this->sanitizeName($id));
	}

	#[\Override]
	public function has(string $id): bool {
		// If a service is no registered but is an existing class, we can probably load it
		return isset($this->aliases[$id]) || isset($this->container[$id]) || class_exists($id);
	}

	/**
	 * @param ReflectionClass $class the class to instantiate
	 * @param list<class-string> $chain
	 * @return object the created class
	 * @suppress PhanUndeclaredClassInstanceof
	 */
	private function buildClass(ReflectionClass $class, array $chain): object {
		$constructor = $class->getConstructor();
		if ($constructor === null) {
			/* No constructor, return a instance directly */
			return $class->newInstance();
		}
		if (PHP_VERSION_ID >= 80400 && self::$useLazyObjects && !$class->isInternal()) {
			/* For PHP>=8.4, use a lazy ghost to delay constructor and dependency resolving */
			/** @psalm-suppress UndefinedMethod */
			return $class->newLazyGhost(function (object $object) use ($constructor, $chain): void {
				/** @psalm-suppress DirectConstructorCall For lazy ghosts we have to call the constructor directly */
				$object->__construct(...$this->buildClassConstructorParameters($constructor, $chain));
			});
		} else {
			return $class->newInstanceArgs($this->buildClassConstructorParameters($constructor, $chain));
		}
	}

	/**
	 * @param list<class-string> $chain
	 */
	private function buildClassConstructorParameters(\ReflectionMethod $constructor, array $chain): array {
		return array_map(function (ReflectionParameter $parameter) use ($chain) {
			$parameterType = $parameter->getType();

			$resolveName = $parameter->getName();

			// try to find out if it is a class or a simple parameter
			if ($parameterType !== null && ($parameterType instanceof ReflectionNamedType) && !$parameterType->isBuiltin()) {
				$resolveName = $parameterType->getName();
			}

			try {
				$builtIn = $parameterType !== null && ($parameterType instanceof ReflectionNamedType)
							&& $parameterType->isBuiltin();
				return $this->query($resolveName, !$builtIn, $chain);
			} catch (ContainerExceptionInterface $e) {
				// Service not found, use the default value when available
				if ($parameter->isDefaultValueAvailable()) {
					return $parameter->getDefaultValue();
				}

				if ($parameterType !== null && ($parameterType instanceof ReflectionNamedType) && !$parameterType->isBuiltin()) {
					$resolveName = $parameter->getName();
					try {
						return $this->query($resolveName, chain: $chain);
					} catch (ContainerExceptionInterface $e2) {
						// Pass null if typed and nullable
						if ($parameter->allowsNull() && ($parameterType instanceof ReflectionNamedType)) {
							return null;
						}

						// don't lose the error we got while trying to query by type
						throw new QueryException($e->getMessage(), (int)$e->getCode(), $e);
					}
				}

				throw $e;
			}
		}, $constructor->getParameters());
	}

	/**
	 * @template T
	 *
	 * Try to instantiate by using reflection to find out how to build the class.
	 *
	 * @param class-string<T>|string $name
	 * @param list<class-string> $chain
	 * @return ($name is class-string<T> ? T : mixed)
	 * @internal
	 * @throws ContainerExceptionInterface if the class could not be found or instantiated
	 */
	public function resolve(string $name, array $chain = []): mixed {
		$baseMsg = 'Could not resolve ' . $name . '!';
		try {
			$class = new ReflectionClass($name);
			if (!$class->isInstantiable()) {
				throw new QueryException($baseMsg
					. ' Class can not be instantiated');
			}

			$attributes = $class->getAttributes(PersistAcrossRequests::class);
			$isPersistent = self::$keepPersistentServices && !empty($attributes) && !self::$checkingGenerations;
			$className = $class->getName();
			$groups = $isPersistent
				? array_map(
					static fn (string|PersistentServiceGroup $group): string => $group instanceof PersistentServiceGroup ? $group->value : $group,
					$attributes[0]->newInstance()->invalidatedBy,
				)
				: [];

			$object = $this->buildClass($class, $chain);

			if ($isPersistent) {
				$this->persistentMeta[$className] = [
					'groups' => $this->currentGenerations($groups),
					'builtAt' => time(),
				];
			}

			return $object;
		} catch (ReflectionException $e) {
			// Class does not exist
			throw new QueryNotFoundException($baseMsg . ' ' . $e->getMessage());
		}
	}

	private function isPersistentInstanceStillValid(string $id): bool {
		if (!isset($this->persistentMeta[$id])) {
			return false;
		}
		['groups' => $groups, 'builtAt' => $builtAt] = $this->persistentMeta[$id];
		if ((time() - $builtAt) > self::MAX_PERSISTENT_AGE_SECONDS) {
			return false;
		}
		return $groups === $this->currentGenerations(array_keys($groups));
	}

	/**
	 * Drops every already-resolved service that a fresh request shouldn't inherit, so the next
	 * query()/get() call for it rebuilds a clean instance. A class kept alive by
	 * {@see PersistAcrossRequests} (and still valid) is left completely untouched.
	 *
	 * Call this instead of throwing the whole container away between requests on a long-running
	 * worker (e.g. FrankenPHP): it keeps every service *definition* (the closures registered via
	 * registerService()/registerAlias(), and by extension anything built through them, such as app
	 * containers), it only forgets which of them have already been resolved this "request epoch".
	 */
	public function resetForNextRequest(): void {
		$this->generationCache = [];
		foreach ($this->container->keys() as $id) {
			if (isset($this->factoryIds[$id]) || $this->isPersistentInstanceStillValid($id)) {
				// A factory never caches anything to begin with; a still-valid persisted
				// instance is exactly what should survive into the next request.
				continue;
			}

			if (isset($this->autoResolvedIds[$id])) {
				// query() only memoized an object it built via reflection; there is no service
				// definition to fall back to, so the entry has to go entirely. The next query()
				// for this id will autowire a fresh instance from scratch.
				$this->container->offsetUnset($id);
				unset($this->autoResolvedIds[$id], $this->persistentMeta[$id]);
				continue;
			}

			// A real service definition: keep it, only forget the cached instance it already
			// produced so it runs again on next access.
			$raw = $this->container->raw($id);
			$this->container->offsetUnset($id);
			$this->container->offsetSet($id, $raw);
			unset($this->persistentMeta[$id]);
		}
	}

	/**
	 * @param list<string> $groups
	 * @return array<string, int>
	 */
	private function currentGenerations(array $groups): array {
		if (empty($groups)) {
			return [];
		}
		self::$checkingGenerations = true;
		try {
			$invalidator = $this->get(PersistentServiceInvalidator::class);
			$generations = [];
			foreach ($groups as $group) {
				$generations[$group] = $this->generationCache[$group] ??= $invalidator->getGeneration($group);
			}
			return $generations;
		} finally {
			self::$checkingGenerations = false;
		}
	}

	/**
	 * @param string $name Already sanitized name
	 * @param list<class-string> $chain
	 */
	protected function query(string $name, bool $autoload = true, array $chain = []): mixed {
		$name = $this->resolveAlias($name);
		if (isset($this->container[$name])) {
			return $this->container[$name];
		}

		if (!$autoload) {
			throw new QueryNotFoundException('Could not resolve ' . $name . '!');
		}

		if (in_array($name, $chain, true)) {
			throw new RuntimeException('Tried to query ' . $name . ', but it is already in the chain: ' . implode(', ', $chain));
		}

		$object = $this->resolve($name, array_merge($chain, [$name]));
		$this->cacheAutoResolvedInstance($name, $object);
		return $object;
	}

	/**
	 * Caches an already-built instance under $id as if query() had resolved it itself: on
	 * resetForNextRequest(), it's dropped entirely (there being no service definition to rebuild
	 * from) rather than kept forever like a raw ArrayAccess write would be.
	 *
	 * @internal
	 */
	public function cacheAutoResolvedInstance(string $id, object $instance): void {
		$this->registerService($id, function () use ($instance) {
			return $instance;
		});
		$this->autoResolvedIds[$id] = true;
	}

	/**
	 * A value is stored in the container with its corresponding name
	 *
	 * @since 6.0.0
	 * @internal apps should use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerParameter
	 */
	public function registerParameter(string $name, mixed $value): void {
		$this->container[$name] = $value;
	}

	/**
	 * A service is registered in the container where a closure is passed in which will actually
	 * create the service on demand.
	 * In case the parameter $shared is set to true (the default usage) the once created service will remain in
	 * memory and be reused on subsequent calls.
	 * In case the parameter is false the service will be recreated on every call.
	 *
	 * @param \Closure(IContainer): mixed $closure
	 * @internal apps should use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerService
	 */
	public function registerService(string $name, Closure $closure, bool $shared = true): void {
		$wrapped = fn () => $closure($this);
		$name = $this->sanitizeName($name);
		if (isset($this->container[$name])) {
			unset($this->container[$name]);
		}
		if (isset($this->aliases[$name])) {
			unset($this->aliases[$name]);
		}
		// A real definition is being (re)registered, so this id is no longer just a bare
		// autowired instance query() happened to memoize.
		unset($this->autoResolvedIds[$name]);
		if ($shared) {
			unset($this->factoryIds[$name]);
			$this->container[$name] = $wrapped;
		} else {
			$this->factoryIds[$name] = true;
			$this->container[$name] = $this->container->factory($wrapped);
		}
	}

	/**
	 * Shortcut for returning a service from a service under a different key,
	 * e.g. to tell the container to return a class when queried for an
	 * interface
	 * @param string $alias the alias that should be registered
	 * @param string $target the target that should be resolved instead
	 */
	public function registerAlias(string $alias, string $target): void {
		$this->aliases[$alias] = $target;
	}

	protected function resolveAlias(string $name) : string {
		while (isset($this->aliases[$name])) {
			$name = $this->aliases[$name];
		}
		return $name;
	}

	protected function registerDeprecatedAlias(string $alias, string $target): void {
		$this->registerService($alias, function (ContainerInterface $container) use ($target, $alias): mixed {
			try {
				$logger = $container->get(LoggerInterface::class);
				$logger->debug('The requested alias "' . $alias . '" is deprecated. Please request "' . $target . '" directly. This alias will be removed in a future Nextcloud version.', [
					'app' => $this->appName ?? 'serverDI',
				]);
			} catch (ContainerExceptionInterface $e) {
				// Could not get logger. Continue
			}

			return $container->get($target);
		}, false);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function sanitizeName($name) {
		if (isset($name[0]) && $name[0] === '\\') {
			return ltrim($name, '\\');
		}
		return $name;
	}

	/**
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::has
	 */
	#[\Override]
	public function offsetExists($id): bool {
		return $this->container->offsetExists($id);
	}

	/**
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::get
	 * @return mixed
	 */
	#[\Override]
	#[\ReturnTypeWillChange]
	public function offsetGet($id) {
		return $this->container->offsetGet($id);
	}

	/**
	 * @deprecated 20.0.0 use \OCP\IContainer::registerService
	 */
	#[\Override]
	public function offsetSet($offset, $value): void {
		$this->container->offsetSet($offset, $value);
	}

	/**
	 * @deprecated 20.0.0
	 */
	#[\Override]
	public function offsetUnset($offset): void {
		$this->container->offsetUnset($offset);
	}
}
