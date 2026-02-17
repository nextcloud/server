<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Setup;

use OC\DatabaseException;
use OC\DatabaseSetupException;
use OC\DB\Connection;
use OC\DB\QueryBuilder\Literal;
use OCP\Security\ISecureRandom;
use OCP\Server;

class PostgreSQL extends AbstractDatabase {
	public $dbprettyname = 'PostgreSQL';

	/**
	 * @throws DatabaseSetupException
	 */
	public function setupDatabase(): void {
		$canCreateRoles = false;
		$adminDBConnection = null; // For admin tasks on 'postgres' DB
		$nextcloudDBConnection = null; // For schema setup on Nextcloud DB

		try {
			$adminDBConnection = $this->connect(['dbname' => 'postgres']);

			if ($this->tryCreateDbUser) {
				$canCreateRoles = $this->checkCanCreateRoles($adminDBConnection);

				if ($canCreateRoles) {
					$nextcloudDBConnection = $this->connect();
					// Create Nextcloud-specific database user
					$this->createDBUser($adminDBConnection);
				}
			}

			// Store new credentials in config
			$this->config->setValues([
				'dbuser' => $this->dbUser,
				'dbpassword' => $this->dbPassword,
			]);

			$this->createDatabase($adminDBConnection);
			$adminDBConnection->close();

			if ($this->tryCreateDbUser) {
				if ($canCreateRoles) {
					// Go to the main database and grant create on the public schema
					// The code below is implemented to make installing possible with PostgreSQL version 15:
					// https://www.postgresql.org/docs/release/15.0/
					// From the release notes: For new databases having no need to defend against insider threats, granting CREATE permission will yield the behavior of prior releases
					// Therefore we assume that the database is only used by one user/service which is Nextcloud
					// Additional services should get installed in a separate database in order to stay secure
					// Also see https://www.postgresql.org/docs/15/ddl-schemas.html#DDL-SCHEMAS-PATTERNS
					$nextcloudDBConnection->executeQuery('GRANT CREATE ON SCHEMA public TO "' . addslashes($this->dbUser) . '"');
					$nextcloudDBConnection->close();
				}
			}
		} catch (\Exception $e) {
			$this->logger->warning('Error trying to connect as "postgres", assuming database is setup and tables need to be created', [
				'exception' => $e,
			]);
			$this->config->setValues([
				'dbuser' => $this->dbUser,
				'dbpassword' => $this->dbPassword,
			]);
		}

		// Verify we can connect with the configured credentials
		$this->verifyDatabaseConnection();
	}

	/**
	 * Checks if the current user has CREATEROLE privilege.
	 */
	private function checkCanCreateRoles(Connection $connection): bool {
		try {
			$builder = $connection->getQueryBuilder();
			$builder->automaticTablePrefix(false);

			$query = $builder
				->select('rolname')
				->from('pg_roles')
				->where($builder->expr()->eq('rolcreaterole', new Literal('TRUE')))
				->andWhere($builder->expr()->eq('rolname', $builder->createNamedParameter($this->dbUser)));

			$result = $query->executeQuery();
			return $result->rowCount() > 0;
		} catch (DatabaseException $e) {
			$this->logger->debug('Could not check role creation privileges', [
				'exception' => $e,
				'app' => 'pgsql.setup',
			]);
			return false;
		}
	}

	private function createDatabase(Connection $connection): void {
		if (!$this->databaseExists($connection)) {
			//The database does not exists... let's create it
			$query = $connection->prepare('CREATE DATABASE ' . addslashes($this->dbName) . ' OWNER "' . addslashes($this->dbUser) . '"');
			try {
				$query->executeStatement();
			} catch (DatabaseException $e) {
				$this->logger->error('Error while trying to create database', [
					'exception' => $e,
				]);
			}
		} else {
			$query = $connection->prepare('REVOKE ALL PRIVILEGES ON DATABASE ' . addslashes($this->dbName) . ' FROM PUBLIC');
			try {
				$query->executeStatement();
			} catch (DatabaseException $e) {
				$this->logger->error('Error while trying to restrict database permissions', [
					'exception' => $e,
				]);
			}
		}
	}

	private function userExists(Connection $connection, string $roleName): bool {
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder->select('*')
			->from('pg_roles')
			->where($builder->expr()->eq('rolname', $builder->createNamedParameter($roleName)));
		$result = $query->executeQuery();
		return $result->rowCount() > 0;
	}

	private function databaseExists(Connection $connection): bool {
		$builder = $connection->getQueryBuilder();
		$builder->automaticTablePrefix(false);
		$query = $builder->select('datname')
			->from('pg_database')
			->where($builder->expr()->eq('datname', $builder->createNamedParameter($this->dbName)));
		$result = $query->executeQuery();
		return $result->rowCount() > 0;
	}

	private function createDBUser(Connection $connection): void {
		// Generate Nextcloud-specific credentials so we don't need to store / use the db admin credentials
		$baseUser = 'oc_admin';
		$newUser = $baseUser;
		$newPassword = Server::get(ISecureRandom::class)->generate(30, ISecureRandom::CHAR_ALPHANUMERIC);

		// Find/generate an available username
		try {
			$i = 1;
			while ($this->userExists($connection, $newUser)) {
				$i++;
				$newUser = $baseUser . $i;
			}

			// Create the new user
			$query = $connection->prepare('CREATE USER "' . addslashes($newUser) . "\" CREATEDB PASSWORD '" . addslashes($newPassword) . "'");
			$query->executeStatement();

			// Grant database access if database already exists
			if ($this->databaseExists($connection)) {
				$query = $connection->prepare('GRANT CONNECT ON DATABASE ' . addslashes($this->dbName) . ' TO "' . addslashes($newUser) . '"');
				$query->executeStatement();
			}

			$this->dbUser = $newUser;
			$this->dbPassword = $newPassword;
		} catch (DatabaseException $e) {
			$this->logger->error('Error while trying to create database user', [
				'exception' => $e,
			]);
		}
	}

	/**
	 * Verifies connection to the Nextcloud database with configured credentials.
	 * 
	 * @throws DatabaseSetupException If connection fails
	 */
	private function verifyDatabaseConnection(): void {
		// Reload credentials from config (may have been updated + to verify config)
		$this->dbUser = $this->config->getValue('dbuser');
		$this->dbPassword = $this->config->getValue('dbpassword');

		try {
			$connection = $this->connect(); // Create new connection object with final config
			$connection->connect(); // Actually connect to verify credentials work
		} catch (\Exception $e) {
			$this->logger->error('Database connection verification failed', [
				'user' => $this->dbUser,
				'database' => $this->dbName,
				'exception' => $e,
				'app' => 'pgsql.setup',
			]);

			throw new DatabaseSetupException(
				$this->trans->t('PostgreSQL login and/or password not valid'),
				$this->trans->t('You need to enter details of an existing account.'),
				0,
				$e
			);
    	}
	}
}
