<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Command;

use OCA\DAV\Connector\Sabre\Principal;
use OCP\Cache\CappedMemoryCache;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveInvalidShares - removes shared calendars and addressbook which
 * have no matching principal. Happened because of a bug in the calendar app.
 */
class RemoveInvalidShares extends Command {
	private CappedMemoryCache $cache;

	public function __construct(
		private IDBConnection $connection,
		private Principal $principalBackend,
	) {
		parent::__construct();

		$this->cache = new CappedMemoryCache();
	}

	protected function configure(): void {
		$this
			->setName('dav:remove-invalid-shares')
			->setDescription('Remove invalid dav shares')
			->addOption('dry-run', 'Check but don\'t delete');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');
		$recipientQb = $this->connection->getQueryBuilder();
		$result = $recipientQb->selectDistinct('principaluri')
			->from('dav_shares')
			->executeQuery();
		while ($row = $result->fetch()) {
			$principaluri = $row['principaluri'];
			if (!isset($this->cache[$principaluri])) {
				$exists = $this->cache[$principaluri] = $this->principalBackend->getPrincipalByPath($principaluri) !== null;
			} else {
				$exists = $this->cache[$principaluri];
			}
			if (!$exists) {
				$output->writeln("Principal URI $principaluri is an invalid share recipient", OutputInterface::VERBOSITY_VERBOSE);
				$this->cache[$principaluri] = false;
				if (!$dryRun) {
					$this->deleteSharesForPrincipal($principaluri);
				}
			}
		}
		$result->closeCursor();

		$sharerQb = $this->connection->getQueryBuilder();
		$result = $sharerQb->select('s.id', 'a.principaluri', 'c.principaluri')
			->selectAlias('a.principaluri', 'a_principaluri')
			->selectAlias('c.principaluri', 'c_principaluri')
			->from('dav_shares', 's')
			->leftJoin('s', 'addressbooks', 'a', $sharerQb->expr()->andX(
				$sharerQb->expr()->eq('s.type', $sharerQb->createNamedParameter('addressbook')),
				$sharerQb->expr()->eq('s.resourceid', 'a.id'),
			))
			->leftJoin('s', 'calendars', 'c', $sharerQb->expr()->andX(
				$sharerQb->expr()->eq('s.type', $sharerQb->createNamedParameter('calendar')),
				$sharerQb->expr()->eq('s.resourceid', 'c.id'),
			))
			->executeQuery();
		while ($row = $result->fetch()) {
			$principaluri = $row['a_principaluri'] ?? $row['c_principaluri'];
			if ($principaluri === null) {
				// Different resource type, ignore
				continue;
			}
			if (!isset($this->cache[$principaluri])) {
				$exists = $this->cache[$principaluri] = $this->principalBackend->getPrincipalByPath($principaluri) !== null;
			} else {
				$exists = $this->cache[$principaluri];
			}
			if (!$exists) {
				$output->writeln("Principal URI $principaluri is an invalid sharer", OutputInterface::VERBOSITY_VERBOSE);
				if (!$dryRun) {
					$this->deleteShareById($row['id']);
				}
			}
		}
		$result->closeCursor();

		return self::SUCCESS;
	}

	private function deleteSharesForPrincipal(string $principaluri): void {
		$delete = $this->connection->getQueryBuilder();
		$delete->delete('dav_shares')
			->where($delete->expr()->eq('principaluri', $delete->createNamedParameter($principaluri)));
		$delete->executeStatement();
	}

	private function deleteShareById(int $id): void {
		$delete = $this->connection->getQueryBuilder();
		$delete->delete('dav_shares')
			->where($delete->expr()->eq('id', $delete->createNamedParameter($id)));
		$delete->executeStatement();
	}
}
