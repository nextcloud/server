<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use OC\DB\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbLocks extends Command {

	public function __construct(
		private readonly Connection $connection,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('db:locks')
			->setDescription('Show active database locks, deadlocks, and long-running transactions')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$platform = $this->connection->getDatabasePlatform();
		$asJson = $input->getOption('json');

		if ($platform instanceof MySQLPlatform) {
			$sql = 'SELECT r.trx_id AS waiting_trx_id,
                       r.trx_mysql_thread_id AS waiting_thread,
                       r.trx_query AS waiting_query,
                       b.trx_id AS blocking_trx_id,
                       b.trx_mysql_thread_id AS blocking_thread,
                       b.trx_query AS blocking_query
                FROM information_schema.innodb_lock_waits w
                JOIN information_schema.innodb_trx b ON b.trx_id = w.blocking_trx_id
                JOIN information_schema.innodb_trx r ON r.trx_id = w.requesting_trx_id';
			$headers = ['Waiting TRX', 'Waiting Thread', 'Waiting Query', 'Blocking TRX', 'Blocking Thread', 'Blocking Query'];
			$cols = ['waiting_trx_id', 'waiting_thread', 'waiting_query', 'blocking_trx_id', 'blocking_thread', 'blocking_query'];
		} elseif ($platform instanceof PostgreSQLPlatform) {
			$sql = 'SELECT blocked_locks.pid AS blocked_pid,
                           blocked_activity.usename AS blocked_user,
                           blocking_locks.pid AS blocking_pid,
                           blocking_activity.usename AS blocking_user,
                           blocked_activity.query AS blocked_query,
                           blocking_activity.query AS blocking_query,
                           now() - blocked_activity.query_start AS blocked_duration
                FROM pg_catalog.pg_locks blocked_locks
                JOIN pg_catalog.pg_stat_activity blocked_activity ON blocked_activity.pid = blocked_locks.pid
                JOIN pg_catalog.pg_locks blocking_locks
                    ON blocking_locks.locktype = blocked_locks.locktype
                    AND blocking_locks.relation IS NOT DISTINCT FROM blocked_locks.relation
                    AND blocking_locks.pid != blocked_locks.pid
                JOIN pg_catalog.pg_stat_activity blocking_activity ON blocking_activity.pid = blocking_locks.pid
                WHERE NOT blocked_locks.granted';
			$headers = ['Blocked PID', 'Blocked User', 'Blocking PID', 'Blocking User', 'Blocked Query', 'Duration'];
			$cols = ['blocked_pid', 'blocked_user', 'blocking_pid', 'blocking_user', 'blocked_query', 'blocked_duration'];
		} else {
			$output->writeln('<comment>db:locks is not supported for SQLite and Oracle (SQLite uses file-level locking).</comment>');
			return Command::SUCCESS;
		}

		$rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

		if (empty($rows)) {
			$output->writeln('<info>No active locks or blocking transactions detected.</info>');
			return Command::SUCCESS;
		}

		if ($asJson) {
			$output->writeln(json_encode($rows, JSON_PRETTY_PRINT));
			return Command::SUCCESS;
		}

		$output->writeln(sprintf('<error>Found %d blocking transaction(s)!</error>', count($rows)));
		$output->writeln('');

		$table = new Table($output);
		$table->setHeaders($headers);

		foreach ($rows as $row) {
			$table->addRow(array_map(fn ($col) => $row[$col] ?? '—', $cols));
		}

		$table->render();
		return Command::SUCCESS;
	}
}
