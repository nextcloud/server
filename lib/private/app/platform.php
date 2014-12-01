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

use OCP\IConfig;

class Platform {

	function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getPhpVersion() {
		return phpversion();
	}

	public function getDatabase() {
		$dbType = $this->config->getSystemValue('dbtype', 'sqlite');
		if ($dbType === 'sqlite3') {
			$dbType = 'sqlite';
		}

		return $dbType;
	}
}
