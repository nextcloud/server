<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\DB\Connection;
use OC\DB\MissingPrimaryKeyInformation;
use OC\DB\SchemaWrapper;
use OCP\DB\Events\AddMissingPrimaryKeyEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DatabaseHasMissingPrimaryKeys implements ISetupCheck {
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
		return $this->l10n->t('Database missing primary keys');
	}

	private function getMissingPrimaryKeys(): array {
		$primaryKeyInfo = new MissingPrimaryKeyInformation();
		// Dispatch event so apps can also hint for pending primary key updates if needed
		$event = new AddMissingPrimaryKeyEvent();
		$this->dispatcher->dispatchTyped($event);
		$missingPrimaryKeys = $event->getMissingPrimaryKeys();

		if (!empty($missingPrimaryKeys)) {
			$schema = new SchemaWrapper($this->connection);
			foreach ($missingPrimaryKeys as $missingPrimaryKey) {
				if ($schema->hasTable($missingPrimaryKey['tableName'])) {
					$table = $schema->getTable($missingPrimaryKey['tableName']);
					if ($table->getPrimaryKey() === null) {
						$primaryKeyInfo->addHintForMissingPrimaryKey($missingPrimaryKey['tableName']);
					}
				}
			}
		}

		return $primaryKeyInfo->getListOfMissingPrimaryKeys();
	}

	public function run(): SetupResult {
		$missingPrimaryKeys = $this->getMissingPrimaryKeys();
		if (empty($missingPrimaryKeys)) {
			return SetupResult::success('None');
		} else {
			$list = '';
			foreach ($missingPrimaryKeys as $missingPrimaryKey) {
				$list .= "\n" . $this->l10n->t('Missing primary key on table "%s".', [$missingPrimaryKey['tableName']]);
			}
			return SetupResult::warning(
				$this->l10n->t('The database is missing some primary keys. Due to the fact that adding primary keys on big tables could take some time they were not added automatically. By running "occ db:add-missing-primary-keys" those missing primary keys could be added manually while the instance keeps running.') . $list
			);
		}
	}
}
