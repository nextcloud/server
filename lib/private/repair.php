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
	/**
	 * run a series of repair steps for common problems
	 * progress can be reported by emitting \OC\Repair::step events
	 */
	public function run() {
		$this->emit('\OC\Repair', 'step', array('No repair steps configured at the moment'));
	}
}
