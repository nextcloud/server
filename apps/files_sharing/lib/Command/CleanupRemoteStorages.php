<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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
namespace OCA\Files_Sharing\Command;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Federation\ICloudIdManager;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cleanup 'shared::' storage entries that have no matching entries in the
 * shares_external table.
 */
class CleanupRemoteStorages extends Command {

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @var ICloudIdManager
	 */
	private $cloudIdManager;

	public function __construct(IDBConnection $connection, ICloudIdManager $cloudIdManager) {
		$this->connection = $connection;
		$this->cloudIdManager = $cloudIdManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('sharing:cleanup-remote-storages')
			->setDescription('Cleanup shared storage entries that have no matching entry in the shares_external table')
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'only show which storages would be deleted'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$remoteStorages = $this->getRemoteStorages();

		$output->writeln(count($remoteStorages) . ' remote storage(s) need(s) to be checked');

		$remoteShareIds = $this->getRemoteShareIds();

		$output->writeln(count($remoteShareIds) . ' remote share(s) exist');

		foreach ($remoteShareIds as $id => $remoteShareId) {
			if (isset($remoteStorages[$remoteShareId])) {
				if ($input->getOption('dry-run') || $output->isVerbose()) {
					$output->writeln("<info>$remoteShareId belongs to remote share $id</info>");
				}

				unset($remoteStorages[$remoteShareId]);
			} else {
				$output->writeln("<comment>$remoteShareId for share $id has no matching storage, yet</comment>");
			}
		}

		if (empty($remoteStorages)) {
			$output->writeln('<info>no storages deleted</info>');
		} else {
			$dryRun = $input->getOption('dry-run');
			foreach ($remoteStorages as $id => $numericId) {
				if ($dryRun) {
					$output->writeln("<error>$id [$numericId] can be deleted</error>");
					$this->countFiles($numericId, $output);
				} else {
					$this->deleteStorage($id, $numericId, $output);
				}
			}
		}
		return 0;
	}

	public function countFiles($numericId, OutputInterface $output) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->select($queryBuilder->func()->count('fileid'))
			->from('filecache')
			->where($queryBuilder->expr()->eq(
				'storage',
				$queryBuilder->createNamedParameter($numericId, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR)
			);
		$result = $queryBuilder->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();
		$output->writeln("$count files can be deleted for storage $numericId");
	}

	public function deleteStorage($id, $numericId, OutputInterface $output) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->delete('storages')
			->where($queryBuilder->expr()->eq(
				'id',
				$queryBuilder->createNamedParameter($id, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR)
			);
		$output->write("deleting $id [$numericId] ... ");
		$count = $queryBuilder->executeStatement();
		$output->writeln("deleted $count storage");
		$this->deleteFiles($numericId, $output);
	}

	public function deleteFiles($numericId, OutputInterface $output) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->delete('filecache')
			->where($queryBuilder->expr()->eq(
				'storage',
				$queryBuilder->createNamedParameter($numericId, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR)
			);
		$output->write("deleting files for storage $numericId ... ");
		$count = $queryBuilder->executeStatement();
		$output->writeln("deleted $count files");
	}

	public function getRemoteStorages() {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->select(['id', 'numeric_id'])
			->from('storages')
			->where($queryBuilder->expr()->like(
				'id',
				// match all 'shared::' + 32 characters storages
				$queryBuilder->createNamedParameter($this->connection->escapeLikeParameter('shared::') . str_repeat('_', 32)),
				IQueryBuilder::PARAM_STR)
			)
			->andWhere($queryBuilder->expr()->notLike(
				'id',
				// but not the ones starting with a '/', they are for normal shares
				$queryBuilder->createNamedParameter($this->connection->escapeLikeParameter('shared::/') . '%'),
				IQueryBuilder::PARAM_STR)
			)
			->orderBy('numeric_id');
		$result = $queryBuilder->executeQuery();

		$remoteStorages = [];

		while ($row = $result->fetch()) {
			$remoteStorages[$row['id']] = $row['numeric_id'];
		}
		$result->closeCursor();

		return $remoteStorages;
	}

	public function getRemoteShareIds() {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->select(['id', 'share_token', 'owner', 'remote'])
			->from('share_external');
		$result = $queryBuilder->executeQuery();

		$remoteShareIds = [];

		while ($row = $result->fetch()) {
			$cloudId = $this->cloudIdManager->getCloudId($row['owner'], $row['remote']);
			$remote = $cloudId->getRemote();

			$remoteShareIds[$row['id']] = 'shared::' . md5($row['share_token'] . '@' . $remote);
		}
		$result->closeCursor();

		return $remoteShareIds;
	}
}
