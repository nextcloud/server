<?php
/**
 * @author Administrator <Administrator@WINDOWS-2012>
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Brice Maron <brice@bmaron.net>
 * @author François Kubler <francois@kubler.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sean Comeau <sean@ftlnetworks.ca>
 * @author Serge Martin <edb@sigluy.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
use Exception;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Security\ISecureRandom;

class Setup {
	/** @var \OCP\IConfig */
	protected $config;
	/** @var IniGetWrapper */
	protected $iniWrapper;
	/** @var IL10N */
	protected $l10n;
	/** @var \OC_Defaults */
	protected $defaults;
	/** @var ILogger */
	protected $logger;
	/** @var ISecureRandom */
	protected $random;

	/**
	 * @param IConfig $config
	 * @param IniGetWrapper $iniWrapper
	 * @param \OC_Defaults $defaults
	 */
	function __construct(IConfig $config,
						 IniGetWrapper $iniWrapper,
						 IL10N $l10n,
						 \OC_Defaults $defaults,
						 ILogger $logger,
						 ISecureRandom $random
		) {
		$this->config = $config;
		$this->iniWrapper = $iniWrapper;
		$this->l10n = $l10n;
		$this->defaults = $defaults;
		$this->logger = $logger;
		$this->random = $random;
	}

	static $dbSetupClasses = array(
		'mysql' => '\OC\Setup\MySQL',
		'pgsql' => '\OC\Setup\PostgreSQL',
		'oci'   => '\OC\Setup\OCI',
		'sqlite' => '\OC\Setup\Sqlite',
		'sqlite3' => '\OC\Setup\Sqlite',
	);

	/**
	 * Wrapper around the "class_exists" PHP function to be able to mock it
	 * @param string $name
	 * @return bool
	 */
	protected function class_exists($name) {
		return class_exists($name);
	}

	/**
	 * Wrapper around the "is_callable" PHP function to be able to mock it
	 * @param string $name
	 * @return bool
	 */
	protected function is_callable($name) {
		return is_callable($name);
	}

	/**
	 * Wrapper around \PDO::getAvailableDrivers
	 *
	 * @return array
	 */
	protected function getAvailableDbDriversForPdo() {
		return \PDO::getAvailableDrivers();
	}

	/**
	 * Get the available and supported databases of this instance
	 *
	 * @param bool $allowAllDatabases
	 * @return array
	 * @throws Exception
	 */
	public function getSupportedDatabases($allowAllDatabases = false) {
		$availableDatabases = array(
			'sqlite' =>  array(
				'type' => 'class',
				'call' => 'SQLite3',
				'name' => 'SQLite'
			),
			'mysql' => array(
				'type' => 'pdo',
				'call' => 'mysql',
				'name' => 'MySQL/MariaDB'
			),
			'pgsql' => array(
				'type' => 'function',
				'call' => 'pg_connect',
				'name' => 'PostgreSQL'
			),
			'oci' => array(
				'type' => 'function',
				'call' => 'oci_connect',
				'name' => 'Oracle'
			)
		);
		if ($allowAllDatabases) {
			$configuredDatabases = array_keys($availableDatabases);
		} else {
			$configuredDatabases = $this->config->getSystemValue('supportedDatabases',
				array('sqlite', 'mysql', 'pgsql'));
		}
		if(!is_array($configuredDatabases)) {
			throw new Exception('Supported databases are not properly configured.');
		}

		$supportedDatabases = array();

		foreach($configuredDatabases as $database) {
			if(array_key_exists($database, $availableDatabases)) {
				$working = false;
				$type = $availableDatabases[$database]['type'];
				$call = $availableDatabases[$database]['call'];

				if($type === 'class') {
					$working = $this->class_exists($call);
				} elseif ($type === 'function') {
					$working = $this->is_callable($call);
				} elseif($type === 'pdo') {
					$working = in_array($call, $this->getAvailableDbDriversForPdo(), TRUE);
				}
				if($working) {
					$supportedDatabases[$database] = $availableDatabases[$database]['name'];
				}
			}
		}

		return $supportedDatabases;
	}

	/**
	 * Gathers system information like database type and does
	 * a few system checks.
	 *
	 * @return array of system info, including an "errors" value
	 * in case of errors/warnings
	 */
	public function getSystemInfo($allowAllDatabases = false) {
		$databases = $this->getSupportedDatabases($allowAllDatabases);

		$dataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT.'/data');

		$errors = array();

		// Create data directory to test whether the .htaccess works
		// Notice that this is not necessarily the same data directory as the one
		// that will effectively be used.
		@mkdir($dataDir);
		$htAccessWorking = true;
		if (is_dir($dataDir) && is_writable($dataDir)) {
			// Protect data directory here, so we can test if the protection is working
			\OC\Setup::protectDataDirectory();

			try {
				$util = new \OC_Util();
				$htAccessWorking = $util->isHtaccessWorking(\OC::$server->getConfig());
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
					'This will lead to problems with files over 4 GB and is highly discouraged.',
					$this->defaults->getName()
				),
				'hint' => $this->l10n->t('Please remove the open_basedir setting within your php.ini or switch to 64-bit PHP.')
			);
		}

		return array(
			'hasSQLite' => isset($databases['sqlite']),
			'hasMySQL' => isset($databases['mysql']),
			'hasPostgreSQL' => isset($databases['pgsql']),
			'hasOracle' => isset($databases['oci']),
			'databases' => $databases,
			'directory' => $dataDir,
			'htaccessWorking' => $htAccessWorking,
			'errors' => $errors,
		);
	}

	/**
	 * @param $options
	 * @return array
	 */
	public function install($options) {
		$l = $this->l10n;

		$error = array();
		$dbType = $options['dbtype'];

		if(empty($options['adminlogin'])) {
			$error[] = $l->t('Set an admin username.');
		}
		if(empty($options['adminpass'])) {
			$error[] = $l->t('Set an admin password.');
		}
		if(empty($options['directory'])) {
			$options['directory'] = \OC::$SERVERROOT."/data";
		}

		if (!isset(self::$dbSetupClasses[$dbType])) {
			$dbType = 'sqlite';
		}

		$username = htmlspecialchars_decode($options['adminlogin']);
		$password = htmlspecialchars_decode($options['adminpass']);
		$dataDir = htmlspecialchars_decode($options['directory']);

		$class = self::$dbSetupClasses[$dbType];
		/** @var \OC\Setup\AbstractDatabase $dbSetup */
		$dbSetup = new $class($l, 'db_structure.xml', $this->config,
			$this->logger, $this->random);
		$error = array_merge($error, $dbSetup->validate($options));

		// validate the data directory
		if (
			(!is_dir($dataDir) and !mkdir($dataDir)) or
			!is_writable($dataDir)
		) {
			$error[] = $l->t("Can't create or write into the data directory %s", array($dataDir));
		}

		if(count($error) != 0) {
			return $error;
		}

		$request = \OC::$server->getRequest();

		//no errors, good
		if(isset($options['trusted_domains'])
		    && is_array($options['trusted_domains'])) {
			$trustedDomains = $options['trusted_domains'];
		} else {
			$trustedDomains = [$request->getInsecureServerHost()];
		}

		if (\OC_Util::runningOnWindows()) {
			$dataDir = rtrim(realpath($dataDir), '\\');
		}

		//use sqlite3 when available, otherwise sqlite2 will be used.
		if($dbType=='sqlite' and class_exists('SQLite3')) {
			$dbType='sqlite3';
		}

		//generate a random salt that is used to salt the local user passwords
		$salt = $this->random->getLowStrengthGenerator()->generate(30);
		// generate a secret
		$secret = $this->random->getMediumStrengthGenerator()->generate(48);

		//write the config file
		$this->config->setSystemValues([
			'passwordsalt'		=> $salt,
			'secret'			=> $secret,
			'trusted_domains'	=> $trustedDomains,
			'datadirectory'		=> $dataDir,
			'overwrite.cli.url'	=> $request->getServerProtocol() . '://' . $request->getInsecureServerHost() . \OC::$WEBROOT,
			'dbtype'			=> $dbType,
			'version'			=> implode('.', \OC_Util::getVersion()),
		]);

		try {
			$dbSetup->initialize($options);
			$dbSetup->setupDatabase($username);
		} catch (\OC\DatabaseSetupException $e) {
			$error[] = array(
				'error' => $e->getMessage(),
				'hint' => $e->getHint()
			);
			return($error);
		} catch (Exception $e) {
			$error[] = array(
				'error' => 'Error while trying to create admin user: ' . $e->getMessage(),
				'hint' => ''
			);
			return($error);
		}

		//create the user and group
		$user =  null;
		try {
			$user = \OC::$server->getUserManager()->createUser($username, $password);
			if (!$user) {
				$error[] = "User <$username> could not be created.";
			}
		} catch(Exception $exception) {
			$error[] = $exception->getMessage();
		}

		if(count($error) == 0) {
			$config = \OC::$server->getConfig();
			$config->setAppValue('core', 'installedat', microtime(true));
			$config->setAppValue('core', 'lastupdatedat', microtime(true));

			$group =\OC::$server->getGroupManager()->createGroup('admin');
			$group->addUser($user);
			\OC_User::login($username, $password);

			//guess what this does
			\OC_Installer::installShippedApps();

			// create empty file in data dir, so we can later find
			// out that this is indeed an ownCloud data directory
			file_put_contents($config->getSystemValue('datadirectory', \OC::$SERVERROOT.'/data').'/.ocdata', '');

			// Update htaccess files for apache hosts
			if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
				self::updateHtaccess();
				self::protectDataDirectory();
			}

			//try to write logtimezone
			if (date_default_timezone_get()) {
				$config->setSystemValue('logtimezone', date_default_timezone_get());
			}

			//and we are done
			$config->setSystemValue('installed', true);
		}

		return $error;
	}

	/**
	 * @return string Absolute path to htaccess
	 */
	private function pathToHtaccess() {
		return \OC::$SERVERROOT.'/.htaccess';
	}

	/**
	 * Checks if the .htaccess contains the current version parameter
	 *
	 * @return bool
	 */
	private function isCurrentHtaccess() {
		$version = \OC_Util::getVersion();
		unset($version[3]);

		return !strpos(
			file_get_contents($this->pathToHtaccess()),
			'Version: '.implode('.', $version)
		) === false;
	}

	/**
	 * Append the correct ErrorDocument path for Apache hosts
	 *
	 * @throws \OC\HintException If .htaccess does not include the current version
	 */
	public static function updateHtaccess() {
		$setupHelper = new \OC\Setup(\OC::$server->getConfig(), \OC::$server->getIniWrapper(),
			\OC::$server->getL10N('lib'), new \OC_Defaults(), \OC::$server->getLogger(),
			\OC::$server->getSecureRandom());
		if(!$setupHelper->isCurrentHtaccess()) {
			throw new \OC\HintException('.htaccess file has the wrong version. Please upload the correct version. Maybe you forgot to replace it after updating?');
		}

		$htaccessContent = file_get_contents($setupHelper->pathToHtaccess());
		$content = '';
		if (strpos($htaccessContent, 'ErrorDocument 403') === false) {
			//custom 403 error page
			$content.= "\nErrorDocument 403 ".\OC::$WEBROOT."/core/templates/403.php";
		}
		if (strpos($htaccessContent, 'ErrorDocument 404') === false) {
			//custom 404 error page
			$content.= "\nErrorDocument 404 ".\OC::$WEBROOT."/core/templates/404.php";
		}
		if ($content !== '') {
			//suppress errors in case we don't have permissions for it
			@file_put_contents($setupHelper->pathToHtaccess(), $content . "\n", FILE_APPEND);
		}
	}

	public static function protectDataDirectory() {
		//Require all denied
		$now =  date('Y-m-d H:i:s');
		$content = "# Generated by ownCloud on $now\n";
		$content.= "# line below if for Apache 2.4\n";
		$content.= "<ifModule mod_authz_core.c>\n";
		$content.= "Require all denied\n";
		$content.= "</ifModule>\n\n";
		$content.= "# line below if for Apache 2.2\n";
		$content.= "<ifModule !mod_authz_core.c>\n";
		$content.= "deny from all\n";
		$content.= "Satisfy All\n";
		$content.= "</ifModule>\n\n";
		$content.= "# section for Apache 2.2 and 2.4\n";
		$content.= "IndexIgnore *\n";
		file_put_contents(\OC_Config::getValue('datadirectory', \OC::$SERVERROOT.'/data').'/.htaccess', $content);
		file_put_contents(\OC_Config::getValue('datadirectory', \OC::$SERVERROOT.'/data').'/index.html', '');
	}
}
