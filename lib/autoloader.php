<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AutoloadNotAllowedException;
use OCP\ICache;
use Psr\Log\LoggerInterface;

class Autoloader {
	/** @var bool */
	private $useGlobalClassPath = true;
	/** @var array */
	private $validRoots = [];

	/**
	 * Optional low-latency memory cache for class to path mapping.
	 *
	 * @var \OC\Memcache\Cache
	 */
	protected $memoryCache;

	/**
	 * Autoloader constructor.
	 *
	 * @param string[] $validRoots
	 */
	public function __construct(array $validRoots) {
		foreach ($validRoots as $root) {
			$this->validRoots[$root] = true;
		}
	}

	/**
	 * Add a path to the list of valid php roots for auto loading
	 *
	 * @param string $root
	 */
	public function addValidRoot(string $root): void {
		$root = stream_resolve_include_path($root);
		$this->validRoots[$root] = true;
	}

	/**
	 * disable the usage of the global classpath \OC::$CLASSPATH
	 */
	public function disableGlobalClassPath(): void {
		$this->useGlobalClassPath = false;
	}

	/**
	 * enable the usage of the global classpath \OC::$CLASSPATH
	 */
	public function enableGlobalClassPath(): void {
		$this->useGlobalClassPath = true;
	}

	/**
	 * get the possible paths for a class
	 *
	 * @param string $class
	 * @return array an array of possible paths
	 */
	public function findClass(string $class): array {
		$class = trim($class, '\\');

		$paths = [];
		if ($this->useGlobalClassPath && array_key_exists($class, \OC::$CLASSPATH)) {
			$paths[] = \OC::$CLASSPATH[$class];
			/**
			 * @TODO: Remove this when necessary
			 * Remove "apps/" from inclusion path for smooth migration to multi app dir
			 */
			if (strpos(\OC::$CLASSPATH[$class], 'apps/') === 0) {
				\OCP\Server::get(LoggerInterface::class)->debug('include path for class "' . $class . '" starts with "apps/"', ['app' => 'core']);
				$paths[] = str_replace('apps/', '', \OC::$CLASSPATH[$class]);
			}
		} elseif (strpos($class, 'OC_') === 0) {
			$paths[] = \OC::$SERVERROOT . '/lib/private/legacy/' . strtolower(str_replace('_', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OCA\\') === 0) {
			[, $app, $rest] = explode('\\', $class, 3);
			$app = strtolower($app);
			try {
				$appPath = \OCP\Server::get(IAppManager::class)->getAppPath($app);
				if (stream_resolve_include_path($appPath)) {
					$paths[] = $appPath . '/' . strtolower(str_replace('\\', '/', $rest) . '.php');
					// If not found in the root of the app directory, insert '/lib' after app id and try again.
					$paths[] = $appPath . '/lib/' . strtolower(str_replace('\\', '/', $rest) . '.php');
				}
			} catch (AppPathNotFoundException) {
				// App not found, ignore
			}
		} elseif ($class === 'Test\\TestCase') {
			// This File is considered public API, so we make sure that the class
			// can still be loaded, although the PSR-4 paths have not been loaded.
			$paths[] = \OC::$SERVERROOT . '/tests/lib/TestCase.php';
		}
		return $paths;
	}

	/**
	 * @param string $fullPath
	 * @return bool
	 * @throws AutoloadNotAllowedException
	 */
	protected function isValidPath(string $fullPath): bool {
		foreach ($this->validRoots as $root => $true) {
			if (substr($fullPath, 0, strlen($root) + 1) === $root . '/') {
				return true;
			}
		}
		throw new AutoloadNotAllowedException($fullPath);
	}

	/**
	 * Load the specified class
	 *
	 * @param string $class
	 * @return bool
	 * @throws AutoloadNotAllowedException
	 */
	public function load(string $class): bool {
		$pathsToRequire = null;
		if ($this->memoryCache) {
			$pathsToRequire = $this->memoryCache->get($class);
		}

		if (class_exists($class, false)) {
			return false;
		}

		if (!is_array($pathsToRequire)) {
			// No cache or cache miss
			$pathsToRequire = [];
			foreach ($this->findClass($class) as $path) {
				$fullPath = stream_resolve_include_path($path);
				if ($fullPath && $this->isValidPath($fullPath)) {
					$pathsToRequire[] = $fullPath;
				}
			}

			if ($this->memoryCache) {
				$this->memoryCache->set($class, $pathsToRequire, 60); // cache 60 sec
			}
		}

		foreach ($pathsToRequire as $fullPath) {
			require_once $fullPath;
		}

		return false;
	}

	/**
	 * Sets the optional low-latency cache for class to path mapping.
	 *
	 * @param ICache $memoryCache Instance of memory cache.
	 */
	public function setMemoryCache(?ICache $memoryCache = null): void {
		$this->memoryCache = $memoryCache;
	}
}
