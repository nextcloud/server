<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OC\RepairStep;
use Sabre\DAV\Exception;

/**
 * Class RepairConfig
 *
 * @package OC\Repair
 */
class RepairConfig extends BasicEmitter implements RepairStep {

	/**
	 * @return string
	 */
	public function getName() {
		return 'Repair config';
	}

	/**
	 * Updates the configuration after running an update
	 */
	public function run() {
		$this->addSecret();
		$this->removePortsFromTrustedDomains();
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


	/**
	 * Remove ports from existing trusted domains in config.php
	 */
	private function removePortsFromTrustedDomains() {
		$trustedDomains = \OC::$server->getConfig()->getSystemValue('trusted_domains', array());
		$newTrustedDomains = array();
		foreach($trustedDomains as $domain) {
			$pos = strrpos($domain, ':');
			if ($pos !== false) {
				$port = substr($domain, $pos + 1);
				if (is_numeric($port)) {
					$domain = substr($domain, 0, $pos);
				}
			}
			$newTrustedDomains[] = $domain;
		}
		\OC::$server->getConfig()->setSystemValue('trusted_domains', $newTrustedDomains);
	}
}
