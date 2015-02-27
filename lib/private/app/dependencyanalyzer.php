<?php
/**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\App;

use OCP\IL10N;

class DependencyAnalyzer {

	/** @var Platform */
	private $platform;

	/** @var \OCP\IL10N */
	private $l;

	/**
	 * @param Platform $platform
	 * @param \OCP\IL10N $l
	 */
	function __construct(Platform $platform, IL10N $l) {
		$this->platform = $platform;
		$this->l = $l;
	}

	/**
	 * @param array $app
	 * @returns array of missing dependencies
	 */
	public function analyze($app) {
		$this->appInfo = $app;
		if (isset($app['dependencies'])) {
			$dependencies = $app['dependencies'];
		} else {
			$dependencies = [];
		}

		return array_merge(
			$this->analyzePhpVersion($dependencies),
			$this->analyzeDatabases($dependencies),
			$this->analyzeCommands($dependencies),
			$this->analyzeLibraries($dependencies),
			$this->analyzeOS($dependencies),
			$this->analyzeOC($dependencies, $app));
	}

	/**
	 * Truncates both verions to the lowest common version, e.g.
	 * 5.1.2.3 and 5.1 will be turned into 5.1 and 5.1,
	 * 5.2.6.5 and 5.1 will be turned into 5.2 and 5.1
	 * @param string $first
	 * @param string $second
	 * @return array first element is the first version, second element is the
	 * second version
	 */
	private function normalizeVersions($first, $second) {
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
	 * @param string $first
	 * @param string $second
	 * @param string $operator
	 * @return bool result similar to version_compare
	 */
	private function compare($first, $second, $operator) {
		// we cant normalize versions if one of the given parameters is not a
		// version string but null. In case one parameter is null normalization
		// will therefore be skipped
		if ($first !== null && $second !== null) {
			list($first, $second) = $this->normalizeVersions($first, $second);
		}

		return version_compare($first, $second, $operator);
	}

	/**
	 * Checks if a version is bigger than another version
	 * @param string $first
	 * @param string $second
	 * @return bool true if the first version is bigger than the second
	 */
	private function compareBigger($first, $second) {
		return $this->compare($first, $second, '>');
	}

	/**
	 * Checks if a version is smaller than another version
	 * @param string $first
	 * @param string $second
	 * @return bool true if the first version is smaller than the second
	 */
	private function compareSmaller($first, $second) {
		return $this->compare($first, $second, '<');
	}

	private function analyzePhpVersion($dependencies) {
		$missing = [];
		if (isset($dependencies['php']['@attributes']['min-version'])) {
			$minVersion = $dependencies['php']['@attributes']['min-version'];
			if ($this->compareSmaller($this->platform->getPhpVersion(), $minVersion)) {
				$missing[] = (string)$this->l->t('PHP %s or higher is required.', $minVersion);
			}
		}
		if (isset($dependencies['php']['@attributes']['max-version'])) {
			$maxVersion = $dependencies['php']['@attributes']['max-version'];
			if ($this->compareBigger($this->platform->getPhpVersion(), $maxVersion)) {
				$missing[] = (string)$this->l->t('PHP with a version lower than %s is required.', $maxVersion);
			}
		}
		return $missing;
	}

	private function analyzeDatabases($dependencies) {
		$missing = [];
		if (!isset($dependencies['database'])) {
			return $missing;
		}

		$supportedDatabases = $dependencies['database'];
		if (empty($supportedDatabases)) {
			return $missing;
		}
		if (!is_array($supportedDatabases)) {
			$supportedDatabases = array($supportedDatabases);
		}
		$supportedDatabases = array_map(function ($db) {
			return $this->getValue($db);
		}, $supportedDatabases);
		$currentDatabase = $this->platform->getDatabase();
		if (!in_array($currentDatabase, $supportedDatabases)) {
			$missing[] = (string)$this->l->t('Following databases are supported: %s', join(', ', $supportedDatabases));
		}
		return $missing;
	}

	private function analyzeCommands($dependencies) {
		$missing = [];
		if (!isset($dependencies['command'])) {
			return $missing;
		}

		$commands = $dependencies['command'];
		if (!is_array($commands)) {
			$commands = array($commands);
		}
		$os = $this->platform->getOS();
		foreach ($commands as $command) {
			if (isset($command['@attributes']['os']) && $command['@attributes']['os'] !== $os) {
				continue;
			}
			$commandName = $this->getValue($command);
			if (!$this->platform->isCommandKnown($commandName)) {
				$missing[] = (string)$this->l->t('The command line tool %s could not be found', $commandName);
			}
		}
		return $missing;
	}

	private function analyzeLibraries($dependencies) {
		$missing = [];
		if (!isset($dependencies['lib'])) {
			return $missing;
		}

		$libs = $dependencies['lib'];
		if (!is_array($libs)) {
			$libs = array($libs);
		}
		foreach ($libs as $lib) {
			$libName = $this->getValue($lib);
			$libVersion = $this->platform->getLibraryVersion($libName);
			if (is_null($libVersion)) {
				$missing[] = (string)$this->l->t('The library %s is not available.', $libName);
				continue;
			}

			if (is_array($lib)) {
				if (isset($lib['@attributes']['min-version'])) {
					$minVersion = $lib['@attributes']['min-version'];
					if ($this->compareSmaller($libVersion, $minVersion)) {
						$missing[] = (string)$this->l->t('Library %s with a version higher than %s is required - available version %s.',
							array($libName, $minVersion, $libVersion));
					}
				}
				if (isset($lib['@attributes']['max-version'])) {
					$maxVersion = $lib['@attributes']['max-version'];
					if ($this->compareBigger($libVersion, $maxVersion)) {
						$missing[] = (string)$this->l->t('Library %s with a version lower than %s is required - available version %s.',
							array($libName, $maxVersion, $libVersion));
					}
				}
			}
		}
		return $missing;
	}

	private function analyzeOS($dependencies) {
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
			$oss = array($oss);
		}
		$currentOS = $this->platform->getOS();
		if (!in_array($currentOS, $oss)) {
			$missing[] = (string)$this->l->t('Following platforms are supported: %s', join(', ', $oss));
		}
		return $missing;
	}

	private function analyzeOC($dependencies, $appInfo) {
		$missing = [];
		$minVersion = null;
		if (isset($dependencies['owncloud']['@attributes']['min-version'])) {
			$minVersion = $dependencies['owncloud']['@attributes']['min-version'];
		} elseif (isset($appInfo['requiremin'])) {
			$minVersion = $appInfo['requiremin'];
		} elseif (isset($appInfo['require'])) {
			$minVersion = $appInfo['require'];
		}
		$maxVersion = null;
		if (isset($dependencies['owncloud']['@attributes']['max-version'])) {
			$maxVersion = $dependencies['owncloud']['@attributes']['max-version'];
		} elseif (isset($appInfo['requiremax'])) {
			$maxVersion = $appInfo['requiremax'];
		}

		if (!is_null($minVersion)) {
			if ($this->compareSmaller($this->platform->getOcVersion(), $minVersion)) {
				$missing[] = (string)$this->l->t('ownCloud %s or higher is required.', $minVersion);
			}
		}
		if (!is_null($maxVersion)) {
			if ($this->compareBigger($this->platform->getOcVersion(), $maxVersion)) {
				$missing[] = (string)$this->l->t('ownCloud with a version lower than %s is required.', $maxVersion);
			}
		}
		return $missing;
	}

	/**
	 * @param $element
	 * @return mixed
	 */
	private function getValue($element) {
		if (isset($element['@value']))
			return $element['@value'];
		return (string)$element;
	}
}
