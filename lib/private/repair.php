<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use OC\Hooks\BasicEmitter;
use OC\Hooks\Emitter;
use OC\Repair\AssetCache;
use OC\Repair\CleanTags;
use OC\Repair\Collation;
use OC\Repair\EnableFilesApp;
use OC\Repair\FillETags;
use OC\Repair\InnoDB;
use OC\Repair\RepairConfig;
use OC\Repair\RepairLegacyStorages;
use OC\Repair\RepairMimeTypes;
use OC\Repair\SearchLuceneTables;

class Repair extends BasicEmitter {
	/**
	 * @var RepairStep[]
	 **/
	private $repairSteps;

	/**
	 * Creates a new repair step runner
	 *
	 * @param array $repairSteps array of RepairStep instances
	 */
	public function __construct($repairSteps = array()) {
		$this->repairSteps = $repairSteps;
	}

	/**
	 * Run a series of repair steps for common problems
	 */
	public function run() {
		$self = $this;
		if (count($this->repairSteps) === 0) {
			$this->emit('\OC\Repair', 'info', array('No repair steps available'));
			return;
		}
		// run each repair step
		foreach ($this->repairSteps as $step) {
			$this->emit('\OC\Repair', 'step', array($step->getName()));

			if ($step instanceof Emitter) {
				$step->listen('\OC\Repair', 'warning', function ($description) use ($self) {
					$self->emit('\OC\Repair', 'warning', array($description));
				});
				$step->listen('\OC\Repair', 'info', function ($description) use ($self) {
					$self->emit('\OC\Repair', 'info', array($description));
				});
			}

			$step->run();
		}
	}

	/**
	 * Add repair step
	 *
	 * @param RepairStep $repairStep repair step
	 */
	public function addStep($repairStep) {
		$this->repairSteps[] = $repairStep;
	}

	/**
	 * Returns the default repair steps to be run on the
	 * command line or after an upgrade.
	 *
	 * @return array of RepairStep instances
	 */
	public static function getRepairSteps() {
		return array(
			new RepairMimeTypes(),
			new RepairLegacyStorages(\OC::$server->getConfig(), \OC_DB::getConnection()),
			new RepairConfig(),
			new AssetCache(),
			new FillETags(\OC_DB::getConnection()),
			new CleanTags(\OC_DB::getConnection()),
			new EnableFilesApp(\OC::$server->getConfig()),
		);
	}

	/**
	 * Returns the repair steps to be run before an
	 * upgrade.
	 *
	 * @return array of RepairStep instances
	 */
	public static function getBeforeUpgradeRepairSteps() {
		$steps = array(
			new InnoDB(),
			new Collation(\OC::$server->getConfig(), \OC_DB::getConnection()),
			new SearchLuceneTables(),
			new RepairConfig()
		);

		//There is no need to delete all previews on every single update
		//only 7.0.0 through 7.0.2 generated broken previews
		$currentVersion = \OC::$server->getConfig()->getSystemValue('version');
		if (version_compare($currentVersion, '7.0.0.0', '>=') &&
			version_compare($currentVersion, '7.0.3.4', '<=')) {
			$steps[] = new \OC\Repair\Preview();
		}

		return $steps;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Re-declared as public to allow invocation from within the closure above in php 5.3
	 */
	public function emit($scope, $method, $arguments = array()) {
		parent::emit($scope, $method, $arguments);
	}
}
