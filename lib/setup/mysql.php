<?php

namespace OC\Setup;

class MySQL {
	public static function setupDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix, $username) {
		//check if the database user has admin right
		$l = \OC_Setup::getTrans();
		$connection = @mysql_connect($dbhost, $dbuser, $dbpass);
		if(!$connection) {
			throw new DatabaseSetupException($l->t('MySQL username and/or password not valid'),
				$l->t('You need to enter either an existing account or the administrator.'));
		}
		$oldUser=\OC_Config::getValue('dbuser', false);

		//this should be enough to check for admin rights in mysql
		$query="SELECT user FROM mysql.user WHERE user='$dbuser'";
		if(mysql_query($query, $connection)) {
			//use the admin login data for the new database user

			//add prefix to the mysql user name to prevent collisions
			$dbusername=substr('oc_'.$username, 0, 16);
			if($dbusername!=$oldUser) {
				//hash the password so we don't need to store the admin config in the config file
				$dbpassword=OC_Util::generate_random_bytes(30);

				self::createDBUser($dbusername, $dbpassword, $connection);

				\OC_Config::setValue('dbuser', $dbusername);
				\OC_Config::setValue('dbpassword', $dbpassword);
			}

			//create the database
			self::createDatabase($dbname, $dbusername, $connection);
		}
		else {
			if($dbuser!=$oldUser) {
				\OC_Config::setValue('dbuser', $dbuser);
				\OC_Config::setValue('dbpassword', $dbpass);
			}

			//create the database
			self::createDatabase($dbname, $dbuser, $connection);
		}

		//fill the database if needed
		$query='select count(*) from information_schema.tables'
			." where table_schema='$dbname' AND table_name = '{$dbtableprefix}users';";
		$result = mysql_query($query, $connection);
		if($result) {
			$row=mysql_fetch_row($result);
		}
		if(!$result or $row[0]==0) {
			\OC_DB::createDbFromStructure('db_structure.xml');
		}
		mysql_close($connection);
	}

	private static function createDatabase($name, $user, $connection) {
		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$l = \OC_Setup::getTrans();
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
		$l = \OC_Setup::getTrans();
		$query = "CREATE USER '$name'@'localhost' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		if (!$result) {
			throw new DatabaseSetupException($l->t("MySQL user '%s'@'localhost' exists already.", array($name)),
				$l->t("Drop this user from MySQL", array($name)));
		}
		$query = "CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		if (!$result) {
			throw new DatabaseSetupException($l->t("MySQL user '%s'@'%%' already exists", array($name)),
				$l->t("Drop this user from MySQL."));
		}
	}
}
