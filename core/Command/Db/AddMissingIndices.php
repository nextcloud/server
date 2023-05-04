<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Mario Danic <mario@lovelyhq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OC\Core\Command\Db;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class AddMissingIndices
 *
 * if you added any new indices to the database, this is the right place to add
 * your update routine for existing instances
 *
 * @package OC\Core\Command\Db
 */
class AddMissingIndices extends Command {
	private Connection $connection;
	private EventDispatcherInterface $dispatcher;

	public function __construct(Connection $connection, EventDispatcherInterface $dispatcher) {
		parent::__construct();

		$this->connection = $connection;
		$this->dispatcher = $dispatcher;
	}

	protected function configure() {
		$this
			->setName('db:add-missing-indices')
			->setDescription('Add missing indices to the database tables')
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Output the SQL queries instead of running them.");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->addCoreIndexes($output, $input->getOption('dry-run'));

		// Dispatch event so apps can also update indexes if needed
		$event = new GenericEvent($output);
		$this->dispatcher->dispatch(IDBConnection::ADD_MISSING_INDEXES_EVENT, $event);
		return 0;
	}

	/**
	 * add missing indices to the share table
	 *
	 * @param OutputInterface $output
	 * @param bool $dryRun If true, will return the sql queries instead of running them.
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	private function addCoreIndexes(OutputInterface $output, bool $dryRun): void {
		$output->writeln('<info>Check indices of the share table.</info>');

		$schema = new SchemaWrapper($this->connection);
		$updated = false;

		if ($schema->hasTable('share')) {
			$table = $schema->getTable('share');
			if (!$table->hasIndex('share_with_index')) {
				$output->writeln('<info>Adding additional share_with index to the share table, this can take some time...</info>');
				$table->addIndex(['share_with'], 'share_with_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}

			if (!$table->hasIndex('parent_index')) {
				$output->writeln('<info>Adding additional parent index to the share table, this can take some time...</info>');
				$table->addIndex(['parent'], 'parent_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}

			if (!$table->hasIndex('owner_index')) {
				$output->writeln('<info>Adding additional owner index to the share table, this can take some time...</info>');
				$table->addIndex(['uid_owner'], 'owner_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}

			if (!$table->hasIndex('initiator_index')) {
				$output->writeln('<info>Adding additional initiator index to the share table, this can take some time...</info>');
				$table->addIndex(['uid_initiator'], 'initiator_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Share table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the filecache table.</info>');
		if ($schema->hasTable('filecache')) {
			$table = $schema->getTable('filecache');
			if (!$table->hasIndex('fs_mtime')) {
				$output->writeln('<info>Adding additional mtime index to the filecache table, this can take some time...</info>');
				$table->addIndex(['mtime'], 'fs_mtime');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Filecache table updated successfully.</info>');
			}
			if (!$table->hasIndex('fs_size')) {
				$output->writeln('<info>Adding additional size index to the filecache table, this can take some time...</info>');
				$table->addIndex(['size'], 'fs_size');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Filecache table updated successfully.</info>');
			}
			if (!$table->hasIndex('fs_id_storage_size')) {
				$output->writeln('<info>Adding additional size index to the filecache table, this can take some time...</info>');
				$table->addIndex(['fileid', 'storage', 'size'], 'fs_id_storage_size');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Filecache table updated successfully.</info>');
			}
			if (!$table->hasIndex('fs_storage_path_prefix') && !$schema->getDatabasePlatform() instanceof PostgreSQL94Platform) {
				$output->writeln('<info>Adding additional path index to the filecache table, this can take some time...</info>');
				$table->addIndex(['storage', 'path'], 'fs_storage_path_prefix', [], ['lengths' => [null, 64]]);
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Filecache table updated successfully.</info>');
			}
			if (!$table->hasIndex('fs_parent')) {
				$output->writeln('<info>Adding additional parent index to the filecache table, this can take some time...</info>');
				$table->addIndex(['parent'], 'fs_parent');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Filecache table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the twofactor_providers table.</info>');
		if ($schema->hasTable('twofactor_providers')) {
			$table = $schema->getTable('twofactor_providers');
			if (!$table->hasIndex('twofactor_providers_uid')) {
				$output->writeln('<info>Adding additional twofactor_providers_uid index to the twofactor_providers table, this can take some time...</info>');
				$table->addIndex(['uid'], 'twofactor_providers_uid');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>Twofactor_providers table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the login_flow_v2 table.</info>');
		if ($schema->hasTable('login_flow_v2')) {
			$table = $schema->getTable('login_flow_v2');
			if (!$table->hasIndex('poll_token')) {
				$output->writeln('<info>Adding additional indeces to the login_flow_v2 table, this can take some time...</info>');

				foreach ($table->getIndexes() as $index) {
					$columns = $index->getColumns();
					if ($columns === ['poll_token'] ||
						$columns === ['login_token'] ||
						$columns === ['timestamp']) {
						$table->dropIndex($index->getName());
					}
				}

				$table->addUniqueIndex(['poll_token'], 'poll_token');
				$table->addUniqueIndex(['login_token'], 'login_token');
				$table->addIndex(['timestamp'], 'timestamp');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>login_flow_v2 table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the whats_new table.</info>');
		if ($schema->hasTable('whats_new')) {
			$table = $schema->getTable('whats_new');
			if (!$table->hasIndex('version')) {
				$output->writeln('<info>Adding version index to the whats_new table, this can take some time...</info>');

				foreach ($table->getIndexes() as $index) {
					if ($index->getColumns() === ['version']) {
						$table->dropIndex($index->getName());
					}
				}

				$table->addUniqueIndex(['version'], 'version');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>whats_new table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the cards table.</info>');
		$cardsUpdated = false;
		if ($schema->hasTable('cards')) {
			$table = $schema->getTable('cards');

			if ($table->hasIndex('addressbookid_uri_index')) {
				if ($table->hasIndex('cards_abiduri')) {
					$table->dropIndex('addressbookid_uri_index');
				} else {
					$output->writeln('<info>Renaming addressbookid_uri_index index to cards_abiduri in the cards table, this can take some time...</info>');

					foreach ($table->getIndexes() as $index) {
						if ($index->getColumns() === ['addressbookid', 'uri']) {
							$table->renameIndex('addressbookid_uri_index', 'cards_abiduri');
						}
					}
				}

				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$cardsUpdated = true;
			}

			if (!$table->hasIndex('cards_abid')) {
				$output->writeln('<info>Adding cards_abid index to the cards table, this can take some time...</info>');

				foreach ($table->getIndexes() as $index) {
					if ($index->getColumns() === ['addressbookid']) {
						$table->dropIndex($index->getName());
					}
				}

				$table->addIndex(['addressbookid'], 'cards_abid');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$cardsUpdated = true;
			}

			if (!$table->hasIndex('cards_abiduri')) {
				$output->writeln('<info>Adding cards_abiduri index to the cards table, this can take some time...</info>');

				foreach ($table->getIndexes() as $index) {
					if ($index->getColumns() === ['addressbookid', 'uri']) {
						$table->dropIndex($index->getName());
					}
				}

				$table->addIndex(['addressbookid', 'uri'], 'cards_abiduri');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$cardsUpdated = true;
			}

			if ($cardsUpdated) {
				$updated = true;
				$output->writeln('<info>cards table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the cards_properties table.</info>');
		if ($schema->hasTable('cards_properties')) {
			$table = $schema->getTable('cards_properties');
			if (!$table->hasIndex('cards_prop_abid')) {
				$output->writeln('<info>Adding cards_prop_abid index to the cards_properties table, this can take some time...</info>');

				foreach ($table->getIndexes() as $index) {
					if ($index->getColumns() === ['addressbookid']) {
						$table->dropIndex($index->getName());
					}
				}

				$table->addIndex(['addressbookid'], 'cards_prop_abid');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>cards_properties table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the calendarobjects_props table.</info>');
		if ($schema->hasTable('calendarobjects_props')) {
			$table = $schema->getTable('calendarobjects_props');
			if (!$table->hasIndex('calendarobject_calid_index')) {
				$output->writeln('<info>Adding calendarobject_calid_index index to the calendarobjects_props table, this can take some time...</info>');

				$table->addIndex(['calendarid', 'calendartype'], 'calendarobject_calid_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>calendarobjects_props table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the schedulingobjects table.</info>');
		if ($schema->hasTable('schedulingobjects')) {
			$table = $schema->getTable('schedulingobjects');
			if (!$table->hasIndex('schedulobj_principuri_index')) {
				$output->writeln('<info>Adding schedulobj_principuri_index index to the schedulingobjects table, this can take some time...</info>');

				$table->addIndex(['principaluri'], 'schedulobj_principuri_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>schedulingobjects table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the oc_properties table.</info>');
		if ($schema->hasTable('properties')) {
			$table = $schema->getTable('properties');
			$propertiesUpdated = false;

			if (!$table->hasIndex('properties_path_index')) {
				$output->writeln('<info>Adding properties_path_index index to the oc_properties table, this can take some time...</info>');

				$table->addIndex(['userid', 'propertypath'], 'properties_path_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$propertiesUpdated = true;
			}
			if (!$table->hasIndex('properties_pathonly_index')) {
				$output->writeln('<info>Adding properties_pathonly_index index to the oc_properties table, this can take some time...</info>');

				$table->addIndex(['propertypath'], 'properties_pathonly_index');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$propertiesUpdated = true;
			}

			if ($propertiesUpdated) {
				$updated = true;
				$output->writeln('<info>oc_properties table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the oc_jobs table.</info>');
		if ($schema->hasTable('jobs')) {
			$table = $schema->getTable('jobs');
			if (!$table->hasIndex('job_lastcheck_reserved')) {
				$output->writeln('<info>Adding job_lastcheck_reserved index to the oc_jobs table, this can take some time...</info>');

				$table->addIndex(['last_checked', 'reserved_at'], 'job_lastcheck_reserved');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>oc_properties table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the oc_direct_edit table.</info>');
		if ($schema->hasTable('direct_edit')) {
			$table = $schema->getTable('direct_edit');
			if (!$table->hasIndex('direct_edit_timestamp')) {
				$output->writeln('<info>Adding direct_edit_timestamp index to the oc_direct_edit table, this can take some time...</info>');

				$table->addIndex(['timestamp'], 'direct_edit_timestamp');
				$sqlQueries = $this->connection->migrateToSchema($schema->getWrappedSchema(), $dryRun);
				if ($dryRun && $sqlQueries !== null) {
					$output->writeln($sqlQueries);
				}
				$updated = true;
				$output->writeln('<info>oc_direct_edit table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the oc_preferences table.</info>');
		if ($schema->hasTable('preferences')) {
			$table = $schema->getTable('preferences');
			if (!$table->hasIndex('preferences_app_key')) {
				$output->writeln('<info>Adding preferences_app_key index to the oc_preferences table, this can take some time...</info>');

				$table->addIndex(['appid', 'configkey'], 'preferences_app_key');
				$this->connection->migrateToSchema($schema->getWrappedSchema());
				$updated = true;
				$output->writeln('<info>oc_properties table updated successfully.</info>');
			}
		}

		$output->writeln('<info>Check indices of the oc_mounts table.</info>');
		if ($schema->hasTable('mounts')) {
			$table = $schema->getTable('mounts');
			if (!$table->hasIndex('mounts_class_index')) {
				$output->writeln('<info>Adding mounts_class_index index to the oc_mounts table, this can take some time...</info>');

				$table->addIndex(['mount_provider_class'], 'mounts_class_index');
				$this->connection->migrateToSchema($schema->getWrappedSchema());
				$updated = true;
				$output->writeln('<info>oc_mounts table updated successfully.</info>');
			}
			if (!$table->hasIndex('mounts_user_root_path_index')) {
				$output->writeln('<info>Adding mounts_user_root_path_index index to the oc_mounts table, this can take some time...</info>');

				$table->addIndex(['user_id', 'root_id', 'mount_point'], 'mounts_user_root_path_index', [], ['lengths' => [null, null, 128]]);
				$this->connection->migrateToSchema($schema->getWrappedSchema());
				$updated = true;
				$output->writeln('<info>oc_mounts table updated successfully.</info>');
			}
		}

		if (!$updated) {
			$output->writeln('<info>Done.</info>');
		}
	}
}
