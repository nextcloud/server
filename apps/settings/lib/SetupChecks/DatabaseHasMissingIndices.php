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
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DatabaseHasMissingIndices implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private Connection $connection,
		private IEventDispatcher $dispatcher,
		private IURLGenerator $urlGenerator,
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
		$indicesToReplace = $event->getIndicesToReplace();

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

		if (!empty($indicesToReplace)) {
			$schema = new SchemaWrapper($this->connection);
			foreach ($indicesToReplace as $indexToReplace) {
				if ($schema->hasTable($indexToReplace['tableName'])) {
					$table = $schema->getTable($indexToReplace['tableName']);
					if (!$table->hasIndex($indexToReplace['newIndexName'])) {
						$indexInfo->addHintForMissingIndex($indexToReplace['tableName'], $indexToReplace['newIndexName']);
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
			$processed = 0;
			$list = $this->l10n->t('Missing indices:');
			foreach ($missingIndices as $missingIndex) {
				$processed++;
				$list .= "\n " . $this->l10n->t('"%s" in table "%s"', [$missingIndex['indexName'], $missingIndex['tableName']]);
				if (count($missingIndices) > $processed) {
					$list .= ", ";
				}
			}
			return SetupResult::warning(
				$this->l10n->t('Detected some missing optional indices. Occasionally new indices are added (by Nextcloud or installed applications) to improve database performance. Adding indices can sometimes take awhile and temporarily hurt performance so this is not done automatically during upgrades. Once the indices are added, queries to those tables should be faster. Use the command `occ db:add-missing-indices` to add them. ') . $list . '.',
				$this->urlGenerator->linkToDocs('admin-long-running-migration-steps')
			);
		}
	}
}
