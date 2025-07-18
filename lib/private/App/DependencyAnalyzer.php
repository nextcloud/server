<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\App;

use OCP\IL10N;

class DependencyAnalyzer {
	public function __construct(
		private Platform $platform,
		private IL10N $l,
	) {
	}

	/**
	 * @return array of missing dependencies
	 */
	public function analyze(array $app, bool $ignoreMax = false): array {
		if (isset($app['dependencies'])) {
			$dependencies = $app['dependencies'];
		} else {
			$dependencies = [];
		}

		return array_merge(
			$this->analyzeArchitecture($dependencies),
			$this->analyzePhpVersion($dependencies),
			$this->analyzeDatabases($dependencies),
			$this->analyzeCommands($dependencies),
			$this->analyzeLibraries($dependencies),
			$this->analyzeOS($dependencies),
			$this->analyzeOC($dependencies, $app, $ignoreMax)
		);
	}

	public function isMarkedCompatible(array $app): bool {
		if (isset($app['dependencies'])) {
			$dependencies = $app['dependencies'];
		} else {
			$dependencies = [];
		}

		$maxVersion = $this->getMaxVersion($dependencies, $app);
		if ($maxVersion === null) {
			return true;
		}
		return !$this->compareBigger($this->platform->getOcVersion(), $maxVersion);
	}

	/**
	 * Truncates both versions to the lowest common version, e.g.
	 * 5.1.2.3 and 5.1 will be turned into 5.1 and 5.1,
	 * 5.2.6.5 and 5.1 will be turned into 5.2 and 5.1
	 * @return string[] first element is the first version, second element is the
	 *                  second version
	 */
	private function normalizeVersions(string $first, string $second): array {
		$first = explode('.', $first);
		$second = explode('.', $second);

		// get both arrays to the same minimum size
		$length = min(count($second), count($first));
		$first = array_slice($first, 0, $length);
		$second = array_slice($second, 0, $length);

		return [implode('.', $first), implode('.', $second)];
	}

	/**
	 * Parameters will be normalized and then passed into version_compare
	 * in the same order they are specified in the method header
	 * @return bool result similar to version_compare
	 */
	private function compare(string $first, string $second, string $operator): bool {
		[$first, $second] = $this->normalizeVersions($first, $second);

		return version_compare($first, $second, $operator);
	}

	/**
	 * Checks if a version is bigger than another version
	 * @return bool true if the first version is bigger than the second
	 */
	private function compareBigger(string $first, string $second): bool {
		return $this->compare($first, $second, '>');
	}

	/**
	 * Checks if a version is smaller than another version
	 * @return bool true if the first version is smaller than the second
	 */
	private function compareSmaller(string $first, string $second): bool {
		return $this->compare($first, $second, '<');
	}

	private function analyzePhpVersion(array $dependencies): array {
		$missing = [];
		if (isset($dependencies['php']['@attributes']['min-version'])) {
			$minVersion = $dependencies['php']['@attributes']['min-version'];
			if ($this->compareSmaller($this->platform->getPhpVersion(), $minVersion)) {
				$missing[] = $this->l->t('PHP %s or higher is required.', [$minVersion]);
			}
		}
		if (isset($dependencies['php']['@attributes']['max-version'])) {
			$maxVersion = $dependencies['php']['@attributes']['max-version'];
			if ($this->compareBigger($this->platform->getPhpVersion(), $maxVersion)) {
				$missing[] = $this->l->t('PHP with a version lower than %s is required.', [$maxVersion]);
			}
		}
		if (isset($dependencies['php']['@attributes']['min-int-size'])) {
			$intSize = $dependencies['php']['@attributes']['min-int-size'];
			if ($intSize > $this->platform->getIntSize() * 8) {
				$missing[] = $this->l->t('%sbit or higher PHP required.', [$intSize]);
			}
		}
		return $missing;
	}

	private function analyzeArchitecture(array $dependencies): array {
		$missing = [];
		if (!isset($dependencies['architecture'])) {
			return $missing;
		}

		$supportedArchitectures = $dependencies['architecture'];
		if (empty($supportedArchitectures)) {
			return $missing;
		}
		if (!is_array($supportedArchitectures)) {
			$supportedArchitectures = [$supportedArchitectures];
		}
		$supportedArchitectures = array_map(function ($architecture) {
			return $this->getValue($architecture);
		}, $supportedArchitectures);
		$currentArchitecture = $this->platform->getArchitecture();
		if (!in_array($currentArchitecture, $supportedArchitectures, true)) {
			$missing[] = $this->l->t('The following architectures are supported: %s', [implode(', ', $supportedArchitectures)]);
		}
		return $missing;
	}

	private function analyzeDatabases(array $dependencies): array {
		$missing = [];
		if (!isset($dependencies['database'])) {
			return $missing;
		}

		$supportedDatabases = $dependencies['database'];
		if (empty($supportedDatabases)) {
			return $missing;
		}
		if (!is_array($supportedDatabases)) {
			$supportedDatabases = [$supportedDatabases];
		}
		if (isset($supportedDatabases['@value'])) {
			$supportedDatabases = [$supportedDatabases];
		}
		$supportedDatabases = array_map(function ($db) {
			return $this->getValue($db);
		}, $supportedDatabases);
		$currentDatabase = $this->platform->getDatabase();
		if (!in_array($currentDatabase, $supportedDatabases)) {
			$missing[] = $this->l->t('The following databases are supported: %s', [implode(', ', $supportedDatabases)]);
		}
		return $missing;
	}

