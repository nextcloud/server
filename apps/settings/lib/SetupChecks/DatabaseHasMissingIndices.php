<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
namespace OCA\Settings\SetupChecks;

use OC\DB\Connection;
use OC\DB\MissingIndexInformation;
use OC\DB\SchemaWrapper;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DatabaseHasMissingIndices implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private Connection $connection,
		private IEventDispatcher $dispatcher,
	) {
	}

	public function getCategory(): string {
		return 'database';
	}

	public function getName(): string {
		return $this->l10n->t('Database missing indices');
	}

	private function getMissingIndices(): array {
		$indexInfo = new MissingIndexInformation();
		// Dispatch event so apps can also hint for pending index updates if needed
		$event = new AddMissingIndicesEvent();
		$this->dispatcher->dispatchTyped($event);
		$missingIndices = $event->getMissingIndices();

		if (!empty($missingIndices)) {
			$schema = new SchemaWrapper($this->connection);
			foreach ($missingIndices as $missingIndex) {
				if ($schema->hasTable($missingIndex['tableName'])) {
					$table = $schema->getTable($missingIndex['tableName']);
					if (!$table->hasIndex($missingIndex['indexName'])) {
						$indexInfo->addHintForMissingIndex($missingIndex['tableName'], $missingIndex['indexName']);
					}
				}
			}
		}

		return $indexInfo->getListOfMissingIndices();
	}

	public function run(): SetupResult {
		$missingIndices = $this->getMissingIndices();
		if (empty($missingIndices)) {
			return SetupResult::success('None');
		} else {
			$list = '';
			foreach ($missingIndices as $missingIndex) {
				$list .= "\n".$this->l10n->t('Missing optional index "%s" in table "%s".', [$missingIndex['indexName'], $missingIndex['tableName']]);
			}
			return SetupResult::warning(
				$this->l10n->t('The database is missing some indexes. Due to the fact that adding indexes on big tables could take some time they were not added automatically. By running "occ db:add-missing-indices" those missing indexes could be added manually while the instance keeps running. Once the indexes are added queries to those tables are usually much faster.').$list
			);
		}
	}
}
