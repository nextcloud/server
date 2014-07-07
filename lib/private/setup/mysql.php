<?php

namespace OC\Setup;

class MySQL extends AbstractDatabase {
	public $dbprettyname = 'MySQL/MariaDB';

	public function setupDatabase($username) {
		//check if the database user has admin right
		$connection = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword);
		if(!$connection) {
			throw new \DatabaseSetupException($this->trans->t('MySQL/MariaDB username and/or password not valid'),
				$this->trans->t('You need to enter either an existing account or the administrator.'));
		}
		$oldUser=\OC_Config::getValue('dbuser', false);

		//this should be enough to check for admin rights in mysql
		$query="SELECT user FROM mysql.user WHERE user='$this->dbuser'";
		if(mysql_query($query, $connection)) {
			//use the admin login data for the new database user

			//add prefix to the mysql user name to prevent collisions
			$this->dbuser=substr('oc_'.$username, 0, 16);
			if($this->dbuser!=$oldUser) {
				//hash the password so we don't need to store the admin config in the config file
				$this->dbpassword=\OC_Util::generateRandomBytes(30);

				$this->createDBUser($connection);

				\OC_Config::setValue('dbuser', $this->dbuser);
				\OC_Config::setValue('dbpassword', $this->dbpassword);
			}

			//create the database
			$this->createDatabase($connection);
		}
		else {
			if($this->dbuser!=$oldUser) {
				\OC_Config::setValue('dbuser', $this->dbuser);
				\OC_Config::setValue('dbpassword', $this->dbpassword);
			}

			//create the database
			$this->createDatabase($connection);
		}

		//fill the database if needed
		$query='select count(*) from information_schema.tables'
			." where table_schema='".$this->dbname."' AND table_name = '".$this->tableprefix."users';";
		$result = mysql_query($query, $connection);
		if($result) {
			$row=mysql_fetch_row($result);
		}
		if(!$result or $row[0]==0) {
			\OC_DB::createDbFromStructure($this->dbDefinitionFile);
		}
		mysql_close($connection);
	}

	private function createDatabase($connection) {
		$name = $this->dbname;
		$user = $this->dbuser;
		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$query = "CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8 COLLATE utf8_bin;";
		$result = mysql_query($query, $connection);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array(mysql_error($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		}
		$query="GRANT ALL PRIVILEGES ON `$name` . * TO '$user'";

		//this query will fail if there aren't the right permissions, ignore the error
		mysql_query($query, $connection);
	}

	private function createDBUser($connection) {
		$name = $this->dbuser;
		$password = $this->dbpassword;
		// we need to create 2 accounts, one for global use and one for local user. if we don't specify the local one,
		// the anonymous user would take precedence when there is one.
		$query = "CREATE USER '$name'@'localhost' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		if (!$result) {
			throw new \DatabaseSetupException($this->trans->t("MySQL/MariaDB user '%s'@'localhost' exists already.", array($name)),
				$this->trans->t("Drop this user from MySQL/MariaDB", array($name)));
		}
		$query = "CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		if (!$result) {
			throw new \DatabaseSetupException($this->trans->t("MySQL/MariaDB user '%s'@'%%' already exists", array($name)),
				$this->trans->t("Drop this user from MySQL/MariaDB."));
		}
	}
}
