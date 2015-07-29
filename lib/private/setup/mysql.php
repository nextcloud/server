<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Michael Göhler <somebody.here@gmx.de>
 * @author Robin Appelman <icewind@owncloud.com>
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
namespace OC\Setup;

use OC\DB\ConnectionFactory;

class MySQL extends AbstractDatabase {
	public $dbprettyname = 'MySQL/MariaDB';

	public function setupDatabase($username) {
		//check if the database user has admin right
		$connection = $this->connect();
		//user already specified in config
		$oldUser=\OC_Config::getValue('dbuser', false);

		//we don't have a dbuser specified in config
		if($this->dbUser!=$oldUser) {
			//add prefix to the admin username to prevent collisions
			$adminUser=substr('oc_'.$username, 0, 16);

			$i = 1;
			while(true) {
				//this should be enough to check for admin rights in mysql
				$query="SELECT user FROM mysql.user WHERE user='$adminUser'";
				$result = $connection->executeQuery($query);

				//current dbuser has admin rights
				if($result) {
					$data = $result->fetchAll();
					//new dbuser does not exist
					if(count($data) === 0) {
						//use the admin login data for the new database user
						$this->dbUser=$adminUser;

						//create a random password so we don't need to store the admin password in the config file
						$this->dbPassword=\OC_Util::generateRandomBytes(30);

						$this->createDBUser($connection);

						break;
					} else {
						//repeat with different username
						$length=strlen((string)$i);
						$adminUser=substr('oc_'.$username, 0, 16 - $length).$i;
						$i++;
					}
				} else {
					break;
				}
			};

			\OC_Config::setValues([
				'dbuser'		=> $this->dbUser,
				'dbpassword'	=> $this->dbPassword,
			]);
		}

		//create the database
		$this->createDatabase($connection);

		//fill the database if needed
		$query='select count(*) from information_schema.tables'
			." where table_schema='".$this->dbName."' AND table_name = '".$this->tablePrefix."users';";
		$result = $connection->executeQuery($query);
		$row = $result->fetch();
		if(!$result or $row[0]==0) {
			\OC_DB::createDbFromStructure($this->dbDefinitionFile);
		}
	}

	/**
	 * @param \OC\DB\Connection $connection
	 */
	private function createDatabase($connection) {
		$name = $this->dbName;
		$user = $this->dbUser;
		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$query = "CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8 COLLATE utf8_bin;";
		$connection->executeUpdate($query);

		//this query will fail if there aren't the right permissions, ignore the error
		$query="GRANT ALL PRIVILEGES ON `$name` . * TO '$user'";
		$connection->executeUpdate($query);
	}

	/**
	 * @param \OC\DB\Connection $connection
	 * @throws \OC\DatabaseSetupException
	 */
	private function createDBUser($connection) {
		$name = $this->dbUser;
		$password = $this->dbPassword;
		// we need to create 2 accounts, one for global use and one for local user. if we don't specify the local one,
		// the anonymous user would take precedence when there is one.
		$query = "CREATE USER '$name'@'localhost' IDENTIFIED BY '$password'";
		$connection->executeUpdate($query);
		$query = "CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$connection->executeUpdate($query);
	}

	/**
	 * @return \OC\DB\Connection
	 * @throws \OC\DatabaseSetupException
	 */
	private function connect() {
		$type = 'mysql';
		$connectionParams = array(
			'host' => $this->dbHost,
			'user' => $this->dbUser,
			'password' => $this->dbPassword,
			'tablePrefix' => $this->tablePrefix,
		);
		$cf = new ConnectionFactory();
		return $cf->getConnection($type, $connectionParams);
	}
}
