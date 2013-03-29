<?php

namespace OC\Setup;

class PostgreSQL {
	public static function setupDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $username) {
		$e_host = addslashes($dbhost);
		$e_user = addslashes($dbuser);
		$e_password = addslashes($dbpass);
		$l = \OC_Setup::getTrans();

		//check if the database user has admin rights
		$connection_string = "host='$e_host' dbname=postgres user='$e_user' password='$e_password'";
		$connection = @pg_connect($connection_string);
		if(!$connection) {
			// Try if we can connect to the DB with the specified name
			$e_dbname = addslashes($dbname);
			$connection_string = "host='$e_host' dbname='$e_dbname' user='$e_user' password='$e_password'";
			$connection = @pg_connect($connection_string);

			if(!$connection)
				throw new DatabaseSetupException($l->t('PostgreSQL username and/or password not valid'),
						$l->t('You need to enter either an existing account or the administrator.'));
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
			$dbpassword=OC_Util::generate_random_bytes(30);

			self::createDBUser($dbusername, $dbpassword, $connection);

			\OC_Config::setValue('dbuser', $dbusername);
			\OC_Config::setValue('dbpassword', $dbpassword);

			//create the database
			self::createDatabase($dbname, $dbusername, $connection);
		}
		else {
			\OC_Config::setValue('dbuser', $dbuser);
			\OC_Config::setValue('dbpassword', $dbpass);

			//create the database
			self::createDatabase($dbname, $dbuser, $connection);
		}

		// the connection to dbname=postgres is not needed anymore
		pg_close($connection);

		// connect to the ownCloud database (dbname=$dbname) and check if it needs to be filled
		$dbuser = \OC_Config::getValue('dbuser');
		$dbpass = \OC_Config::getValue('dbpassword');

		$e_host = addslashes($dbhost);
		$e_dbname = addslashes($dbname);
		$e_user = addslashes($dbuser);
		$e_password = addslashes($dbpass);

		$connection_string = "host='$e_host' dbname='$e_dbname' user='$e_user' password='$e_password'";
		$connection = @pg_connect($connection_string);
		if(!$connection) {
			throw new DatabaseSetupException($l->t('PostgreSQL username and/or password not valid'),
					$l->t('You need to enter either an existing account or the administrator.'));
		}
		$query = "select count(*) FROM pg_class WHERE relname='{$dbtableprefix}users' limit 1";
		$result = pg_query($connection, $query);
		if($result) {
			$row = pg_fetch_row($result);
		}
		if(!$result or $row[0]==0) {
			\OC_DB::createDbFromStructure('db_structure.xml');
		}
	}

	private static function createDatabase($name, $user, $connection) {

		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$l = \OC_Setup::getTrans();
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

	private static function createDBUser($name, $password, $connection) {
		$l = \OC_Setup::getTrans();
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
}
