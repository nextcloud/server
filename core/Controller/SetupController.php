<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author ideaship <ideaship@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\Core\Controller;

use OC\Setup;

class SetupController {
	/** @var Setup */
	protected $setupHelper;
	/** @var string */
	private $autoConfigFile;

	/**
	 * @param Setup $setupHelper
	 */
	function __construct(Setup $setupHelper) {
		$this->autoConfigFile = \OC::$configDir.'autoconfig.php';
		$this->setupHelper = $setupHelper;
	}

	/**
	 * @param $post
	 */
	public function run($post) {
		// Check for autosetup:
		$post = $this->loadAutoConfig($post);
		$opts = $this->setupHelper->getSystemInfo();

		// convert 'abcpassword' to 'abcpass'
		if (isset($post['adminpassword'])) {
			$post['adminpass'] = $post['adminpassword'];
		}
		if (isset($post['dbpassword'])) {
			$post['dbpass'] = $post['dbpassword'];
		}

		if(isset($post['install']) AND $post['install']=='true') {
			// We have to launch the installation process :
			$e = $this->setupHelper->install($post);
			$errors = array('errors' => $e);

			if(count($e) > 0) {
				$options = array_merge($opts, $post, $errors);
				$this->display($options);
			} else {
				$this->finishSetup();
			}
		} else {
			$options = array_merge($opts, $post);
			$this->display($options);
		}
	}

	public function display($post) {
		$defaults = array(
			'adminlogin' => '',
			'adminpass' => '',
			'dbuser' => '',
			'dbpass' => '',
			'dbname' => '',
			'dbtablespace' => '',
			'dbhost' => 'localhost',
			'dbtype' => '',
		);
		$parameters = array_merge($defaults, $post);

		\OC_Util::addVendorScript('strengthify/jquery.strengthify');
		\OC_Util::addVendorStyle('strengthify/strengthify');
		\OC_Util::addScript('setup');
		\OC_Template::printGuestPage('', 'installation', $parameters);
	}

	public function finishSetup() {
		if( file_exists( $this->autoConfigFile )) {
			unlink($this->autoConfigFile);
		}
		\OC::$server->getIntegrityCodeChecker()->runInstanceVerification();
		\OC_Util::redirectToDefaultPage();
	}

	public function loadAutoConfig($post) {
		if( file_exists($this->autoConfigFile)) {
			\OCP\Util::writeLog('core', 'Autoconfig file found, setting up ownCloud…', \OCP\Util::INFO);
			$AUTOCONFIG = array();
			include $this->autoConfigFile;
			$post = array_merge ($post, $AUTOCONFIG);
		}

		$dbIsSet = isset($post['dbtype']);
		$directoryIsSet = isset($post['directory']);
		$adminAccountIsSet = isset($post['adminlogin']);

		if ($dbIsSet AND $directoryIsSet AND $adminAccountIsSet) {
			$post['install'] = 'true';
		}
		$post['dbIsSet'] = $dbIsSet;
		$post['directoryIsSet'] = $directoryIsSet;

		return $post;
	}
}
