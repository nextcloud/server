<?php

class DatabaseSetupException extends Exception
{
	private $hint;

	public function __construct($message, $hint, $code = 0, Exception $previous = null) {
		$this->hint = $hint;
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
	}

	public function getHint() {
		return $this->hint;
	}
}

class OC_Setup {

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
			$error[] = $l->t('Specify a data folder.');
		}

		if($dbtype == 'mysql' or $dbtype == 'pgsql' or $dbtype == 'oci' or $dbtype == 'mssql') { //mysql and postgresql needs more config options
			if($dbtype == 'mysql')
				$dbprettyname = 'MySQL';
			else if($dbtype == 'pgsql')
				$dbprettyname = 'PostgreSQL';
			else if ($dbtype == 'mssql')
				$dbprettyname = 'MS SQL Server';
			else
				$dbprettyname = 'Oracle';


			if(empty($options['dbuser'])) {
				$error[] = $l->t("%s enter the database username.", array($dbprettyname));
			}
			if(empty($options['dbname'])) {
				$error[] = $l->t("%s enter the database name.", array($dbprettyname));
			}
			if(substr_count($options['dbname'], '.') >= 1) {
				$error[] = $l->t("%s you may not use dots in the database name", array($dbprettyname));
			}
			if($dbtype != 'oci' && empty($options['dbhost'])) {
				$error[] = $l->t("%s set the database host.", array($dbprettyname));
			}
		}

		if(count($error) == 0) { //no errors, good
			$username = htmlspecialchars_decode($options['adminlogin']);
			$password = htmlspecialchars_decode($options['adminpass']);
			$datadir = htmlspecialchars_decode($options['directory']);

			if (OC_Util::runningOnWindows()) {
				$datadir = rtrim(realpath($datadir), '\\');
			}

			//use sqlite3 when available, otherise sqlite2 will be used.
			if($dbtype=='sqlite' and class_exists('SQLite3')) {
				$dbtype='sqlite3';
			}

			//generate a random salt that is used to salt the local user passwords
			$salt = OC_Util::generate_random_bytes(30);
			OC_Config::setValue('passwordsalt', $salt);

			//write the config file
			OC_Config::setValue('datadirectory', $datadir);
			OC_Config::setValue('dbtype', $dbtype);
			OC_Config::setValue('version', implode('.', OC_Util::getVersion()));
			if($dbtype == 'mysql') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbhost = $options['dbhost'];
				$dbtableprefix = isset($options['dbtableprefix']) ? $options['dbtableprefix'] : 'oc_';

				OC_Config::setValue('dbname', $dbname);
				OC_Config::setValue('dbhost', $dbhost);
				OC_Config::setValue('dbtableprefix', $dbtableprefix);

				try {
					self::setupMySQLDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $username);
				} catch (DatabaseSetupException $e) {
					$error[] = array(
						'error' => $e->getMessage(),
						'hint' => $e->getHint()
					);
					return($error);
				} catch (Exception $e) {
					$error[] = array(
						'error' => $e->getMessage(),
						'hint' => ''
					);
					return($error);
				}
			}
			elseif($dbtype == 'pgsql') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbhost = $options['dbhost'];
				$dbtableprefix = isset($options['dbtableprefix']) ? $options['dbtableprefix'] : 'oc_';

				OC_Config::setValue('dbname', $dbname);
				OC_Config::setValue('dbhost', $dbhost);
				OC_Config::setValue('dbtableprefix', $dbtableprefix);

				try {
					self::setupPostgreSQLDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $username);
				} catch (Exception $e) {
					$error[] = array(
						'error' => $l->t('PostgreSQL username and/or password not valid'),
						'hint' => $l->t('You need to enter either an existing account or the administrator.')
					);
					return $error;
				}
			}
			elseif($dbtype == 'oci') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbtablespace = $options['dbtablespace'];
				$dbhost = isset($options['dbhost'])?$options['dbhost']:'';
				$dbtableprefix = isset($options['dbtableprefix']) ? $options['dbtableprefix'] : 'oc_';

				OC_Config::setValue('dbname', $dbname);
				OC_Config::setValue('dbtablespace', $dbtablespace);
				OC_Config::setValue('dbhost', $dbhost);
				OC_Config::setValue('dbtableprefix', $dbtableprefix);

				try {
					self::setupOCIDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $dbtablespace, $username);
				} catch (Exception $e) {
					$error[] = array(
						'error' => $l->t('Oracle username and/or password not valid'),
						'hint' => $l->t('You need to enter either an existing account or the administrator.')
					);
					return $error;
				}
			}
			elseif ($dbtype == 'mssql') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbhost = $options['dbhost'];
				$dbtableprefix = isset($options['dbtableprefix']) ? $options['dbtableprefix'] : 'oc_';

				OC_Config::setValue('dbname', $dbname);
				OC_Config::setValue('dbhost', $dbhost);
				OC_Config::setValue('dbuser', $dbuser);
				OC_Config::setValue('dbpassword', $dbpass);
				OC_Config::setValue('dbtableprefix', $dbtableprefix);

				try {
					self::setupMSSQLDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix);
				} catch (Exception $e) {
					$error[] = array(
						'error' => 'MS SQL username and/or password not valid',
						'hint' => 'You need to enter either an existing account or the administrator.'
					);
					return $error;
				}
			}
			else {
				//delete the old sqlite database first, might cause infinte loops otherwise
				if(file_exists("$datadir/owncloud.db")) {
					unlink("$datadir/owncloud.db");
				}
				//in case of sqlite, we can always fill the database
				OC_DB::createDbFromStructure('db_structure.xml');
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

				//create htaccess files for apache hosts
				if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
					self::createHtaccess();
				}

				//and we are done
				OC_Config::setValue('installed', true);
			}
		}

		return $error;
	}

	private static function setupMySQLDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $username) {
		//check if the database user has admin right
		$l = self::getTrans();
		$connection = @mysql_connect($dbhost, $dbuser, $dbpass);
		if(!$connection) {
			throw new DatabaseSetupException($l->t('MySQL username and/or password not valid'),
				$l->t('You need to enter either an existing account or the administrator.'));
		}
		$oldUser=OC_Config::getValue('dbuser', false);

		//this should be enough to check for admin rights in mysql
		$query="SELECT user FROM mysql.user WHERE user='$dbuser'";
		if(mysql_query($query, $connection)) {
			//use the admin login data for the new database user

			//add prefix to the mysql user name to prevent collisions
			$dbusername=substr('oc_'.$username, 0, 16);
			if($dbusername!=$oldUser) {
				//hash the password so we don't need to store the admin config in the config file
				$dbpassword=md5(time().$dbpass);

				self::createDBUser($dbusername, $dbpassword, $connection);

				OC_Config::setValue('dbuser', $dbusername);
				OC_Config::setValue('dbpassword', $dbpassword);
			}

			//create the database
			self::createMySQLDatabase($dbname, $dbusername, $connection);
		}
		else {
			if($dbuser!=$oldUser) {
				OC_Config::setValue('dbuser', $dbuser);
				OC_Config::setValue('dbpassword', $dbpass);
			}

			//create the database
			self::createMySQLDatabase($dbname, $dbuser, $connection);
		}

		//fill the database if needed
		$query='select count(*) from information_schema.tables'
			." where table_schema='$dbname' AND table_name = '{$dbtableprefix}users';";
		$result = mysql_query($query, $connection);
		if($result) {
			$row=mysql_fetch_row($result);
		}
		if(!$result or $row[0]==0) {
			OC_DB::createDbFromStructure('db_structure.xml');
		}
		mysql_close($connection);
	}

	private static function createMySQLDatabase($name, $user, $connection) {
		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$l = self::getTrans();
		$query = "CREATE DATABASE IF NOT EXISTS  `$name`";
		$result = mysql_query($query, $connection);
		if(!$result) {
			$entry = $l->t('DB Error: "%s"', array(mysql_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		}
		$query="GRANT ALL PRIVILEGES ON  `$name` . * TO  '$user'";

		//this query will fail if there aren't the right permissions, ignore the error
		mysql_query($query, $connection);
	}

	private static function createDBUser($name, $password, $connection) {
		// we need to create 2 accounts, one for global use and one for local user. if we don't specify the local one,
		// the anonymous user would take precedence when there is one.
		$l = self::getTrans();
		$query = "CREATE USER '$name'@'localhost' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		if (!$result) {
			throw new DatabaseSetupException($l->t("MySQL user '%s'@'localhost' exists already.",
				array($name)), $l->t("Drop this user from MySQL", array($name)));
		}
		$query = "CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		if (!$result) {
			throw new DatabaseSetupException($l->t("MySQL user '%s'@'%%' already exists", array($name)),
				$l->t("Drop this user from MySQL."));
		}
	}

	private static function setupPostgreSQLDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $username) {
		$e_host = addslashes($dbhost);
		$e_user = addslashes($dbuser);
		$e_password = addslashes($dbpass);
		$l = self::getTrans();

		//check if the database user has admin rights
		$connection_string = "host='$e_host' dbname=postgres user='$e_user' password='$e_password'";
		$connection = @pg_connect($connection_string);
		if(!$connection) {
			throw new Exception($l->t('PostgreSQL username and/or password not valid'));
		}
		$e_user = pg_escape_string($dbuser);
		//check for roles creation rights in postgresql
		$query="SELECT 1 FROM pg_roles WHERE rolcreaterole=TRUE AND rolname='$e_user'";
		$result = pg_query($connection, $query);
		if($result and pg_num_rows($result) > 0) {
			//use the admin login data for the new database user

			//add prefix to the postgresql user name to prevent collisions
			$dbusername='oc_'.$username;
			//create a new password so we don't need to store the admin config in the config file
			$dbpassword=md5(OC_Util::generate_random_bytes(30));

			self::pg_createDBUser($dbusername, $dbpassword, $connection);

			OC_Config::setValue('dbuser', $dbusername);
			OC_Config::setValue('dbpassword', $dbpassword);

			//create the database
			self::pg_createDatabase($dbname, $dbusername, $connection);
		}
		else {
			OC_Config::setValue('dbuser', $dbuser);
			OC_Config::setValue('dbpassword', $dbpass);

			//create the database
			self::pg_createDatabase($dbname, $dbuser, $connection);
		}

		// the connection to dbname=postgres is not needed anymore
		pg_close($connection);

		// connect to the ownCloud database (dbname=$dbname) and check if it needs to be filled
		$dbuser = OC_Config::getValue('dbuser');
		$dbpass = OC_Config::getValue('dbpassword');

		$e_host = addslashes($dbhost);
		$e_dbname = addslashes($dbname);
		$e_user = addslashes($dbuser);
		$e_password = addslashes($dbpass);

		$connection_string = "host='$e_host' dbname='$e_dbname' user='$e_user' password='$e_password'";
		$connection = @pg_connect($connection_string);
		if(!$connection) {
			throw new Exception($l->t('PostgreSQL username and/or password not valid'));
		}
		$query = "select count(*) FROM pg_class WHERE relname='{$dbtableprefix}users' limit 1";
		$result = pg_query($connection, $query);
		if($result) {
			$row = pg_fetch_row($result);
		}
		if(!$result or $row[0]==0) {
			OC_DB::createDbFromStructure('db_structure.xml');
		}
	}

	private static function pg_createDatabase($name, $user, $connection) {

		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$l = self::getTrans();
		$e_name = pg_escape_string($name);
		$e_user = pg_escape_string($user);
		$query = "select datname from pg_database where datname = '$e_name'";
		$result = pg_query($connection, $query);
		if(!$result) {
			$entry = $l->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.pg', $entry, \OC_Log::WARN);
		}
		if(! pg_fetch_row($result)) {
			//The database does not exists... let's create it
			$query = "CREATE DATABASE \"$e_name\" OWNER \"$e_user\"";
			$result = pg_query($connection, $query);
			if(!$result) {
				$entry = $l->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.pg', $entry, \OC_Log::WARN);
			}
			else {
				$query = "REVOKE ALL PRIVILEGES ON DATABASE \"$e_name\" FROM PUBLIC";
				pg_query($connection, $query);
			}
		}
	}

	private static function pg_createDBUser($name, $password, $connection) {
		$l = self::getTrans();
		$e_name = pg_escape_string($name);
		$e_password = pg_escape_string($password);
		$query = "select * from pg_roles where rolname='$e_name';";
		$result = pg_query($connection, $query);
		if(!$result) {
			$entry = $l->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.pg', $entry, \OC_Log::WARN);
		}

		if(! pg_fetch_row($result)) {
			//user does not exists let's create it :)
			$query = "CREATE USER \"$e_name\" CREATEDB PASSWORD '$e_password';";
			$result = pg_query($connection, $query);
			if(!$result) {
				$entry = $l->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.pg', $entry, \OC_Log::WARN);
			}
		}
		else { // change password of the existing role
			$query = "ALTER ROLE \"$e_name\" WITH PASSWORD '$e_password';";
			$result = pg_query($connection, $query);
			if(!$result) {
				$entry = $l->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.pg', $entry, \OC_Log::WARN);
			}
		}
	}

	private static function setupOCIDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $dbtablespace,
		$username) {
		$l = self::getTrans();
		$e_host = addslashes($dbhost);
		$e_dbname = addslashes($dbname);
		//check if the database user has admin right
		if ($e_host == '') {
			$easy_connect_string = $e_dbname; // use dbname as easy connect name
		} else {
			$easy_connect_string = '//'.$e_host.'/'.$e_dbname;
		}
		$connection = @oci_connect($dbuser, $dbpass, $easy_connect_string);
		if(!$connection) {
			$e = oci_error();
			throw new Exception($l->t('Oracle username and/or password not valid'));
		}
		//check for roles creation rights in oracle

		$query='SELECT count(*) FROM user_role_privs, role_sys_privs'
			." WHERE user_role_privs.granted_role = role_sys_privs.role AND privilege = 'CREATE ROLE'";
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $l->t('DB Error: "%s"', array(oci_last_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		$result = oci_execute($stmt);
		if($result) {
			$row = oci_fetch_row($stmt);
		}
		if($result and $row[0] > 0) {
			//use the admin login data for the new database user

			//add prefix to the oracle user name to prevent collisions
			$dbusername='oc_'.$username;
			//create a new password so we don't need to store the admin config in the config file
			$dbpassword=md5(time().$dbpass);

			//oracle passwords are treated as identifiers:
			//  must start with aphanumeric char
			//  needs to be shortened to 30 bytes, as the two " needed to escape the identifier count towards the identifier length.
			$dbpassword=substr($dbpassword, 0, 30);

			self::oci_createDBUser($dbusername, $dbpassword, $dbtablespace, $connection);

			OC_Config::setValue('dbuser', $dbusername);
			OC_Config::setValue('dbname', $dbusername);
			OC_Config::setValue('dbpassword', $dbpassword);

			//create the database not neccessary, oracle implies user = schema
			//self::oci_createDatabase($dbname, $dbusername, $connection);
		} else {

			OC_Config::setValue('dbuser', $dbuser);
			OC_Config::setValue('dbname', $dbname);
			OC_Config::setValue('dbpassword', $dbpass);

			//create the database not neccessary, oracle implies user = schema
			//self::oci_createDatabase($dbname, $dbuser, $connection);
		}

		//FIXME check tablespace exists: select * from user_tablespaces

		// the connection to dbname=oracle is not needed anymore
		oci_close($connection);

		// connect to the oracle database (schema=$dbuser) an check if the schema needs to be filled
		$dbuser = OC_Config::getValue('dbuser');
		//$dbname = OC_Config::getValue('dbname');
		$dbpass = OC_Config::getValue('dbpassword');

		$e_host = addslashes($dbhost);
		$e_dbname = addslashes($dbname);

		if ($e_host == '') {
			$easy_connect_string = $e_dbname; // use dbname as easy connect name
		} else {
			$easy_connect_string = '//'.$e_host.'/'.$e_dbname;
		}
		$connection = @oci_connect($dbuser, $dbpass, $easy_connect_string);
		if(!$connection) {
			throw new Exception($l->t('Oracle username and/or password not valid'));
		}
		$query = "SELECT count(*) FROM user_tables WHERE table_name = :un";
		$stmt = oci_parse($connection, $query);
		$un = $dbtableprefix.'users';
		oci_bind_by_name($stmt, ':un', $un);
		if (!$stmt) {
			$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		$result = oci_execute($stmt);

		if($result) {
			$row = oci_fetch_row($stmt);
		}
		if(!$result or $row[0]==0) {
			OC_DB::createDbFromStructure('db_structure.xml');
		}
	}

	/**
	 *
	 * @param String $name
	 * @param String $password
	 * @param String $tablespace
	 * @param resource $connection
	 */
	private static function oci_createDBUser($name, $password, $tablespace, $connection) {
		$l = self::getTrans();
		$query = "SELECT * FROM all_users WHERE USERNAME = :un";
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		oci_bind_by_name($stmt, ':un', $name);
		$result = oci_execute($stmt);
		if(!$result) {
			$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}

		if(! oci_fetch_row($stmt)) {
			//user does not exists let's create it :)
			//password must start with alphabetic character in oracle
			$query = 'CREATE USER '.$name.' IDENTIFIED BY "'.$password.'" DEFAULT TABLESPACE '.$tablespace; //TODO set default tablespace
			$stmt = oci_parse($connection, $query);
			if (!$stmt) {
				$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
			//oci_bind_by_name($stmt, ':un', $name);
			$result = oci_execute($stmt);
			if(!$result) {
				$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s", name: %s, password: %s',
					array($query, $name, $password)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
		} else { // change password of the existing role
			$query = "ALTER USER :un IDENTIFIED BY :pw";
			$stmt = oci_parse($connection, $query);
			if (!$stmt) {
				$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
			oci_bind_by_name($stmt, ':un', $name);
			oci_bind_by_name($stmt, ':pw', $password);
			$result = oci_execute($stmt);
			if(!$result) {
				$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
				$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
		}
		// grant necessary roles
		$query = 'GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE TRIGGER, UNLIMITED TABLESPACE TO '.$name;
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		$result = oci_execute($stmt);
		if(!$result) {
			$entry = $l->t('DB Error: "%s"', array(oci_error($connection))) . '<br />';
			$entry .= $l->t('Offending command was: "%s", name: %s, password: %s',
				array($query, $name, $password)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
	}

	private static function setupMSSQLDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix) {
		$l = self::getTrans();

		//check if the database user has admin right
		$masterConnectionInfo = array( "Database" => "master", "UID" => $dbuser, "PWD" => $dbpass);

		$masterConnection = @sqlsrv_connect($dbhost, $masterConnectionInfo);
		if(!$masterConnection) {
			$entry = null;
			if( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			throw new Exception($l->t('MS SQL username and/or password not valid: %s', array($entry)));
		}

		OC_Config::setValue('dbuser', $dbuser);
		OC_Config::setValue('dbpassword', $dbpass);

		self::mssql_createDBLogin($dbuser, $dbpass, $masterConnection);

		self::mssql_createDatabase($dbname, $masterConnection);

		self::mssql_createDBUser($dbuser, $dbname, $masterConnection);

		sqlsrv_close($masterConnection);

		self::mssql_createDatabaseStructure($dbhost, $dbname, $dbuser, $dbpass, $dbtableprefix);
	}

	private static function mssql_createDBLogin($name, $password, $connection) {
		$query = "SELECT * FROM master.sys.server_principals WHERE name = '".$name."';";
		$result = sqlsrv_query($connection, $query);
		if ($result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		} else {
			$row = sqlsrv_fetch_array($result);

			if ($row === false) {
				if ( ($errors = sqlsrv_errors() ) != null) {
					$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
				} else {
					$entry = '';
				}
				$entry.='Offending command was: '.$query.'<br />';
				\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
			} else {
				if ($row == null) {
					$query = "CREATE LOGIN [".$name."] WITH PASSWORD = '".$password."';";
					$result = sqlsrv_query($connection, $query);
					if (!$result or $result === false) {
						if ( ($errors = sqlsrv_errors() ) != null) {
							$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
						} else {
							$entry = '';
						}
						$entry.='Offending command was: '.$query.'<br />';
						\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
					}
				}
			}
		}
	}

	private static function mssql_createDBUser($name, $dbname, $connection) {
		$query = "SELECT * FROM [".$dbname."].sys.database_principals WHERE name = '".$name."';";
		$result = sqlsrv_query($connection, $query);
		if ($result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		} else {
			$row = sqlsrv_fetch_array($result);

			if ($row === false) {
				if ( ($errors = sqlsrv_errors() ) != null) {
					$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
				} else {
					$entry = '';
				}
				$entry.='Offending command was: '.$query.'<br />';
				\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
			} else {
				if ($row == null) {
					$query = "USE [".$dbname."]; CREATE USER [".$name."] FOR LOGIN [".$name."];";
					$result = sqlsrv_query($connection, $query);
					if (!$result || $result === false) {
						if ( ($errors = sqlsrv_errors() ) != null) {
							$entry = 'DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
						} else {
							$entry = '';
						}
						$entry.='Offending command was: '.$query.'<br />';
						\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
					}
				}

				$query = "USE [".$dbname."]; EXEC sp_addrolemember 'db_owner', '".$name."';";
				$result = sqlsrv_query($connection, $query);
				if (!$result || $result === false) {
					if ( ($errors = sqlsrv_errors() ) != null) {
						$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
					} else {
						$entry = '';
					}
					$entry.='Offending command was: '.$query.'<br />';
					\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
				}
			}
		}
	}

	private static function mssql_createDatabase($dbname, $connection) {
		$query = "CREATE DATABASE [".$dbname."];";
		$result = sqlsrv_query($connection, $query);
		if (!$result || $result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		}
	}

	private static function mssql_createDatabaseStructure($dbhost, $dbname, $dbuser, $dbpass, $dbtableprefix) {
		$connectionInfo = array( "Database" => $dbname, "UID" => $dbuser, "PWD" => $dbpass);

		$connection = @sqlsrv_connect($dbhost, $connectionInfo);

		//fill the database if needed
		$query = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$dbtableprefix}users'";
		$result = sqlsrv_query($connection, $query);
		if ($result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		} else {
			$row = sqlsrv_fetch_array($result);

			if ($row === false) {
				if ( ($errors = sqlsrv_errors() ) != null) {
					$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
				} else {
					$entry = '';
				}
				$entry.='Offending command was: '.$query.'<br />';
				\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
			} else {
				if ($row == null) {
					OC_DB::createDbFromStructure('db_structure.xml');
				}
			}
		}

		sqlsrv_close($connection);
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
		$content.= "Options -Indexes\n";
		@file_put_contents(OC::$SERVERROOT.'/.htaccess', $content); //supress errors in case we don't have permissions for it

		self::protectDataDirectory();
	}

	public static function protectDataDirectory() {
		$content = "deny from all\n";
		$content.= "IndexIgnore *";
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
				'http://doc.owncloud.org/server/5.0/admin_manual/installation.html');

			$tmpl = new OC_Template('', 'error', 'guest');
			$tmpl->assign('errors', array(1 => array('error' => $error, 'hint' => $hint)));
			$tmpl->printPage();
			exit();
		}
	}
}
