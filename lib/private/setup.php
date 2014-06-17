<?php

class DatabaseSetupException extends \OC\HintException
{
}

class OC_Setup {
	static $dbSetupClasses = array(
		'mysql' => '\OC\Setup\MySQL',
		'pgsql' => '\OC\Setup\PostgreSQL',
		'oci'   => '\OC\Setup\OCI',
		'mssql' => '\OC\Setup\MSSQL',
		'sqlite' => '\OC\Setup\Sqlite',
		'sqlite3' => '\OC\Setup\Sqlite',
	);

	public static function getTrans(){
		return OC_L10N::get('lib');
	}

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

		$class = self::$dbSetupClasses[$dbtype];
		$dbSetup = new $class(self::getTrans(), 'db_structure.xml');
		$error = array_merge($error, $dbSetup->validate($options));

		if(count($error) != 0) {
			return $error;
		}

		//no errors, good
		$username = htmlspecialchars_decode($options['adminlogin']);
		$password = htmlspecialchars_decode($options['adminpass']);
		$datadir = htmlspecialchars_decode($options['directory']);
		if(    isset($options['trusted_domains'])
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
			OC_Appconfig::setValue('core', 'installedat', microtime(true));
			OC_Appconfig::setValue('core', 'lastupdatedat', microtime(true));
			OC_AppConfig::setValue('core', 'remote_core.css', '/core/minimizer.php');
			OC_AppConfig::setValue('core', 'remote_core.js', '/core/minimizer.php');

			OC_Group::createGroup('admin');
			OC_Group::addToGroup($username, 'admin');
			OC_User::login($username, $password);

			//guess what this does
			OC_Installer::installShippedApps();

			// create empty file in data dir, so we can later find
			// out that this is indeed an ownCloud data directory
			file_put_contents(OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data').'/.ocdata', '');

			//create htaccess files for apache hosts
			if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
				self::createHtaccess();
			}

			//and we are done
			OC_Config::setValue('installed', true);
		}

		return $error;
	}

	/**
	 * create .htaccess files for apache hosts
	 */
	private static function createHtaccess() {
		$content = "<IfModule mod_fcgid.c>\n";
		$content.= "<IfModule mod_setenvif.c>\n";
		$content.= "<IfModule mod_headers.c>\n";
		$content.= "SetEnvIfNoCase ^Authorization$ \"(.+)\" XAUTHORIZATION=$1\n";
		$content.= "RequestHeader set XAuthorization %{XAUTHORIZATION}e env=XAUTHORIZATION\n";
		$content.= "</IfModule>\n";
		$content.= "</IfModule>\n";
		$content.= "</IfModule>\n";
		$content.= "ErrorDocument 403 ".OC::$WEBROOT."/core/templates/403.php\n";//custom 403 error page
		$content.= "ErrorDocument 404 ".OC::$WEBROOT."/core/templates/404.php\n";//custom 404 error page
		$content.= "<IfModule mod_php5.c>\n";
		$content.= "php_value upload_max_filesize 512M\n";//upload limit
		$content.= "php_value post_max_size 512M\n";
		$content.= "php_value memory_limit 512M\n";
		$content.= "php_value mbstring.func_overload 0\n";
		$content.= "<IfModule env_module>\n";
		$content.= "  SetEnv htaccessWorking true\n";
		$content.= "</IfModule>\n";
		$content.= "</IfModule>\n";
		$content.= "<IfModule mod_rewrite.c>\n";
		$content.= "RewriteEngine on\n";
		$content.= "RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
		$content.= "RewriteRule ^.well-known/host-meta /public.php?service=host-meta [QSA,L]\n";
		$content.= "RewriteRule ^.well-known/carddav /remote.php/carddav/ [R]\n";
		$content.= "RewriteRule ^.well-known/caldav /remote.php/caldav/ [R]\n";
		$content.= "RewriteRule ^apps/([^/]*)/(.*\.(css|php))$ index.php?app=$1&getfile=$2 [QSA,L]\n";
		$content.= "RewriteRule ^remote/(.*) remote.php [QSA,L]\n";
		$content.= "</IfModule>\n";
		$content.= "<IfModule mod_mime.c>\n";
		$content.= "AddType image/svg+xml svg svgz\n";
		$content.= "AddEncoding gzip svgz\n";
		$content.= "</IfModule>\n";
		$content.= "<IfModule dir_module>\n";
		$content.= "DirectoryIndex index.php index.html\n";
		$content.= "</IfModule>\n";
		$content.= "AddDefaultCharset utf-8\n";
		$content.= "Options -Indexes\n";
		$content.= "<IfModule pagespeed_module>\n";
		$content.= "ModPagespeed Off\n";
		$content.= "</IfModule>\n";
		@file_put_contents(OC::$SERVERROOT.'/.htaccess', $content); //supress errors in case we don't have permissions for it

		self::protectDataDirectory();
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

	/**
	 * @brief Post installation checks
	 */
	public static function postSetupCheck($params) {
		// setup was successful -> webdav testing now
		$l = self::getTrans();
		if (OC_Util::isWebDAVWorking()) {
			header("Location: ".OC::$WEBROOT.'/');
		} else {

			$error = $l->t('Your web server is not yet properly setup to allow files synchronization because the WebDAV interface seems to be broken.');
			$hint = $l->t('Please double check the <a href=\'%s\'>installation guides</a>.',
				\OC_Helper::linkToDocs('admin-install'));

			OC_Template::printErrorPage($error, $hint);
			exit();
		}
	}
}
