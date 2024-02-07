<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Simon Lindner <szaimen@e.mail.de>
 *
 * @author Simon Lindner <szaimen@e.mail.de>
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

use Doctrine\DBAL\Types\BigIntType;
use OC\Core\Command\Db\ConvertFilecacheBigInt;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DatabasePendingBigIntConversions implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private Connection $db,
		private IEventDispatcher $dispatcher,
		private IDBConnection $connection,
	) {
	}

	public function getCategory(): string {
		return 'database';
	}

	public function getName(): string {
		return $this->l10n->t('Database has non unique index names');
	}

	protected function getUniqueNamesConstraints(Schema $targetSchema): void {
		$constraintNames = [];

		$sequences = $targetSchema->getSequences();

		foreach ($targetSchema->getTables() as $table) {
			foreach ($table->getIndexes() as $thing) {
				$indexName = strtolower($thing->getName());
				if ($indexName === 'primary' || $thing->isPrimary()) {
					continue;
				}

				if (isset($constraintNames[$thing->getName()])) {
					throw new \InvalidArgumentException('Index name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$thing->getName()] = $table->getName();
			}

			foreach ($table->getForeignKeys() as $thing) {
				if (isset($constraintNames[$thing->getName()])) {
					throw new \InvalidArgumentException('Foreign key name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$thing->getName()] = $table->getName();
			}

			$primaryKey = $table->getPrimaryKey();
			if ($primaryKey instanceof Index) {
				$indexName = strtolower($primaryKey->getName());
				if ($indexName === 'primary') {
					continue;
				}

				if (isset($constraintNames[$indexName])) {
					throw new \InvalidArgumentException('Primary index name "' . $indexName . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$indexName] = $table->getName();
			}
		}

		foreach ($sequences as $sequence) {
			if (isset($constraintNames[$sequence->getName()])) {
				throw new \InvalidArgumentException('Sequence name "' . $sequence->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
			}
			$constraintNames[$sequence->getName()] = 'sequence';
		}
	}

	public function run(): SetupResult {
		$pendingColumns = $this->getUniqueNamesConstraints();
		if (empty($pendingColumns)) {
			return SetupResult::success('None');
		} else {
			$list = '';
			foreach ($pendingColumns as $pendingColumn) {
				$list .= "\n$pendingColumn";
			}
			$list .= "\n";
			return SetupResult::info(
				$this->l10n->t('Some indexes in the database are non unique.').$list
			);
		}
	}
}
