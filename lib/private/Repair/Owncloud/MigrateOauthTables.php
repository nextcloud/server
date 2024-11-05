<?php
/**
 * @copyright 2021 Louis Chemineau <louis@chmn.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair\Owncloud;

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigrateOauthTables implements IRepairStep {
	/** @var Connection */
	protected $db;

	/**
	 * @param Connection $db
	 */
	public function __construct(Connection $db) {
		$this->db = $db;
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
			$output->info("oauth2_clients table does not exist.");
			return;
		}

		$output->info('Update the oauth2_access_tokens table schema.');
		$table = $schema->getTable('oauth2_access_tokens');
		if (!$table->hasColumn('hashed_code')) {
			$table->addColumn('hashed_code', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
		}
		if (!$table->hasColumn('encrypted_token')) {
			$table->addColumn('encrypted_token', 'string', [
				'notnull' => true,
				'length' => 786,
			]);
		}
		if (!$table->hasIndex('oauth2_access_hash_idx')) {
			$table->addUniqueIndex(['hashed_code'], 'oauth2_access_hash_idx');
		}
		if (!$table->hasIndex('oauth2_access_client_id_idx')) {
			$table->addIndex(['client_id'], 'oauth2_access_client_id_idx');
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

		$this->db->migrateToSchema($schema->getWrappedSchema());

		// Regenerate schema after migrating to it
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
			foreach ($identifiers as ["id" => $id, "identifier" => $clientIdentifier]) {
				$insertQuery = $this->db->getQueryBuilder();
				$insertQuery->update('oauth2_clients')
					->set('client_identifier', $insertQuery->createNamedParameter($clientIdentifier, IQueryBuilder::PARAM_STR))
					->where($insertQuery->expr()->eq('id', $insertQuery->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
					->executeStatement();
			}

			$output->info('Drop the identifier column.');
			$table = $schema->getTable('oauth2_clients');
			$table->dropColumn('identifier');
			$this->db->migrateToSchema($schema->getWrappedSchema());
		}

		$output->info('Delete clients (and their related access tokens) with the redirect_uri starting with oc:// or ending with *');
		// delete the access tokens
		$qbDeleteAccessTokens = $this->db->getQueryBuilder();

		$qbSelectClientId = $this->db->getQueryBuilder();
		$qbSelectClientId->select('id')
			->from('oauth2_clients')
			->where(
				$qbSelectClientId->expr()->iLike('redirect_uri', $qbDeleteAccessTokens->createNamedParameter('oc://%', IQueryBuilder::PARAM_STR))
			)
			->orWhere(
				$qbSelectClientId->expr()->iLike('redirect_uri', $qbDeleteAccessTokens->createNamedParameter('%*', IQueryBuilder::PARAM_STR))
			);

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
			)
			->orWhere(
				$qbDeleteClients->expr()->iLike('redirect_uri', $qbDeleteClients->createNamedParameter('%*', IQueryBuilder::PARAM_STR))
			);
		$qbDeleteClients->executeStatement();
	}
}
