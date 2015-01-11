<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Setup;

use bantu\IniGetWrapper\IniGetWrapper;
use OCP\IConfig;
use OCP\IL10N;

class Controller {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;
	/** @var IniGetWrapper */
	protected $iniWrapper;
	/** @var IL10N */
	protected $l10n;
	/** @var \OC_Defaults */
	protected $defaults;

	/**
	 * @var string
	 */
	private $autoConfigFile;

	/**
	 * @param IConfig $config
	 * @param IniGetWrapper $iniWrapper
	 * @param IL10N $l10n
	 * @param \OC_Defaults $defaults
	 */
	function __construct(IConfig $config,
						 IniGetWrapper $iniWrapper,
						 IL10N $l10n,
						 \OC_Defaults $defaults) {
		$this->autoConfigFile = \OC::$SERVERROOT.'/config/autoconfig.php';
		$this->config = $config;
		$this->iniWrapper = $iniWrapper;
		$this->l10n = $l10n;
		$this->defaults = $defaults;
	}

	/**
	 * @param $post
	 */
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
			\OC_Log::write('core', 'Autoconfig file found, setting up owncloud...', \OC_Log::INFO);
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

		$dataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT.'/data');
		$vulnerableToNullByte = false;
		if(@file_exists(__FILE__."\0Nullbyte")) { // Check if the used PHP version is vulnerable to the NULL Byte attack (CVE-2006-7243)
			$vulnerableToNullByte = true;
		} 

		$errors = array();

		// Create data directory to test whether the .htaccess works
		// Notice that this is not necessarily the same data directory as the one
		// that will effectively be used.
		@mkdir($dataDir);
		$htAccessWorking = true;
		if (is_dir($dataDir) && is_writable($dataDir)) {
			// Protect data directory here, so we can test if the protection is working
			\OC_Setup::protectDataDirectory();

			try {
				$htAccessWorking = \OC_Util::isHtaccessWorking();
			} catch (\OC\HintException $e) {
				$errors[] = array(
					'error' => $e->getMessage(),
					'hint' => $e->getHint()
				);
				$htAccessWorking = false;
			}
		}


		if (\OC_Util::runningOnMac()) {
			$errors[] = array(
				'error' => $this->l10n->t(
					'Mac OS X is not supported and %s will not work properly on this platform. ' .
					'Use it at your own risk! ',
					$this->defaults->getName()
				),
				'hint' => $this->l10n->t('For the best results, please consider using a GNU/Linux server instead.')
			);
		}

		if($this->iniWrapper->getString('open_basedir') !== '' && PHP_INT_SIZE === 4) {
			$errors[] = array(
				'error' => $this->l10n->t(
					'It seems that this %s instance is running on a 32-bit PHP environment and the open_basedir has been configured in php.ini. ' .
					'This will lead to problems with files over 4GB and is highly discouraged.',
					$this->defaults->getName()
				),
				'hint' => $this->l10n->t('Please remove the open_basedir setting within your php.ini or switch to 64-bit PHP.')
			);
		}
		if(!function_exists('curl_init') && PHP_INT_SIZE === 4) {
			$errors[] = array(
				'error' => $this->l10n->t(
					'It seems that this %s instance is running on a 32-bit PHP environment and cURL is not installed. ' .
					'This will lead to problems with files over 4GB and is highly discouraged.',
					$this->defaults->getName()
				),
				'hint' => $this->l10n->t('Please install the cURL extension and restart your webserver.')
			);
		}

		return array(
			'hasSQLite' => isset($databases['sqlite']),
			'hasMySQL' => isset($databases['mysql']),
			'hasPostgreSQL' => isset($databases['pgsql']),
			'hasOracle' => isset($databases['oci']),
			'hasMSSQL' => isset($databases['mssql']),
			'databases' => $databases,
			'directory' => $dataDir,
			'htaccessWorking' => $htAccessWorking,
			'vulnerableToNullByte' => $vulnerableToNullByte,
			'errors' => $errors,
		);
	}
}
