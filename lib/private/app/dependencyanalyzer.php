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

	/** @var array  */
	private $missing = array();

	/** @var array  */
	private $dependencies = array();

	private $appInfo = array();

	/**
	 * @param array $app
	 * @param Platform $platform
	 * @param \OCP\IL10N $l
	 */
	function __construct(array $app, Platform $platform, IL10N $l) {
		$this->platform = $platform;
		$this->l = $l;
		if (isset($app['dependencies'])) {
			$this->dependencies = $app['dependencies'];
		}
	}

	/**
	 * @param array $app
	 * @returns array of missing dependencies
	 */
	public function analyze() {
		$this->analyzePhpVersion();
		$this->analyzeDatabases();
		$this->analyzeCommands();
		$this->analyzeLibraries();
		$this->analyzeOS();
		$this->analyzeOC();
		return $this->missing;
	}

	private function analyzePhpVersion() {
		if (isset($this->dependencies['php']['@attributes']['min-version'])) {
			$minVersion = $this->dependencies['php']['@attributes']['min-version'];
			if (version_compare($this->platform->getPhpVersion(), $minVersion, '<')) {
				$this->addMissing((string)$this->l->t('PHP %s or higher is required.', $minVersion));
			}
		}
		if (isset($this->dependencies['php']['@attributes']['max-version'])) {
			$maxVersion = $this->dependencies['php']['@attributes']['max-version'];
			if (version_compare($this->platform->getPhpVersion(), $maxVersion, '>')) {
				$this->addMissing((string)$this->l->t('PHP with a version lower than %s is required.', $maxVersion));
			}
		}
	}

	private function analyzeDatabases() {
		if (!isset($this->dependencies['database'])) {
			return;
		}

		$supportedDatabases = $this->dependencies['database'];
		if (empty($supportedDatabases)) {
			return;
		}
		if (!is_array($supportedDatabases)) {
			$supportedDatabases = array($supportedDatabases);
		}
		$supportedDatabases = array_map(function($db) {
			return $this->getValue($db);
		}, $supportedDatabases);
		$currentDatabase = $this->platform->getDatabase();
		if (!in_array($currentDatabase, $supportedDatabases)) {
			$this->addMissing((string)$this->l->t('Following databases are supported: %s', join(', ', $supportedDatabases)));
		}
	}

	private function analyzeCommands() {
		if (!isset($this->dependencies['command'])) {
			return;
		}

		$commands = $this->dependencies['command'];
		if (!is_array($commands)) {
			$commands = array($commands);
		}
		$os = $this->platform->getOS();
		foreach($commands as $command) {
			if (isset($command['@attributes']['os']) && $command['@attributes']['os'] !== $os) {
				continue;
			}
			$commandName = $this->getValue($command);
			if (!$this->platform->isCommandKnown($commandName)) {
				$this->addMissing((string)$this->l->t('The command line tool %s could not be found', $commandName));
			}
		}
	}

	private function analyzeLibraries() {
		if (!isset($this->dependencies['lib'])) {
			return;
		}

		$libs = $this->dependencies['lib'];
		if (!is_array($libs)) {
			$libs = array($libs);
		}
		foreach($libs as $lib) {
			$libName = $this->getValue($lib);
			$libVersion = $this->platform->getLibraryVersion($libName);
			if (is_null($libVersion)) {
				$this->addMissing((string)$this->l->t('The library %s is not available.', $libName));
				continue;
			}

			if (is_array($lib)) {
				if (isset($lib['@attributes']['min-version'])) {
					$minVersion = $lib['@attributes']['min-version'];
					if (version_compare($libVersion, $minVersion, '<')) {
						$this->addMissing((string)$this->l->t('Library %s with a version higher than %s is required - available version %s.',
							array($libName, $minVersion, $libVersion)));
					}
				}
				if (isset($lib['@attributes']['max-version'])) {
					$maxVersion = $lib['@attributes']['max-version'];
					if (version_compare($libVersion, $maxVersion, '>')) {
						$this->addMissing((string)$this->l->t('Library %s with a version lower than %s is required - available version %s.',
							array($libName, $maxVersion, $libVersion)));
					}
				}
			}
		}
	}

	private function analyzeOS() {
		if (!isset($this->dependencies['os'])) {
			return;
		}

		$oss = $this->dependencies['os'];
		if (empty($oss)) {
			return;
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
			$this->addMissing((string)$this->l->t('Following platforms are supported: %s', join(', ', $oss)));
		}
	}

	private function analyzeOC() {
		$minVersion = null;
		if (isset($this->dependencies['owncloud']['@attributes']['min-version'])) {
			$minVersion = $this->dependencies['owncloud']['@attributes']['min-version'];
		} elseif (isset($this->appInfo['requiremin'])) {
			$minVersion = $this->appInfo['requiremin'];
		}
		$maxVersion = null;
		if (isset($this->dependencies['owncloud']['@attributes']['max-version'])) {
			$maxVersion = $this->dependencies['owncloud']['@attributes']['max-version'];
		} elseif (isset($this->appInfo['requiremax'])) {
			$maxVersion = $this->appInfo['requiremax'];
		}

		if (!is_null($minVersion)) {
			if (version_compare($this->platform->getOcVersion(), $minVersion, '<')) {
				$this->addMissing((string)$this->l->t('ownCloud %s or higher is required.', $minVersion));
			}
		}
		if (!is_null($maxVersion)) {
			if (version_compare($this->platform->getOcVersion(), $maxVersion, '>')) {
				$this->addMissing((string)$this->l->t('ownCloud with a version lower than %s is required.', $maxVersion));
			}
		}
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

	/**
	 * @param $minVersion
	 */
	private function addMissing($message) {
		$this->missing[] = $message;
	}
}
