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

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IDBConnection;
use OC\DB\SchemaWrapper;

class MigrateOauthTables implements IRepairStep {

	/** @var IDBConnection */
	protected $db;

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
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

		$output->info("Update the oauth2_access_tokens table schema.");
		$schema = new SchemaWrapper($this->db);
		$table = $schema->getTable('oauth2_access_tokens');
		$table->addColumn('hashed_code', 'string', [
			'notnull' => true,
			'length' => 128,
		]);
		$table->addColumn('encrypted_token', 'string', [
			'notnull' => true,
			'length' => 786,
		]);
		$table->addUniqueIndex(['hashed_code'], 'oauth2_access_hash_idx');
		$table->addIndex(['client_id'], 'oauth2_access_client_id_idx');


		$output->info("Update the oauth2_clients table schema.");
		$schema = new SchemaWrapper($this->db);
		$table = $schema->getTable('oauth2_clients');
		$table->getColumn('name')->setLength(64);
		$table->dropColumn('allow_subdomains');

		if (!$schema->getTable('oauth2_clients')->hasColumn('client_identifier')) {
			$table->addColumn('client_identifier', 'string', [
				'notnull' => true,
				'length' => 64,
				'default' => ''
			]);
			$table->addIndex(['client_identifier'], 'oauth2_client_id_idx');
		}

		$this->db->migrateToSchema($schema->getWrappedSchema());


		if ($schema->getTable('oauth2_clients')->hasColumn('identifier')) {
			$output->info("Move identifier column's data to the new client_identifier column.");
			// 1. Fetch all [id, identifier] couple.
			$qb = $this->db->getQueryBuilder();
			$qb->select('id', 'identifier')->from('oauth2_clients');
			$result = $qb->execute();
			$identifiers = $result->fetchAll();
			$result->closeCursor();

			// 2. Insert them into the client_identifier column.
			foreach ($identifiers as ["id" => $id, "identifier" => $clientIdentifier]) {
				$this->db->getQueryBuilder()
					->update('oauth2_clients')
					->set('client_identifier', $clientIdentifier)
					->where($qb->expr()->eq('id', $id))
					->execute();
			}

			$output->info("Drop the identifier column.");
			$schema = new SchemaWrapper($this->db);
			$table = $schema->getTable('oauth2_clients');
			$table->dropColumn('identifier');
			$this->db->migrateToSchema($schema->getWrappedSchema());
		}
	}
}
