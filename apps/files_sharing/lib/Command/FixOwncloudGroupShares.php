<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Command;

use OC\Core\Command\Base;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Share\IShare;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fixes USERGROUP subshares that were created without `accepted = STATUS_ACCEPTED`
 * by the rename bug affecting ownCloud-migrated group shares.
 *
 * When an ownCloud-migrated group share (which has no per-user USERGROUP subshare)
 * is renamed for the first time, a new USERGROUP row is inserted without an
 * `accepted` value. The column defaults to 0 (STATUS_PENDING), causing
 * MountProvider to skip the share — the file disappears for the recipient.
 *
 * A USERGROUP subshare with permissions = 0 was explicitly declined by the user
 * and must not be touched.
 */
class FixOwncloudGroupShares extends Base {
	public function __construct(
		private IDBConnection $connection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('sharing:fix-owncloud-group-shares')
			->setDescription('Fix group share subshares left with accepted = STATUS_PENDING after renaming on an ownCloud-migrated instance')
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'Show how many shares would be fixed without making any changes',
			);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		$qb = $this->connection->getQueryBuilder();
		$count = (int)$qb->select($qb->func()->count('id'))
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter(IShare::STATUS_PENDING, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->neq('permissions', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->executeQuery()
			->fetchOne();

		if ($count === 0) {
			$output->writeln('No affected group share subshares found.');
			return self::SUCCESS;
		}

		if ($dryRun) {
			$output->writeln("Would fix <info>$count</info> group share subshare(s) (dry-run, no changes made).");
			return self::SUCCESS;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->update('share')
			->set('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter(IShare::STATUS_PENDING, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->neq('permissions', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		$output->writeln("Fixed <info>$count</info> group share subshare(s).");
		return self::SUCCESS;
	}
}
