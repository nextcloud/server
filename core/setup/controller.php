<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Setup;

use OCP\IConfig;

class Controller {
	/** @var \OCP\IConfig */
	protected $config;

	/**
	 * @param IConfig $config
	 */
	function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function run($post) {
		// Check for autosetup:
		$post = $this->loadAutoConfig($post);
		$opts = $this->getSystemInfo();

		if(isset($post['install']) AND $post['install']=='true') {
			// We have to launch the installation process :
			$e = \OC_Setup::install($post);
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

		\OC_Util::addScript( '3rdparty', 'strengthify/jquery.strengthify' );
		\OC_Util::addStyle( '3rdparty', 'strengthify/strengthify' );
		\OC_Util::addScript('setup');
		\OC_Template::printGuestPage('', 'installation', $parameters);
	}

	public function finishSetup() {
		\OC_Util::redirectToDefaultPage();
	}

	public function loadAutoConfig($post) {
		$autosetup_file = \OC::$SERVERROOT.'/config/autoconfig.php';
		if( file_exists( $autosetup_file )) {
			\OC_Log::write('core', 'Autoconfig file found, setting up owncloud...', \OC_Log::INFO);
			$AUTOCONFIG = array();
			include $autosetup_file;
			$post = array_merge ($post, $AUTOCONFIG);
		}

		$dbIsSet = isset($post['dbtype']);
		$directoryIsSet = isset($post['directory']);
		$adminAccountIsSet = isset($post['adminlogin']);

		if ($dbIsSet AND $directoryIsSet AND $adminAccountIsSet) {
			$post['install'] = 'true';
			if( file_exists( $autosetup_file )) {
				unlink($autosetup_file);
			}
		}
		$post['dbIsSet'] = $dbIsSet;
		$post['directoryIsSet'] = $directoryIsSet;

		return $post;
	}

	/**
	 * Gathers system information like database type and does
	 * a few system checks.
	 *
	 * @return array of system info, including an "errors" value
	 * in case of errors/warnings
	 */
	public function getSystemInfo() {
		$setup = new \OC_Setup($this->config);
		$databases = $setup->getSupportedDatabases();

		$datadir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT.'/data');
		$vulnerableToNullByte = false;
		if(@file_exists(__FILE__."\0Nullbyte")) { // Check if the used PHP version is vulnerable to the NULL Byte attack (CVE-2006-7243)
			$vulnerableToNullByte = true;
		} 

		$errors = array();

		// Create data directory to test whether the .htaccess works
		// Notice that this is not necessarily the same data directory as the one
		// that will effectively be used.
		@mkdir($datadir);
		if (is_dir($datadir) && is_writable($datadir)) {
			// Protect data directory here, so we can test if the protection is working
			\OC_Setup::protectDataDirectory();

			try {
				$htaccessWorking = \OC_Util::isHtaccessWorking();
			} catch (\OC\HintException $e) {
				$errors[] = array(
					'error' => $e->getMessage(),
					'hint' => $e->getHint()
				);
				$htaccessWorking = false;
			}
		}

		if (\OC_Util::runningOnMac()) {
			$l10n = \OC::$server->getL10N('core');
			$themeName = \OC_Util::getTheme();
			$theme = new \OC_Defaults();
			$errors[] = array(
				'error' => $l10n->t(
					'Mac OS X is not supported and %s will not work properly on this platform. ' .
					'Use it at your own risk! ',
					$theme->getName()
				),
				'hint' => $l10n->t('For the best results, please consider using a GNU/Linux server instead.')
			);
		}

		return array(
			'hasSQLite' => isset($databases['sqlite']),
			'hasMySQL' => isset($databases['mysql']),
			'hasPostgreSQL' => isset($databases['postgre']),
			'hasOracle' => isset($databases['oci']),
			'hasMSSQL' => isset($databases['mssql']),
			'databases' => $databases,
			'directory' => $datadir,
			'htaccessWorking' => $htaccessWorking,
			'vulnerableToNullByte' => $vulnerableToNullByte,
			'errors' => $errors,
		);
	}
}
