<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2004 David Grudl (https://davidgrudl.com)
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace OC;

/**
 * TODO
 * Remove unused parts
 */

class Autoloader {
	private const int RetryLimit = 1;

	/** @var string[] */
	public array $ignoreDirs = ['.*', '*.old', '*.bak', '*.tmp', 'temp'];

	/** @var string[] */
	public array $acceptFiles = ['*.php'];
	private bool $reportParseErrors = true;

	/** @var array<string,string> namespace => path */
	private array $psr4Paths = [];

	/** @var string[] */
	private array $excludeDirs = [];

	/** @var array<string, array{string, int}>  class => [file, time] */
	private array $classes = [];
	private bool $cacheLoaded = false;
	private bool $refreshed = false;

	/** @var array<string, int>  class => counter */
	private array $missingClasses = [];

	private bool $needSave = false;

	private int $loadsFromCache = 0;
	private int $diskScans = 0;

	public function __construct(
		private PhpDumpCache $dumpCache,
	) {
		if (!extension_loaded('tokenizer')) {
			throw new \LogicException('PHP extension Tokenizer is not loaded.');
		}
	}

	public function __destruct() {
		if ($this->needSave) {
			$this->saveCache();
		}
	}

	/**
	 * Register autoloader.
	 */
	public function register(bool $prepend = false): static {
		spl_autoload_register([$this, 'tryLoad'], prepend: $prepend);
		return $this;
	}

	/**
	 * Handles autoloading of classes, interfaces or traits.
	 */
	public function tryLoad(string $type): void {
		try {
			$this->loadCache();

			$missing = $this->missingClasses[$type] ?? 0;
			if ($missing >= self::RetryLimit) {
				return;
			}

			[$file, $mtime] = $this->classes[$type] ?? null;

			if ($file) {
				(static function ($file) {
					require $file;
				})($file);
			}
		} catch (\Throwable $t) {
			throw $t;
		}
	}

	/**
	 * Add path for given namespace
	 *
	 * @param string $namespace The namespace to register, with no \ at either end.
	 * @param string $path The path to register, with a beginning / and no ending /.
	 */
	public function addPsr4(string $namespace, string $path): static {
		$this->psr4Paths[$namespace] = $path;
		return $this;
	}

	public function triggerReload(): void {
		$this->refreshed = false;
		$this->cacheLoaded = false;
	}

	public function reportParseErrors(bool $on = true): static {
		$this->reportParseErrors = $on;
		return $this;
	}

	/**
	 * Excludes path or paths from list.
	 */
	public function excludeDirectory(string ...$paths): static {
		$this->excludeDirs = array_merge($this->excludeDirs, $paths);
		return $this;
	}

	/**
	 * @return array<string, string> class => filename
	 */
	public function getIndexedClasses(): array {
		$this->loadCache();
		$res = [];
		foreach ($this->classes as $class => [$file]) {
			$res[$class] = $file;
		}

		return $res;
	}

	/**
	 * Rebuilds class list cache.
	 */
	public function rebuild(): void {
		$this->cacheLoaded = true;
		$this->classes = $this->missingClasses = [];
		$this->refreshClasses();
		$this->saveCache();
	}

	/**
	 * Refreshes class list cache.
	 */
	public function refresh(): void {
		$this->loadCache();
		if (!$this->refreshed) {
			$this->refreshClasses();
			$this->saveCache();
		}
	}

	/**
	 * Refreshes $this->classes.
	 */
	private function refreshClasses(): void {
		$this->refreshed = true; // prevents calling refreshClasses() in tryLoad()
		$files = [];
		$classes = [];
		foreach ($this->classes as $class => [$file, $mtime]) {
			$files[$file] = $mtime;
			$classes[$file][] = $class;
		}

		$this->classes = [];

		foreach ($this->psr4Paths as $namespace => $path) {
			$iterator = $this->createFileIterator($path);
			// Length of path + separator
			$pathLen = strlen($path) + 1;
			if ($namespace === '') {
				$prefix = '';
			} else {
				$prefix = $namespace . '\\';
			}
			foreach ($iterator as $file) {
				$class = $prefix . str_replace('/', '\\', substr($file, $pathLen, -4));
				if (isset($this->classes[$class])) {
					throw new \RuntimeException(sprintf(
						'Ambiguous class %s resolution; defined in %s and in %s.',
						$class,
						$this->classes[$class][0],
						$file,
					));
				}

				//FIXME needed?
				$mtime = filemtime($file);

				$this->classes[$class] = [$file, $mtime];
				unset($this->missingClasses[$class]);
			}
		}

		$this->diskScans++;
	}

	/**
	 * Creates an iterator scanning directory for PHP files and subdirectories.
	 * @throws \RuntimeException if path is not found
	 */
	private function createFileIterator(string $dir): \Generator {
		if (!is_dir($dir)) {
			throw new \RuntimeException(sprintf("Directory '%s' not found.", $dir));
		}

		$dir = realpath($dir) ?: $dir; // realpath does not work in phar
		$disallow = [];
		foreach (array_merge($this->ignoreDirs, $this->excludeDirs) as $item) {
			if ($item = realpath($item)) {
				$disallow[$item] = true;
			}
		}

		yield from $this->traverseDir($dir, $disallow);
	}

	private function traverseDir(string $dir, array $disallow): \Generator {
		try {
			$files = new \FilesystemIterator($dir, \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::UNIX_PATHS);
		} catch (\RuntimeException) {
			return;
		}

		foreach ($files as $file) {
			$realPath = realpath($file);
			$file = $realPath ?: $file;
			if ($realPath && isset($disallow[$realPath])) {
				continue;
			} elseif (is_dir($file) && !self::matches(basename($file), $this->ignoreDirs)) {
				yield from $this->traverseDir($file, $disallow);
			} elseif (is_file($file) && self::matches(basename($file), $this->acceptFiles)) {
				yield $file;
			}
		}
	}

	private static function matches(string $file, array $masks): bool {
		foreach ($masks as $mask) {
			if (fnmatch($mask, $file)) {
				return true;
			}
		}
		return false;
	}

	/********************* caching *******************/

	/**
	 * Loads class list from cache.
	 */
	private function loadCache(): void {
		if ($this->cacheLoaded) {
			return;
		}

		$this->cacheLoaded = true;

		$data = $this->dumpCache->loadCache($this->generateCacheKey());
		if (is_array($data)) {
			[$this->classes, $this->missingClasses] = $data;
			$this->loadsFromCache++;
			return;
		}

		$this->classes = $this->missingClasses = [];
		$this->refreshClasses();
		$this->saveCache();
	}

	/**
	 * Writes class list to cache.
	 * @param resource $lock
	 */
	private function saveCache(): void {
		$this->dumpCache->saveCache($this->generateCacheKey(), [$this->classes, $this->missingClasses]);
	}

	protected function generateCacheKey(): array {
		return [$this->psr4Paths,$this->ignoreDirs, $this->acceptFiles, $this->excludeDirs];
	}

	public function getStats(): array {
		return [
			'Loads from cache' => $this->loadsFromCache,
			'Disk scans' => $this->diskScans,
		];
	}

	public function serializeToArray(): array {
		return [
			'psr4Paths' => $this->psr4Paths,
			'excludeDirs' => $this->excludeDirs,
			'classes' => $this->classes,
			'missingClasses' => $this->missingClasses,
		];
	}

	public function loadFromArray(array $properties): void {
		$this->psr4Paths = $properties['psr4Paths'];
		$this->excludeDirs = $properties['excludeDirs'];
		$this->classes = $properties['classes'];
		$this->missingClasses = $properties['missingClasses'];

		/* Avoid any refresh */
		$this->cacheLoaded = true;
		$this->refreshed = true;
	}
}
