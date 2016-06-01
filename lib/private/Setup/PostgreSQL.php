<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author eduardo <eduardo@vnexu.net>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OC\Setup;

class PostgreSQL extends AbstractDatabase {
	public $dbprettyname = 'PostgreSQL';

	public function setupDatabase($username) {
		$e_host = addslashes($this->dbHost);
		$e_user = addslashes($this->dbUser);
		$e_password = addslashes($this->dbPassword);

		// Fix database with port connection
		if(strpos($e_host, ':')) {
			list($e_host, $port)=explode(':', $e_host, 2);
		} else {
			$port=false;
		}

		//check if the database user has admin rights
		$connection_string = "host='$e_host' dbname=postgres user='$e_user' port='$port' password='$e_password'";
		$connection = @pg_connect($connection_string);
		if(!$connection) {
			// Try if we can connect to the DB with the specified name
			$e_dbname = addslashes($this->dbName);
			$connection_string = "host='$e_host' dbname='$e_dbname' user='$e_user' port='$port' password='$e_password'";
			$connection = @pg_connect($connection_string);

			if(!$connection)
				throw new \OC\DatabaseSetupException($this->trans->t('PostgreSQL username and/or password not valid'),
						$this->trans->t('You need to enter either an existing account or the administrator.'));
		}
		$e_user = pg_escape_string($this->dbUser);
		//check for roles creation rights in postgresql
		$query="SELECT 1 FROM pg_roles WHERE rolcreaterole=TRUE AND rolname='$e_user'";
		$result = pg_query($connection, $query);
		if($result and pg_num_rows($result) > 0) {
			//use the admin login data for the new database user

			//add prefix to the postgresql user name to prevent collisions
			$this->dbUser='oc_'.$username;
			//create a new password so we don't need to store the admin config in the config file
			$this->dbPassword = \OC::$server->getSecureRandom()->generate(30, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

			$this->createDBUser($connection);
		}

		$systemConfig = \OC::$server->getSystemConfig();
		$systemConfig->setValues([
			'dbuser'		=> $this->dbUser,
			'dbpassword'	=> $this->dbPassword,
		]);

		//create the database
		$this->createDatabase($connection);

		// the connection to dbname=postgres is not needed anymore
		pg_close($connection);

		// connect to the ownCloud database (dbname=$this->dbname) and check if it needs to be filled
		$this->dbUser = $systemConfig->getValue('dbuser');
		$this->dbPassword = $systemConfig->getValue('dbpassword');

		$e_host = addslashes($this->dbHost);
		$e_dbname = addslashes($this->dbName);
		$e_user = addslashes($this->dbUser);
		$e_password = addslashes($this->dbPassword);

        	// Fix database with port connection
		if(strpos($e_host, ':')) {
			list($e_host, $port)=explode(':', $e_host, 2);
		} else {
			$port=false;
		}

		$connection_string = "host='$e_host' dbname='$e_dbname' user='$e_user' port='$port' password='$e_password'";
		$connection = @pg_connect($connection_string);
		if(!$connection) {
			throw new \OC\DatabaseSetupException($this->trans->t('PostgreSQL username and/or password not valid'),
					$this->trans->t('You need to enter either an existing account or the administrator.'));
		}
		$query = "select count(*) FROM pg_class WHERE relname='".$this->tablePrefix."users' limit 1";
		$result = pg_query($connection, $query);
		if($result) {
			$row = pg_fetch_row($result);
		}
		if(!$result or $row[0]==0) {
			\OC_DB::createDbFromStructure($this->dbDefinitionFile);
		}
	}

	private function createDatabase($connection) {
		//we can't use OC_BD functions here because we need to connect as the administrative user.
		$e_name = pg_escape_string($this->dbName);
		$e_user = pg_escape_string($this->dbUser);
		$query = "select datname from pg_database where datname = '$e_name'";
		$result = pg_query($connection, $query);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OCP\Util::writeLog('setup.pg', $entry, \OCP\Util::WARN);
		}
		if(! pg_fetch_row($result)) {
			//The database does not exists... let's create it
			$query = "CREATE DATABASE \"$e_name\" OWNER \"$e_user\"";
			$result = pg_query($connection, $query);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				\OCP\Util::writeLog('setup.pg', $entry, \OCP\Util::WARN);
			}
			else {
				$query = "REVOKE ALL PRIVILEGES ON DATABASE \"$e_name\" FROM PUBLIC";
				pg_query($connection, $query);
			}
		}
	}

	private function createDBUser($connection) {
		$e_name = pg_escape_string($this->dbUser);
		$e_password = pg_escape_string($this->dbPassword);
		$query = "select * from pg_roles where rolname='$e_name';";
		$result = pg_query($connection, $query);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OCP\Util::writeLog('setup.pg', $entry, \OCP\Util::WARN);
		}

		if(! pg_fetch_row($result)) {
			//user does not exists let's create it :)
			$query = "CREATE USER \"$e_name\" CREATEDB PASSWORD '$e_password';";
			$result = pg_query($connection, $query);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				\OCP\Util::writeLog('setup.pg', $entry, \OCP\Util::WARN);
			}
		}
		else { // change password of the existing role
			$query = "ALTER ROLE \"$e_name\" WITH PASSWORD '$e_password';";
			$result = pg_query($connection, $query);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array(pg_last_error($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				\OCP\Util::writeLog('setup.pg', $entry, \OCP\Util::WARN);
			}
		}
	}
}
