<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Setup;

use OC\Setup;

class Controller {
	/** @var Setup */
	protected $setupHelper;
	/** @var string */
	private $autoConfigFile;

	/**
	 * @param Setup $setupHelper
	 */
	function __construct(Setup $setupHelper) {
		$this->autoConfigFile = \OC::$SERVERROOT.'/config/autoconfig.php';
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
		\OC_Util::redirectToDefaultPage();
	}

	public function loadAutoConfig($post) {
		if( file_exists($this->autoConfigFile)) {
			\OC_Log::write('core', 'Autoconfig file found, setting up ownCloud…', \OC_Log::INFO);
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
