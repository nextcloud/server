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

class DependencyAnalyzer {

	/** @var Platform */
	private $system;

	/** @var \OCP\IL10N */
	private $l;

	/** @var array  */
	private $missing;

	/** @var array  */
	private $dependencies;

	/**
	 * @param array $app
	 * @param Platform $platform
	 * @param \OCP\IL10N $l
	 */
	function __construct(array $app, $platform, $l) {
		$this->system = $platform;
		$this->l = $l;
		$this->missing = array();
		$this->dependencies = array();
		if (array_key_exists('dependencies', $app)) {
			$this->dependencies = $app['dependencies'];
		}
	}

	/**
	 * @param array $app
	 * @returns array of missing dependencies
	 */
	public function analyze() {
		$this->analysePhpVersion();
		$this->analyseSupportedDatabases();
		return $this->missing;
	}

	private function analysePhpVersion() {
		if (isset($this->dependencies['php']['@attributes']['min-version'])) {
			$minVersion = $this->dependencies['php']['@attributes']['min-version'];
			if (version_compare($this->system->getPhpVersion(), $minVersion, '<')) {
				$this->missing[] = (string)$this->l->t('PHP %s or higher is required.', $minVersion);
			}
		}
		if (isset($this->dependencies['php']['@attributes']['max-version'])) {
			$maxVersion = $this->dependencies['php']['@attributes']['max-version'];
			if (version_compare($this->system->getPhpVersion(), $maxVersion, '>')) {
				$this->missing[] = (string)$this->l->t('PHP with a version less then %s is required.', $maxVersion);
			}
		}
	}

	private function analyseSupportedDatabases() {
		if (!isset($this->dependencies['databases'])) {
			return;
		}

		$supportedDatabases = $this->dependencies['databases'];
		if (empty($supportedDatabases)) {
			return;
		}
		$supportedDatabases = array_map(function($db) {
			if (isset($db['@value'])) {
				return $db['@value'];
			}
			return $db;
		}, $supportedDatabases);
		$currentDatabase = $this->system->getDatabase();
		if (!in_array($currentDatabase, $supportedDatabases)) {
			$this->missing[] = (string)$this->l->t('Following databases are supported: %s', join(', ', $supportedDatabases));
		}
	}
}
