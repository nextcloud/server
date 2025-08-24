<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Command;

use OCA\DAV\Connector\Sabre\Principal;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveInvalidShares - removes shared calendars and addressbook which
 * have no matching principal. Happened because of a bug in the calendar app.
 */
class RemoveInvalidShares extends Command {
	public function __construct(
		private IDBConnection $connection,
		private Principal $principalBackend,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:remove-invalid-shares')
			->setDescription('Remove invalid dav shares');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$query = $this->connection->getQueryBuilder();
		$result = $query->selectDistinct('principaluri')
			->from('dav_shares')
			->executeQuery();

		while ($row = $result->fetch()) {
			$principaluri = $row['principaluri'];
			$p = $this->principalBackend->getPrincipalByPath($principaluri);
			if ($p === null) {
				$this->deleteSharesForPrincipal($principaluri);
			}
		}

		$result->closeCursor();
		return self::SUCCESS;
	}

	/**
	 * @param string $principaluri
	 */
	private function deleteSharesForPrincipal($principaluri): void {
		$delete = $this->connection->getQueryBuilder();
		$delete->delete('dav_shares')
			->where($delete->expr()->eq('principaluri', $delete->createNamedParameter($principaluri)));
		$delete->executeStatement();
	}
}
