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
 * Rescan only ondemand?
 * Support PSR4 check (only allowed namespace in directory) (or even more performant scan based only on filename?)
 * Conditionnaly add apps folders without losing cache
 * Faire un scan que PSR4
 * Cacher par namespace ?
 * Pouvoir activer/désactiver un namespace ?
 * Comment gérer le missingClasses?
 * array [
 * 	classname => [
 * 		filepath,
 * 		rootnamespace (tel qu’ajouté, OCA\MonApp), (du coup ptet appid directement?)
 * ]] ?
 *
 * Actuellement les autoloaders sont enregistrés par le Coordinator pour faire le register
 * le loadApps du appManager a priori n’y retouche pas.
 * Ptet bouger registerAutoloading dans le app manager, y ajouter le support de plusieurs apps, optimiser et appeler ça partout. :bulb: TODO
 * Traquer les loads from cache et tenter d’en avoir un seul ou deux.
 * Remettre le support des autoloaders composer custom pour la BC, enlever ceux des applis de core.
 * Réparer les tests, nettoyer, pousser dans une PR
 */

class Autoloader {
	private const int RetryLimit = 1;

	/** @var string[] */
	public array $ignoreDirs = ['.*', '*.old', '*.bak', '*.tmp', 'temp'];

	/** @var string[] */
	public array $acceptFiles = ['*.php'];
	private bool $autoRebuild = false;
	private bool $reportParseErrors = true;

	/** @var string[] */
	private array $scanPaths = [];

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

