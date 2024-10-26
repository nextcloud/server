<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\DB\Connection;
use OC\DB\MissingColumnInformation;
use OC\DB\SchemaWrapper;
use OCP\DB\Events\AddMissingColumnsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DatabaseHasMissingColumns implements ISetupCheck {
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
		return $this->l10n->t('Database missing columns');
	}

	private function getMissingColumns(): array {
		$columnInfo = new MissingColumnInformation();
		// Dispatch event so apps can also hint for pending column updates if needed
		$event = new AddMissingColumnsEvent();
		$this->dispatcher->dispatchTyped($event);
		$missingColumns = $event->getMissingColumns();

		if (!empty($missingColumns)) {
			$schema = new SchemaWrapper($this->connection);
			foreach ($missingColumns as $missingColumn) {
				if ($schema->hasTable($missingColumn['tableName'])) {
					$table = $schema->getTable($missingColumn['tableName']);
					if (!$table->hasColumn($missingColumn['columnName'])) {
						$columnInfo->addHintForMissingColumn($missingColumn['tableName'], $missingColumn['columnName']);
					}
				}
			}
		}

		return $columnInfo->getListOfMissingColumns();
	}

	public function run(): SetupResult {
		$missingColumns = $this->getMissingColumns();
		if (empty($missingColumns)) {
			return SetupResult::success('None');
		} else {
			$list = '';
			foreach ($missingColumns as $missingColumn) {
				$list .= "\n" . $this->l10n->t('Missing optional column "%s" in table "%s".', [$missingColumn['columnName'], $missingColumn['tableName']]);
			}
			return SetupResult::warning(
				$this->l10n->t('The database is missing some optional columns. Due to the fact that adding columns on big tables could take some time they were not added automatically when they can be optional. By running "occ db:add-missing-columns" those missing columns could be added manually while the instance keeps running. Once the columns are added some features might improve responsiveness or usability.') . $list
			);
		}
	}
}
