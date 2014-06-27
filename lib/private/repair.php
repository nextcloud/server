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
			new \OC\Repair\RepairMimeTypes()
		);
	}

	/**
	 * Returns the repair steps to be run before an
	 * upgrade.
	 *
	 * @return array of RepairStep instances
	 */
	public static function getBeforeUpgradeRepairSteps() {
		return array(
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Redeclared as public to allow invocation from within the closure above in php 5.3
	 */
	public function emit($scope, $method, $arguments = array()) {
		parent::emit($scope, $method, $arguments);
	}
}
