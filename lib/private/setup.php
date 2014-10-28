<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCP\IConfig;

class DatabaseSetupException extends \OC\HintException {
}

class OC_Setup {
	/** @var IConfig */
	protected $config;

	/**
	 * @param IConfig $config
	 */
	function __construct(IConfig $config) {
		$this->config = $config;
	}

	static $dbSetupClasses = array(
		'mysql' => '\OC\Setup\MySQL',
		'pgsql' => '\OC\Setup\PostgreSQL',
		'oci'   => '\OC\Setup\OCI',
		'mssql' => '\OC\Setup\MSSQL',
		'sqlite' => '\OC\Setup\Sqlite',
		'sqlite3' => '\OC\Setup\Sqlite',
	);

	/**
	 * @return OC_L10N
	 */
	public static function getTrans(){
		return OC_L10N::get('lib');
	}

	/**
	 * Wrapper around the "class_exists" PHP function to be able to mock it
	 * @param string $name
	 * @return bool
	 */
	public function class_exists($name) {
		return class_exists($name);
	}

	/**
	 * Wrapper around the "is_callable" PHP function to be able to mock it
	 * @param string $name
	 * @return bool
	 */
	public function is_callable($name) {
		return is_callable($name);
	}

	/**
	 * Get the available and supported databases of this instance
	 *
	 * @throws Exception
	 * @return array
	 */
	public function getSupportedDatabases() {
		$availableDatabases = array(
			'sqlite' =>  array(
				'type' => 'class',
				'call' => 'SQLite3',
				'name' => 'SQLite'
			),
			'mysql' => array(
				'type' => 'function',
				'call' => 'mysql_connect',
				'name' => 'MySQL/MariaDB'
			),
			'pgsql' => array(
				'type' => 'function',
				'call' => 'oci_connect',
				'name' => 'PostgreSQL'
			),
			'oci' => array(
				'type' => 'function',
				'call' => 'oci_connect',
				'name' => 'Oracle'
			),
			'mssql' => array(
				'type' => 'function',
				'call' => 'sqlsrv_connect',
				'name' => 'MS SQL'
			)
		);
		$configuredDatabases = $this->config->getSystemValue('supportedDatabases', array('sqlite', 'mysql', 'pgsql', 'oci', 'mssql'));
		if(!is_array($configuredDatabases)) {
			throw new Exception('Supported databases are not properly configured.');
		}

		$supportedDatabases = array();

		foreach($configuredDatabases as $database) {
			if(array_key_exists($database, $availableDatabases)) {
				$working = false;
				if($availableDatabases[$database]['type'] === 'class') {
					$working = $this->class_exists($availableDatabases[$database]['call']);
				} elseif ($availableDatabases[$database]['type'] === 'function') {
					$working = $this->is_callable($availableDatabases[$database]['call']);
				}
				if($working) {
					$supportedDatabases[$database] = $availableDatabases[$database]['name'];
				}
			}
		}

		return $supportedDatabases;
	}

