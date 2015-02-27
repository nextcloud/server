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

use OC_Util;
use OCP\IConfig;

/**
 * Class Platform
 *
 * This class basically abstracts any kind of information which can be retrieved from the underlying system.
 *
 * @package OC\App
 */
class Platform {

	/**
	 * @param IConfig $config
	 */
	function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getPhpVersion() {
		return phpversion();
	}

	/**
	 * @return string
	 */
	public function getOcVersion() {
		$v = OC_Util::getVersion();
		return join('.', $v);
	}

	/**
	 * @return string
	 */
	public function getDatabase() {
		$dbType = $this->config->getSystemValue('dbtype', 'sqlite');
		if ($dbType === 'sqlite3') {
			$dbType = 'sqlite';
		}

		return $dbType;
	}

	/**
	 * @return string
	 */
	public function getOS() {
		return php_uname('s');
	}

	/**
	 * @param $command
	 * @return bool
	 */
	public function isCommandKnown($command) {
		$path = \OC_Helper::findBinaryPath($command);
		return ($path !== null);
	}

	public function getLibraryVersion($name) {
		$repo = new PlatformRepository();
		$lib = $repo->findLibrary($name);
		return $lib;
	}
}
