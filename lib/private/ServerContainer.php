<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\AppFramework\App;
use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Utility\SimpleContainer;
use OCP\AppFramework\QueryException;
use function explode;
use function strtolower;

/**
 * Class ServerContainer
 *
 * @package OC
 */
class ServerContainer extends SimpleContainer {
	/** @var DIContainer[] */
	protected $appContainers;

	/** @var string[] */
	protected $hasNoAppContainer;

	/** @var string[] */
	protected $namespaces;

	/**
	 * ServerContainer constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->appContainers = [];
		$this->namespaces = [];
		$this->hasNoAppContainer = [];
	}

	/**
	 * @param string $appName
	 * @param string $appNamespace
	 */
	public function registerNamespace(string $appName, string $appNamespace): void {
		// Cut of OCA\ and lowercase
		$appNamespace = strtolower(substr($appNamespace, strrpos($appNamespace, '\\') + 1));
		$this->namespaces[$appNamespace] = $appName;
	}

	/**
	 * @param string $appName
	 * @param DIContainer $container
	 */
	public function registerAppContainer(string $appName, DIContainer $container): void {
		$this->appContainers[strtolower(App::buildAppNamespace($appName, ''))] = $container;
	}

	/**
	 * @param string $appName
	 * @return DIContainer
	 * @throws QueryException
	 */
	public function getRegisteredAppContainer(string $appName): DIContainer {
		if (isset($this->appContainers[strtolower(App::buildAppNamespace($appName, ''))])) {
			return $this->appContainers[strtolower(App::buildAppNamespace($appName, ''))];
		}

		throw new QueryException();
	}

	/**
	 * @param string $namespace
	 * @param string $sensitiveNamespace
	 * @return DIContainer
	 * @throws QueryException
	 */
	protected function getAppContainer(string $namespace, string $sensitiveNamespace): DIContainer {
		if (isset($this->appContainers[$namespace])) {
			return $this->appContainers[$namespace];
		}

		if (isset($this->namespaces[$namespace])) {
			if (!isset($this->hasNoAppContainer[$namespace])) {
				$applicationClassName = 'OCA\\' . $sensitiveNamespace . '\\AppInfo\\Application';
				if (class_exists($applicationClassName)) {
					$app = new $applicationClassName();
					if (isset($this->appContainers[$namespace])) {
						$this->appContainers[$namespace]->offsetSet($applicationClassName, $app);
						return $this->appContainers[$namespace];
					}
				}
				$this->hasNoAppContainer[$namespace] = true;
			}

			return new DIContainer($this->namespaces[$namespace]);
		}
		throw new QueryException();
	}

	public function has($id, bool $noRecursion = false): bool {
		if (!$noRecursion && ($appContainer = $this->getAppContainerForService($id)) !== null) {
			return $appContainer->has($id);
		}

		return parent::has($id);
	}

	/**
	 * @template T
	 * @param class-string<T>|string $name
	 * @return T|mixed
	 * @psalm-template S as class-string<T>|string
	 * @psalm-param S $name
	 * @psalm-return (S is class-string<T> ? T : mixed)
	 * @throws QueryException
	 * @deprecated 20.0.0 use \Psr\Container\ContainerInterface::get
	 */
	public function query(string $name, bool $autoload = true) {
		$name = $this->sanitizeName($name);

		if (str_starts_with($name, 'OCA\\')) {
			// Skip server container query for app namespace classes
			try {
				return parent::query($name, false);
			} catch (QueryException $e) {
				// Continue with general autoloading then
			}
		}

		// In case the service starts with OCA\ we try to find the service in
		// the apps container first.
		if (($appContainer = $this->getAppContainerForService($name)) !== null) {
			try {
				return $appContainer->queryNoFallback($name);
			} catch (QueryException $e) {
				// Didn't find the service or the respective app container
				// In this case the service won't be part of the core container,
				// so we can throw directly
				throw $e;
			}
		} elseif (str_starts_with($name, 'OC\\Settings\\') && substr_count($name, '\\') >= 3) {
			$segments = explode('\\', $name);
			try {
				$appContainer = $this->getAppContainer(strtolower($segments[1]), $segments[1]);
				return $appContainer->queryNoFallback($name);
			} catch (QueryException $e) {
				// Didn't find the service or the respective app container,
				// ignore it and fall back to the core container.
			}
		}

		return parent::query($name, $autoload);
	}

	/**
	 * @internal
	 * @param string $id
	 * @return DIContainer|null
	 */
	public function getAppContainerForService(string $id): ?DIContainer {
		if (!str_starts_with($id, 'OCA\\') || substr_count($id, '\\') < 2) {
			return null;
		}

		try {
			[,$namespace,] = explode('\\', $id);
			return $this->getAppContainer(strtolower($namespace), $namespace);
		} catch (QueryException $e) {
			return null;
		}
	}
}
