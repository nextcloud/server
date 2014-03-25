<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use OC\Hooks\BasicEmitter;

class Repair extends BasicEmitter {
	private $stepClasses;

	/**
	 * Creates a new repair step runner
	 *
	 * @param array $stepClasses optional list of step classes
	 */
	public function __construct($stepClasses = array()) {
		$this->stepClasses = $stepClasses;
	}

	/**
	 * Run a series of repair steps for common problems
	 */
	public function run() {
		$steps = array();

		// instantiate all classes, just to make
		// sure they all exist before starting
		foreach ($this->stepClasses as $className) {
			$steps[] = new $className();
		}

		$self = $this;
		// run each repair step
		foreach ($steps as $step) {
			$this->emit('\OC\Repair', 'step', array($step->getName()));

			$step->listen('\OC\Repair', 'error', function ($description) use ($self) {
				$self->emit('\OC\Repair', 'error', array($description));
			});
			$step->listen('\OC\Repair', 'info', function ($description) use ($self) {
				$self->emit('\OC\Repair', 'info', array($description));
			});
			$step->run();
		}
	}

	/**
	 * Add repair step class
	 *
	 * @param string $className name of a repair step class
	 */
	public function addStep($className) {
		$this->stepClasses[] = $className;
	}

}
