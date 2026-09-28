<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\TransactionIsolationLevel;
use OC\DB\Connection;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class TransactionIsolation implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IDBConnection $connection,
		private Connection $db,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Database transaction isolation level');
	}

	public function getCategory(): string {
		return 'database';
	}

	public function run(): SetupResult {
		try {
			if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE) {
				return SetupResult::success();
			}

			if ($this->db->getTransactionIsolation() === TransactionIsolationLevel::READ_COMMITTED) {
				return SetupResult::success('Read committed');
			} else {
				return SetupResult::error(
					$this->l10n->t('Your database does not run with "READ COMMITTED" transaction isolation level. This can cause problems when multiple actions are executed in parallel.'),
					$this->urlGenerator->linkToDocs('admin-db-transaction')
				);
			}
		} catch (Exception $e) {
			return SetupResult::warning(
				$this->l10n->t('Was not able to get transaction isolation level: %s', $e->getMessage())
			);
		}
	}
}
