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
				$list .= "\n".$this->l10n->t('Missing primary key on table "%s".', [$missingPrimaryKey['tableName']]);
			}
			return SetupResult::warning(
				$this->l10n->t('The database is missing some primary keys. Due to the fact that adding primary keys on big tables could take some time they were not added automatically. By running "occ db:add-missing-primary-keys" those missing primary keys could be added manually while the instance keeps running.').$list
			);
		}
	}
}
