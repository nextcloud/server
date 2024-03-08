<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author eduardo <eduardo@vnexu.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vitor Mattos <vitor@php.rio>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Setup;

use OC\DatabaseException;
use OC\DB\Connection;
use OC\DB\QueryBuilder\Literal;
use OCP\Security\ISecureRandom;

class PostgreSQL extends AbstractDatabase {
	public $dbprettyname = 'PostgreSQL';

	/**
	 * @param string $username
	 * @throws \OC\DatabaseSetupException
	 */
	public function setupDatabase($username) {
		try {
			$connection = $this->connect([
				'dbname' => 'postgres'
			]);
			if ($this->tryCreateDbUser) {
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

				if ($canCreateRoles) {
					$connectionMainDatabase = $this->connect();
					//use the admin login data for the new database user

					//add prefix to the postgresql user name to prevent collisions
					$this->dbUser = 'oc_' . strtolower($username);
					//create a new password so we don't need to store the admin config in the config file
					$this->dbPassword = \OC::$server->getSecureRandom()->generate(30, ISecureRandom::CHAR_ALPHANUMERIC);

					$this->createDBUser($connection);

					// Go to the main database and grant create on the public schema
					// The code below is implemented to make installing possible with PostgreSQL version 15:
					// https://www.postgresql.org/docs/release/15.0/
					// From the release notes: For new databases having no need to defend against insider threats, granting CREATE permission will yield the behavior of prior releases
					// Therefore we assume that the database is only used by one user/service which is Nextcloud
					// Additional services should get installed in a separate database in order to stay secure
					// Also see https://www.postgresql.org/docs/15/ddl-schemas.html#DDL-SCHEMAS-PATTERNS
					$connectionMainDatabase->executeQuery('GRANT CREATE ON SCHEMA public TO "' . addslashes($this->dbUser) . '"');
					$connectionMainDatabase->close();
				}
			}

			$this->config->setValues([
				'dbuser' => $this->dbUser,
				'dbpassword' => $this->dbPassword,
			]);

			//create the database
			$this->createDatabase($connection);
			// the connection to dbname=postgres is not needed anymore
			$connection->close();
		} catch (\Exception $e) {
			$this->logger->warning('Error trying to connect as "postgres", assuming database is setup and tables need to be created', [
				'exception' => $e,
			]);
			$this->config->setValues([
				'dbuser' => $this->dbUser,
				'dbpassword' => $this->dbPassword,
			]);
		}

		// connect to the database (dbname=$this->dbname) and check if it needs to be filled
		$this->dbUser = $this->config->getValue('dbuser');
		$this->dbPassword = $this->config->getValue('dbpassword');
		$connection = $this->connect();
		try {
			$connection->connect();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [
				'exception' => $e,
			]);
			throw new \OC\DatabaseSetupException($this->trans->t('PostgreSQL Login and/or password not valid'),
				$this->trans->t('You need to enter details of an existing account.'), 0, $e);
		}
	}

	private function createDatabase(Connection $connection) {
		if (!$this->databaseExists($connection)) {
			//The database does not exists... let's create it
			$query = $connection->prepare("CREATE DATABASE " . addslashes($this->dbName) . " OWNER \"" . addslashes($this->dbUser) . '"');
			try {
				$query->execute();
			} catch (DatabaseException $e) {
				$this->logger->error('Error while trying to create database', [
					'exception' => $e,
				]);
			}
		} else {
			$query = $connection->prepare("REVOKE ALL PRIVILEGES ON DATABASE " . addslashes($this->dbName) . " FROM PUBLIC");
			try {
				$query->execute();
			} catch (DatabaseException $e) {
				$this->logger->error('Error while trying to restrict database permissions', [
					'exception' => $e,
				]);
			}
		}
	}

	private function userExists(Connection $connection) {
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder->select('*')
			->from('pg_roles')
			->where($builder->expr()->eq('rolname', $builder->createNamedParameter($this->dbUser)));
		$result = $query->execute();
		return $result->rowCount() > 0;
	}

	private function databaseExists(Connection $connection) {
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder->select('datname')
			->from('pg_database')
			->where($builder->expr()->eq('datname', $builder->createNamedParameter($this->dbName)));
		$result = $query->execute();
		return $result->rowCount() > 0;
	}

	private function createDBUser(Connection $connection) {
		$dbUser = $this->dbUser;
		try {
			$i = 1;
			while ($this->userExists($connection)) {
				$i++;
				$this->dbUser = $dbUser . $i;
			}

			// create the user
			$query = $connection->prepare("CREATE USER \"" . addslashes($this->dbUser) . "\" CREATEDB PASSWORD '" . addslashes($this->dbPassword) . "'");
			$query->execute();
			if ($this->databaseExists($connection)) {
				$query = $connection->prepare('GRANT CONNECT ON DATABASE ' . addslashes($this->dbName) . ' TO "' . addslashes($this->dbUser) . '"');
				$query->execute();
			}
		} catch (DatabaseException $e) {
			$this->logger->error('Error while trying to create database user', [
				'exception' => $e,
			]);
		}
	}
}