	/** @var array<string, int>  file => mtime */
	private array $emptyFiles = [];
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
				// echo "missing from cache $type (skip search)\n";
				return;
			}

			[$file, $mtime] = $this->classes[$type] ?? null;

			if ($this->autoRebuild) {
				if (!$this->refreshed) {
					if (!$file || !is_file($file)) {
						$this->refreshClasses();
						[$file] = $this->classes[$type] ?? null;
						$this->needSave = true;

					} elseif (filemtime($file) !== $mtime) {
						$this->updateFile($file);
						[$file] = $this->classes[$type] ?? null;
						$this->needSave = true;
					}
				}

				if (!$file || !is_file($file)) {
					$this->missingClasses[$type] = ++$missing;
					$this->needSave = $this->needSave || $file || ($missing <= self::RetryLimit);
					unset($this->classes[$type]);
					$file = null;
				}
			}

			if ($file) {
				(static function ($file) {
					require $file;
				})($file);
				// } else {
				// echo "Did not find $type in ".print_r($this->scanPaths,true)."\n";
				// print_r($this->classes);
			}
		} catch (\Throwable $t) {
			echo "$t\n";
			throw $t;
		}
	}

	/**
	 * Add path or paths to list.
	 */
	public function addDirectory(string ...$paths): static {
		$this->scanPaths = array_merge($this->scanPaths, $paths);
		$this->refreshed = false;
		$this->cacheLoaded = false;
		return $this;
	}

	/**
	 * Add path or paths to list.
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
		$this->classes = $this->missingClasses = $this->emptyFiles = [];
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
	 * Refreshes $this->classes & $this->emptyFiles.
	 */
	private function refreshClasses(): void {
		// echo "REFRESH ".print_r($this->scanPaths,true)."\n";
		$this->refreshed = true; // prevents calling refreshClasses() or updateFile() in tryLoad()
		$files = $this->emptyFiles;
		$classes = [];
		foreach ($this->classes as $class => [$file, $mtime]) {
			$files[$file] = $mtime;
			$classes[$file][] = $class;
		}

		$this->classes = $this->emptyFiles = [];

		foreach ($this->scanPaths as $path) {
			// echo "path:$path\n";
			$iterator = is_file($path)
				? [$path]
				: $this->createFileIterator($path);

			foreach ($iterator as $file) {
				// echo "file:$file\n";
				$mtime = filemtime($file);
				$foundClasses = isset($files[$file]) && $files[$file] === $mtime
					? ($classes[$file] ?? [])
					: $this->scanPhp($file);

				if (!$foundClasses) {
					$this->emptyFiles[$file] = $mtime;
				}

				$files[$file] = $mtime;
				$classes[$file] = []; // prevents the error when adding the same file twice

				foreach ($foundClasses as $class) {
					if (isset($this->classes[$class])) {
						continue; //FIXME
						throw new \RuntimeException(sprintf(
							'Ambiguous class %s resolution; defined in %s and in %s.',
							$class,
							$this->classes[$class][0],
							$file,
						));
					}

					$this->classes[$class] = [$file, $mtime];
					unset($this->missingClasses[$class]);
				}
			}
		}

		foreach ($this->psr4Paths as $namespace => $path) {
			$iterator = $this->createFileIterator($path);
			$pathLen = strlen($path);
			foreach ($iterator as $file) {
				$class = $namespace . '\\' . str_replace('/', '\\', substr($file, $pathLen, -4));
				if (isset($this->classes[$class])) {
					continue; //FIXME
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

	private function updateFile(string $file): void {
		foreach ($this->classes as $class => [$prevFile]) {
			if ($file === $prevFile) {
				unset($this->classes[$class]);
			}
		}

		$foundClasses = is_file($file) ? $this->scanPhp($file) : [];

		foreach ($foundClasses as $class) {
			[$prevFile, $prevMtime] = $this->classes[$class] ?? null;

			if (isset($prevFile) && @filemtime($prevFile) !== $prevMtime) { // @ file may not exist
				$this->updateFile($prevFile);
				[$prevFile] = $this->classes[$class] ?? null;
			}

			if (isset($prevFile)) {
				throw new \RuntimeException(sprintf(
					'Ambiguous class %s resolution; defined in %s and in %s.',
					$class,
					$prevFile,
					$file,
				));
			}

			$this->classes[$class] = [$file, filemtime($file)];
		}
	}

	/**
	 * Searches classes, interfaces and traits in PHP file.
	 * @return string[]
	 */
	private function scanPhp(string $file): array {
		$code = file_get_contents($file);
		$expected = false;
		$namespace = $name = '';
		$level = $minLevel = 0;
		$classes = [];

		try {
			$tokens = \PhpToken::tokenize($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
			if ($this->reportParseErrors) {
				$rp = new \ReflectionProperty($e, 'file');
				$rp->setAccessible(true);
				$rp->setValue($e, $file);
				throw $e;
			}

			$tokens = [];
		}

		foreach ($tokens as $token) {
			switch ($token->id) {
				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_WHITESPACE:
					continue 2;

				case T_STRING:
				case T_NAME_QUALIFIED:
					if ($expected) {
						$name .= $token->text;
					}

					continue 2;

				case T_NAMESPACE:
				case T_CLASS:
				case T_INTERFACE:
				case T_TRAIT:
				case PHP_VERSION_ID < 80100
					? T_CLASS
				: T_ENUM:
				$expected = $token->id;
					$name = '';
					continue 2;
			}

			if ($expected) {
				if ($expected === T_NAMESPACE) {
					$namespace = $name ? $name . '\\' : '';
					$minLevel = $token->text === '{' ? 1 : 0;

				} elseif ($name && $level === $minLevel) {
					$classes[] = $namespace . $name;
				}

				$expected = null;
			}

			if ($token->text === '{') {
				$level++;
			} elseif ($token->text === '}') {
				$level--;
			}
		}

		return $classes;
	}

	/********************* caching ****************d*g**/

	/**
	 * Sets auto-refresh mode.
	 */
	public function setAutoRefresh(bool $on = true): static {
		$this->autoRebuild = $on;
		return $this;
	}

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
			[$this->classes, $this->missingClasses, $this->emptyFiles] = $data;
			$this->loadsFromCache++;
			return;
		}

		$this->classes = $this->missingClasses = $this->emptyFiles = [];
		$this->refreshClasses();
		$this->saveCache();
	}

	/**
	 * Writes class list to cache.
	 * @param resource $lock
	 */
	private function saveCache(): void {
		$this->dumpCache->saveCache($this->generateCacheKey(), [$this->classes, $this->missingClasses, $this->emptyFiles]);
	}

	protected function generateCacheKey(): array {
		return [$this->psr4Paths,$this->ignoreDirs, $this->acceptFiles, $this->scanPaths, $this->excludeDirs];
	}

	public function getStats(): array {
		return [
			'Loads from cache' => $this->loadsFromCache,
			'Disk scans' => $this->diskScans,
		];
	}
}
