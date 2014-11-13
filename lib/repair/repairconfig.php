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
		$this->removePortsFromTrustedDomains();
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