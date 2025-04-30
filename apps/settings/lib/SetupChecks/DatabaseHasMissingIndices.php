<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
					$list .= ', ';
				}
			}
			return SetupResult::warning(
				$this->l10n->t('Detected some missing optional indices. Occasionally new indices are added (by Nextcloud or installed applications) to improve database performance. Adding indices can sometimes take awhile and temporarily hurt performance so this is not done automatically during upgrades. Once the indices are added, queries to those tables should be faster. Use the command `occ db:add-missing-indices` to add them.') . "\n" . $list,
				$this->urlGenerator->linkToDocs('admin-long-running-migration-steps')
			);
		}
	}
}
