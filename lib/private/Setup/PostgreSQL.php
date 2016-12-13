<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author eduardo <eduardo@vnexu.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

use OC\DatabaseException;
use OC\DB\QueryBuilder\Literal;
use OCP\IDBConnection;

class PostgreSQL extends AbstractDatabase {
	public $dbprettyname = 'PostgreSQL';

	public function setupDatabase($username) {
		$connection = $this->connect([
			'dbname' => 'postgres'
		]);
		//check for roles creation rights in postgresql
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder
			->select('rolname')
			->from('pg_roles')
			->where($builder->expr()->eq('rolcreaterole', new Literal('TRUE')))
			->andWhere($builder->expr()->eq('rolname', $builder->createNamedParameter($this->dbUser)));

		try {
			$result = $query->execute();
			$canCreateRoles = $result->rowCount() > 0;
		} catch (DatabaseException $e) {
			$canCreateRoles = false;
		}

		if($canCreateRoles) {
			//use the admin login data for the new database user

			//add prefix to the postgresql user name to prevent collisions
			$this->dbUser='oc_'.strtolower($username);
			//create a new password so we don't need to store the admin config in the config file
			$this->dbPassword = \OC::$server->getSecureRandom()->generate(30, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

			$this->createDBUser($connection);
		}

		$systemConfig = $this->config->getSystemConfig();
		$systemConfig->setValues([
			'dbuser'		=> $this->dbUser,
			'dbpassword'	=> $this->dbPassword,
		]);

		//create the database
		$this->createDatabase($connection);
		$query = $connection->prepare("select count(*) FROM pg_class WHERE relname=? limit 1");
		$query->execute([$this->tablePrefix . "users"]);
		$tablesSetup = $query->fetchColumn() > 0;

		// the connection to dbname=postgres is not needed anymore
		$connection->close();

		// connect to the ownCloud database (dbname=$this->dbname) and check if it needs to be filled
		$this->dbUser = $systemConfig->getValue('dbuser');
		$this->dbPassword = $systemConfig->getValue('dbpassword');
		$connection = $this->connect();
		try {
			$connection->connect();
		} catch (\Exception $e) {
			$this->logger->logException($e);
			throw new \OC\DatabaseSetupException($this->trans->t('PostgreSQL username and/or password not valid'),
				$this->trans->t('You need to enter either an existing account or the administrator.'));
		}


		if(!$tablesSetup) {
			\OC_DB::createDbFromStructure($this->dbDefinitionFile);
		}
	}

	private function createDatabase(IDBConnection $connection) {
		if(!$this->databaseExists($connection)) {
			//The database does not exists... let's create it
			$query = $connection->prepare("CREATE DATABASE " . addslashes($this->dbName) . " OWNER " . addslashes($this->dbUser));
			try {
				$query->execute();
			} catch (DatabaseException $e) {
				$this->logger->error('Error while trying to create database');
				$this->logger->logException($e);
			}
		} else {
			$query = $connection->prepare("REVOKE ALL PRIVILEGES ON DATABASE " . addslashes($this->dbName) . " FROM PUBLIC");
			try {
				$query->execute();
			} catch (DatabaseException $e) {
				$this->logger->error('Error while trying to restrict database permissions');
				$this->logger->logException($e);
			}
		}
	}

	private function userExists(IDBConnection $connection) {
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder->select('*')
			->from('pg_roles')
			->where($builder->expr()->eq('rolname', $builder->createNamedParameter($this->dbUser)));
		$result = $query->execute();
		return $result->rowCount() > 0;
	}

	private function databaseExists(IDBConnection $connection) {
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder->select('datname')
			->from('pg_database')
			->where($builder->expr()->eq('datname', $builder->createNamedParameter($this->dbName)));
		$result = $query->execute();
		return $result->rowCount() > 0;
	}

	private function createDBUser(IDBConnection $connection) {
		try {
			if ($this->userExists($connection)) {
				// change the password
				$query = $connection->prepare("ALTER ROLE " . addslashes($this->dbUser) . " WITH CREATEDB PASSWORD '" . addslashes($this->dbPassword) . "'");
			} else {
				// create the user
				$query = $connection->prepare("CREATE USER " . addslashes($this->dbUser) . " CREATEDB PASSWORD '" . addslashes($this->dbPassword) . "'");
			}
			$query->execute();
		} catch (DatabaseException $e) {
			$this->logger->error('Error while trying to create database user');
			$this->logger->logException($e);
		}
	}
}
