<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OC\RepairStep;
use Sabre\DAV\Exception;

class RepairConfig extends BasicEmitter implements RepairStep {

	public function getName() {
		return 'Repair config';
	}

	/**
	 * Updates the configuration after running an update
	 */
	public function run() {
		$this->addSecret();
	}

	/**
	 * Adds a secret to config.php
	 */
	private function addSecret() {
		if(\OC::$server->getConfig()->getSystemValue('secret', null) === null) {
			$secret = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(48);
			\OC::$server->getConfig()->setSystemValue('secret', $secret);
		}
	}
}
