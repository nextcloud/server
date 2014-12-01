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

	/**
	 * @param array $app
	 * @param Platform $system
	 * @param \OCP\IL10N $l
	 */
	function __construct(array $app, $system, $l) {
		$this->system = $system;
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
		return $this->missing;
	}

	private function analysePhpVersion() {
		if (!array_key_exists('php', $this->dependencies)) {
			return;
		}

		if (array_key_exists('min-version', $this->dependencies['php'])) {
			$minVersion = $this->dependencies['php']['min-version'];
			if (version_compare($this->system->getPhpVersion(), $minVersion, '<')) {
				$this->missing[] = (string)$this->l->t('PHP %s or higher is required.', $minVersion);
			}
		}
		if (array_key_exists('max-version', $this->dependencies['php'])) {
			$maxVersion = $this->dependencies['php']['max-version'];
			if (version_compare($this->system->getPhpVersion(), $maxVersion, '>')) {
				$this->missing[] = (string)$this->l->t('PHP with a version less then %s is required.', $maxVersion);
			}
		}
	}

}
