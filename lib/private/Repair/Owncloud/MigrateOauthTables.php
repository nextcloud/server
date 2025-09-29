<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair\Owncloud;

use OC\Authentication\Token\IProvider as ITokenProvider;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Token\IToken;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class MigrateOauthTables implements IRepairStep {

	public function __construct(
		protected Connection $db,
		private AccessTokenMapper $accessTokenMapper,
		private ITokenProvider $tokenProvider,
		private ISecureRandom $random,
		private ITimeFactory $timeFactory,
		private ICrypto $crypto,
		private IConfig $config,
	) {
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Migrate oauth2_clients table to nextcloud schema';
	}

	public function run(IOutput $output) {
		$schema = new SchemaWrapper($this->db);
		if (!$schema->hasTable('oauth2_clients')) {
			$output->info('oauth2_clients table does not exist.');
			return;
		}

		// Create column and then migrate before handling unique index.
		// So that we can distinguish between legacy (from oc) and new rows (from nc).
		$table = $schema->getTable('oauth2_access_tokens');
		if (!$table->hasColumn('hashed_code')) {
			$output->info('Prepare the oauth2_access_tokens table schema.');
			$table->addColumn('hashed_code', 'string', [
				'notnull' => true,
				'length' => 128,
			]);

			// Regenerate schema after migrating to it
			$this->db->migrateToSchema($schema->getWrappedSchema());
			$schema = new SchemaWrapper($this->db);
		}

		$output->info('Update the oauth2_access_tokens table schema.');
		$table = $schema->getTable('oauth2_access_tokens');
		if (!$table->hasColumn('encrypted_token')) {
			$table->addColumn('encrypted_token', 'string', [
				'notnull' => true,
				'length' => 786,
			]);
		}
		if (!$table->hasIndex('oauth2_access_hash_idx')) {
			// Drop legacy access codes first to prevent integrity constraint violations
			$qb = $this->db->getQueryBuilder();
			$qb->delete('oauth2_access_tokens')
				->where($qb->expr()->eq('hashed_code', $qb->createNamedParameter('')));
			$qb->executeStatement();

			$table->addUniqueIndex(['hashed_code'], 'oauth2_access_hash_idx');
		}
		if (!$table->hasIndex('oauth2_access_client_id_idx')) {
			$table->addIndex(['client_id'], 'oauth2_access_client_id_idx');
		}
		if (!$table->hasColumn('token_id')) {
			$table->addColumn('token_id', 'integer', [
				'notnull' => true,
			]);
		}
		if ($table->hasColumn('expires')) {
			$table->dropColumn('expires');
		}
		if ($table->hasColumn('user_id')) {
			$table->dropColumn('user_id');
		}
		if ($table->hasColumn('token')) {
			$table->dropColumn('token');
		}

		$output->info('Update the oauth2_clients table schema.');
		$table = $schema->getTable('oauth2_clients');
		if ($table->getColumn('name')->getLength() !== 64) {
			// shorten existing values before resizing the column
			$qb = $this->db->getQueryBuilder();
			$qb->update('oauth2_clients')
				->set('name', $qb->createParameter('shortenedName'))
				->where($qb->expr()->eq('id', $qb->createParameter('theId')));

			$qbSelect = $this->db->getQueryBuilder();
			$qbSelect->select('id', 'name')
				->from('oauth2_clients');

			$result = $qbSelect->executeQuery();
			while ($row = $result->fetch()) {
				$id = $row['id'];
				$shortenedName = mb_substr($row['name'], 0, 64);
				$qb->setParameter('theId', $id, IQueryBuilder::PARAM_INT);
				$qb->setParameter('shortenedName', $shortenedName, IQueryBuilder::PARAM_STR);
				$qb->executeStatement();
			}
			$result->closeCursor();

			// safely set the new column length
			$table->getColumn('name')->setLength(64);
		}
		if ($table->hasColumn('allow_subdomains')) {
			$table->dropColumn('allow_subdomains');
		}
		if ($table->hasColumn('trusted')) {
			$table->dropColumn('trusted');
		}

		if (!$schema->getTable('oauth2_clients')->hasColumn('client_identifier')) {
			$table->addColumn('client_identifier', 'string', [
				'notnull' => true,
				'length' => 64,
				'default' => ''
			]);
			$table->addIndex(['client_identifier'], 'oauth2_client_id_idx');
		}

		// Regenerate schema after migrating to it
		$this->db->migrateToSchema($schema->getWrappedSchema());
		$schema = new SchemaWrapper($this->db);

		if ($schema->getTable('oauth2_clients')->hasColumn('identifier')) {
			$output->info("Move identifier column's data to the new client_identifier column.");
			// 1. Fetch all [id, identifier] couple.
			$selectQuery = $this->db->getQueryBuilder();
			$selectQuery->select('id', 'identifier')->from('oauth2_clients');
			$result = $selectQuery->executeQuery();
			$identifiers = $result->fetchAll();
			$result->closeCursor();

			// 2. Insert them into the client_identifier column.
			foreach ($identifiers as ['id' => $id, 'identifier' => $clientIdentifier]) {
				$insertQuery = $this->db->getQueryBuilder();
				$insertQuery->update('oauth2_clients')
					->set('client_identifier', $insertQuery->createNamedParameter($clientIdentifier, IQueryBuilder::PARAM_STR))
					->where($insertQuery->expr()->eq('id', $insertQuery->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
					->executeStatement();
			}

			$output->info('Drop the identifier column.');
			$table = $schema->getTable('oauth2_clients');
			$table->dropColumn('identifier');

			// Regenerate schema after migrating to it
			$this->db->migrateToSchema($schema->getWrappedSchema());
			$schema = new SchemaWrapper($this->db);
		}

		$enableOcClients = $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);
		if ($enableOcClients) {
			$output->info('Delete clients (and their related access tokens) with the redirect_uri starting with oc://');
		} else {
			$output->info('Delete clients (and their related access tokens) with the redirect_uri starting with oc:// or ending with *');
		}
		// delete the access tokens
		$qbDeleteAccessTokens = $this->db->getQueryBuilder();

		$qbSelectClientId = $this->db->getQueryBuilder();
		$qbSelectClientId->select('id')
			->from('oauth2_clients')
			->where(
				$qbSelectClientId->expr()->iLike('redirect_uri', $qbDeleteAccessTokens->createNamedParameter('oc://%', IQueryBuilder::PARAM_STR))
			);
		if (!$enableOcClients) {
			$qbSelectClientId->orWhere(
				$qbSelectClientId->expr()->iLike('redirect_uri', $qbDeleteAccessTokens->createNamedParameter('%*', IQueryBuilder::PARAM_STR))
			);
		}

		$qbDeleteAccessTokens->delete('oauth2_access_tokens')
			->where(
				$qbSelectClientId->expr()->in('client_id', $qbDeleteAccessTokens->createFunction($qbSelectClientId->getSQL()), IQueryBuilder::PARAM_STR_ARRAY)
			);
		$qbDeleteAccessTokens->executeStatement();

		// delete the clients
		$qbDeleteClients = $this->db->getQueryBuilder();
		$qbDeleteClients->delete('oauth2_clients')
			->where(
				$qbDeleteClients->expr()->iLike('redirect_uri', $qbDeleteClients->createNamedParameter('oc://%', IQueryBuilder::PARAM_STR))
			);
		if (!$enableOcClients) {
			$qbDeleteClients->orWhere(
				$qbDeleteClients->expr()->iLike('redirect_uri', $qbDeleteClients->createNamedParameter('%*', IQueryBuilder::PARAM_STR))
			);
		}
		$qbDeleteClients->executeStatement();

		// Migrate legacy refresh tokens from oc
		if ($schema->hasTable('oauth2_refresh_tokens')) {
			$output->info('Migrate legacy oauth2 refresh tokens.');

			$qbSelect = $this->db->getQueryBuilder();
			$qbSelect->select('*')
				->from('oauth2_refresh_tokens');
			$result = $qbSelect->executeQuery();
			$now = $this->timeFactory->now()->getTimestamp();
			$index = 0;
			while ($row = $result->fetch()) {
				$clientId = $row['client_id'];
				$refreshToken = $row['token'];

				// Insert expired token so that it can be rotated on the next refresh
				$accessToken = $this->random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
				$authToken = $this->tokenProvider->generateToken(
					$accessToken,
					$row['user_id'],
					$row['user_id'],
					null,
					"oc_migrated_client{$clientId}_t{$now}_i$index",
					IToken::PERMANENT_TOKEN,
					IToken::DO_NOT_REMEMBER,
				);
				$authToken->setExpires($now - 3600);
				$this->tokenProvider->updateToken($authToken);

				$accessTokenEntity = new AccessToken();
				$accessTokenEntity->setTokenId($authToken->getId());
				$accessTokenEntity->setClientId($clientId);
				$accessTokenEntity->setHashedCode(hash('sha512', $refreshToken));
				$accessTokenEntity->setEncryptedToken($this->crypto->encrypt($accessToken, $refreshToken));
				$accessTokenEntity->setCodeCreatedAt($now);
				$accessTokenEntity->setTokenCount(1);
				$this->accessTokenMapper->insert($accessTokenEntity);

				$index++;
			}
			$result->closeCursor();

			$schema->dropTable('oauth2_refresh_tokens');
			$schema->performDropTableCalls();
		}
	}
}
