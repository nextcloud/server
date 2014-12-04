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
		$this->analysePhpVersion();
		$this->analyseSupportedDatabases();
		$this->analyseCommands();
		return $this->missing;
	}

	private function analysePhpVersion() {
		if (isset($this->dependencies['php']['@attributes']['min-version'])) {
			$minVersion = $this->dependencies['php']['@attributes']['min-version'];
			if (version_compare($this->platform->getPhpVersion(), $minVersion, '<')) {
				$this->addMissing((string)$this->l->t('PHP %s or higher is required.', $minVersion));
			}
		}
		if (isset($this->dependencies['php']['@attributes']['max-version'])) {
			$maxVersion = $this->dependencies['php']['@attributes']['max-version'];
			if (version_compare($this->platform->getPhpVersion(), $maxVersion, '>')) {
				$this->addMissing((string)$this->l->t('PHP with a version less then %s is required.', $maxVersion));
			}
		}
	}

	private function analyseSupportedDatabases() {
		if (!isset($this->dependencies['database'])) {
			return;
		}

		$supportedDatabases = $this->dependencies['database'];
		if (empty($supportedDatabases)) {
			return;
		}
		$supportedDatabases = array_map(function($db) {
			return $this->getValue($db);
		}, $supportedDatabases);
		$currentDatabase = $this->platform->getDatabase();
		if (!in_array($currentDatabase, $supportedDatabases)) {
			$this->addMissing((string)$this->l->t('Following databases are supported: %s', join(', ', $supportedDatabases)));
		}
	}

	private function analyseCommands() {
		if (!isset($this->dependencies['command'])) {
			return;
		}

		$commands = $this->dependencies['command'];
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

	/**
	 * @param $element
	 * @return mixed
	 */
	private function getValue($element) {
		if (isset($element['@value']))
			return $element['@value'];
		return strval($element);
	}

	/**
	 * @param $minVersion
	 */
	private function addMissing($message) {
		$this->missing[] = $message;
	}
}
