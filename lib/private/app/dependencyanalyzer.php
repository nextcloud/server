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

	private function analyzePhpVersion($dependencies) {
		$missing = [];
		if (isset($dependencies['php']['@attributes']['min-version'])) {
			$minVersion = $dependencies['php']['@attributes']['min-version'];
			if (version_compare($this->platform->getPhpVersion(), $minVersion, '<')) {
				$missing[] = (string)$this->l->t('PHP %s or higher is required.', $minVersion);
			}
		}
		if (isset($dependencies['php']['@attributes']['max-version'])) {
			$maxVersion = $dependencies['php']['@attributes']['max-version'];
			if (version_compare($this->platform->getPhpVersion(), $maxVersion, '>')) {
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
					if (version_compare($libVersion, $minVersion, '<')) {
						$missing[] = (string)$this->l->t('Library %s with a version higher than %s is required - available version %s.',
							array($libName, $minVersion, $libVersion));
					}
				}
				if (isset($lib['@attributes']['max-version'])) {
					$maxVersion = $lib['@attributes']['max-version'];
					if (version_compare($libVersion, $maxVersion, '>')) {
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
		}
		$maxVersion = null;
		if (isset($dependencies['owncloud']['@attributes']['max-version'])) {
			$maxVersion = $dependencies['owncloud']['@attributes']['max-version'];
		} elseif (isset($appInfo['requiremax'])) {
			$maxVersion = $appInfo['requiremax'];
		}

		if (!is_null($minVersion)) {
			if (version_compare($this->platform->getOcVersion(), $minVersion, '<')) {
				$missing[] = (string)$this->l->t('ownCloud %s or higher is required.', $minVersion);
			}
		}
		if (!is_null($maxVersion)) {
			if (version_compare($this->platform->getOcVersion(), $maxVersion, '>')) {
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