	/**
	 * @param $options
	 * @return array
	 */
	public static function install($options) {
		$l = self::getTrans();

		$error = array();
		$dbtype = $options['dbtype'];

		if(empty($options['adminlogin'])) {
			$error[] = $l->t('Set an admin username.');
		}
		if(empty($options['adminpass'])) {
			$error[] = $l->t('Set an admin password.');
		}
		if(empty($options['directory'])) {
			$options['directory'] = OC::$SERVERROOT."/data";
		}

		if (!isset(self::$dbSetupClasses[$dbtype])) {
			$dbtype = 'sqlite';
		}

		$username = htmlspecialchars_decode($options['adminlogin']);
		$password = htmlspecialchars_decode($options['adminpass']);
		$datadir = htmlspecialchars_decode($options['directory']);

		$class = self::$dbSetupClasses[$dbtype];
		/** @var \OC\Setup\AbstractDatabase $dbSetup */
		$dbSetup = new $class(self::getTrans(), 'db_structure.xml');
		$error = array_merge($error, $dbSetup->validate($options));

		// validate the data directory
		if (
			(!is_dir($datadir) and !mkdir($datadir)) or
			!is_writable($datadir)
		) {
			$error[] = $l->t("Can't create or write into the data directory %s", array($datadir));
		}

		if(count($error) != 0) {
			return $error;
		}

		//no errors, good
		if(isset($options['trusted_domains'])
		    && is_array($options['trusted_domains'])) {
			$trustedDomains = $options['trusted_domains'];
		} else {
			$trustedDomains = array(OC_Request::serverHost());
		}

		if (OC_Util::runningOnWindows()) {
			$datadir = rtrim(realpath($datadir), '\\');
		}

		//use sqlite3 when available, otherise sqlite2 will be used.
		if($dbtype=='sqlite' and class_exists('SQLite3')) {
			$dbtype='sqlite3';
		}

		//generate a random salt that is used to salt the local user passwords
		$salt = OC_Util::generateRandomBytes(30);
		OC_Config::setValue('passwordsalt', $salt);

		//write the config file
		OC_Config::setValue('trusted_domains', $trustedDomains);
		OC_Config::setValue('datadirectory', $datadir);
		OC_Config::setValue('overwrite.cli.url', \OC_Request::serverProtocol() . '://' . \OC_Request::serverHost() . OC::$WEBROOT);
		OC_Config::setValue('dbtype', $dbtype);
		OC_Config::setValue('version', implode('.', OC_Util::getVersion()));
		try {
			$dbSetup->initialize($options);
			$dbSetup->setupDatabase($username);
		} catch (DatabaseSetupException $e) {
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
		try {
			OC_User::createUser($username, $password);
		}
		catch(Exception $exception) {
			$error[] = $exception->getMessage();
		}

		if(count($error) == 0) {
			$appConfig = \OC::$server->getAppConfig();
			$appConfig->setValue('core', 'installedat', microtime(true));
			$appConfig->setValue('core', 'lastupdatedat', microtime(true));

			OC_Group::createGroup('admin');
			OC_Group::addToGroup($username, 'admin');
			OC_User::login($username, $password);

			//guess what this does
			OC_Installer::installShippedApps();

			// create empty file in data dir, so we can later find
			// out that this is indeed an ownCloud data directory
			file_put_contents(OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data').'/.ocdata', '');

			// Update htaccess files for apache hosts
			if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
				self::updateHtaccess();
				self::protectDataDirectory();
			}

			//and we are done
			OC_Config::setValue('installed', true);
		}

		return $error;
	}

	/**
	 * Append the correct ErrorDocument path for Apache hosts
	 */
	public static function updateHtaccess() {
		$content = "\n";
		$content.= "ErrorDocument 403 ".OC::$WEBROOT."/core/templates/403.php\n";//custom 403 error page
		$content.= "ErrorDocument 404 ".OC::$WEBROOT."/core/templates/404.php";//custom 404 error page
		@file_put_contents(OC::$SERVERROOT.'/.htaccess', $content, FILE_APPEND); //suppress errors in case we don't have permissions for it
	}

	public static function protectDataDirectory() {
		//Require all denied
		$now =  date('Y-m-d H:i:s');
		$content = "# Generated by ownCloud on $now\n";
		$content.= "# line below if for Apache 2.4\n";
		$content.= "<ifModule mod_authz_core>\n";
		$content.= "Require all denied\n";
		$content.= "</ifModule>\n\n";
		$content.= "# line below if for Apache 2.2\n";
		$content.= "<ifModule !mod_authz_core>\n";
		$content.= "deny from all\n";
		$content.= "</ifModule>\n\n";
		$content.= "# section for Apache 2.2 and 2.4\n";
		$content.= "IndexIgnore *\n";
		file_put_contents(OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data').'/.htaccess', $content);
		file_put_contents(OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data').'/index.html', '');
	}
}