	private function analyzeCommands(array $dependencies): array {
		$missing = [];
		if (!isset($dependencies['command'])) {
			return $missing;
		}

		$commands = $dependencies['command'];
		if (!is_array($commands)) {
			$commands = [$commands];
		}
		if (isset($commands['@value'])) {
			$commands = [$commands];
		}
		$os = $this->platform->getOS();
		foreach ($commands as $command) {
			if (isset($command['@attributes']['os']) && $command['@attributes']['os'] !== $os) {
				continue;
			}
			$commandName = $this->getValue($command);
			if (!$this->platform->isCommandKnown($commandName)) {
				$missing[] = $this->l->t('The command line tool %s could not be found', [$commandName]);
			}
		}
		return $missing;
	}

	private function analyzeLibraries(array $dependencies): array {
		$missing = [];
		if (!isset($dependencies['lib'])) {
			return $missing;
		}

		$libs = $dependencies['lib'];
		if (!is_array($libs)) {
			$libs = [$libs];
		}
		if (isset($libs['@value'])) {
			$libs = [$libs];
		}
		foreach ($libs as $lib) {
			$libName = $this->getValue($lib);
			$libVersion = $this->platform->getLibraryVersion($libName);
			if (is_null($libVersion)) {
				$missing[] = $this->l->t('The library %s is not available.', [$libName]);
				continue;
			}

			if (is_array($lib)) {
				if (isset($lib['@attributes']['min-version'])) {
					$minVersion = $lib['@attributes']['min-version'];
					if ($this->compareSmaller($libVersion, $minVersion)) {
						$missing[] = $this->l->t('Library %1$s with a version higher than %2$s is required - available version %3$s.',
							[$libName, $minVersion, $libVersion]);
					}
				}
				if (isset($lib['@attributes']['max-version'])) {
					$maxVersion = $lib['@attributes']['max-version'];
					if ($this->compareBigger($libVersion, $maxVersion)) {
						$missing[] = $this->l->t('Library %1$s with a version lower than %2$s is required - available version %3$s.',
							[$libName, $maxVersion, $libVersion]);
					}
				}
			}
		}
		return $missing;
	}

	private function analyzeOS(array $dependencies): array {
		$missing = [];
		if (!isset($dependencies['os'])) {
			return $missing;
		}

		$oss = $dependencies['os'];
		if (empty($oss)) {
			return $missing;
		}
		if (is_array($oss)) {
			$oss = array_map(function ($os) {
				return $this->getValue($os);
			}, $oss);
		} else {
			$oss = [$oss];
		}
		$currentOS = $this->platform->getOS();
		if (!in_array($currentOS, $oss)) {
			$missing[] = $this->l->t('The following platforms are supported: %s', [implode(', ', $oss)]);
		}
		return $missing;
	}

	private function analyzeOC(array $dependencies, array $appInfo, bool $ignoreMax): array {
		$missing = [];
		$minVersion = null;
		if (isset($dependencies['nextcloud']['@attributes']['min-version'])) {
			$minVersion = $dependencies['nextcloud']['@attributes']['min-version'];
		} elseif (isset($dependencies['owncloud']['@attributes']['min-version'])) {
			$minVersion = $dependencies['owncloud']['@attributes']['min-version'];
		} elseif (isset($appInfo['requiremin'])) {
			$minVersion = $appInfo['requiremin'];
		} elseif (isset($appInfo['require'])) {
			$minVersion = $appInfo['require'];
		}
		$maxVersion = $this->getMaxVersion($dependencies, $appInfo);

		if (!is_null($minVersion)) {
			if ($this->compareSmaller($this->platform->getOcVersion(), $minVersion)) {
				$missing[] = $this->l->t('Server version %s or higher is required.', [$minVersion]);
			}
		}
		if (!$ignoreMax && !is_null($maxVersion)) {
			if ($this->compareBigger($this->platform->getOcVersion(), $maxVersion)) {
				$missing[] = $this->l->t('Server version %s or lower is required.', [$maxVersion]);
			}
		}
		return $missing;
	}

	private function getMaxVersion(array $dependencies, array $appInfo): ?string {
		if (isset($dependencies['nextcloud']['@attributes']['max-version'])) {
			return $dependencies['nextcloud']['@attributes']['max-version'];
		}
		if (isset($dependencies['owncloud']['@attributes']['max-version'])) {
			return $dependencies['owncloud']['@attributes']['max-version'];
		}
		if (isset($appInfo['requiremax'])) {
			return $appInfo['requiremax'];
		}

		return null;
	}

	/**
	 * @param mixed $element
	 * @return mixed
	 */
	private function getValue($element) {
		if (isset($element['@value'])) {
			return $element['@value'];
		}
		return (string)$element;
	}
}
