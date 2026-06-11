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

class DbIndexUsage extends Command {

	public function __construct(
		private readonly Connection $connection,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('db:index-usage')
			->setDescription('Report unused database indexes (indexes that slow writes but are never read)')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format')
			->addOption('all', null, InputOption::VALUE_NONE, 'Show all indexes, not just unused ones');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$platform = $this->connection->getDatabasePlatform();
		$asJson = $input->getOption('json');
		$showAll = $input->getOption('all');

		if ($platform instanceof MySQLPlatform) {
			// Requires performance_schema to be enabled (default in MySQL 5.6+/MariaDB 10.0+)
			$unused_filter = $showAll ? '' : "WHERE s.count_read = 0 AND s.index_name IS NOT NULL AND s.index_name != 'PRIMARY'";
			$sql = "SELECT s.object_name AS `table`,
                           s.index_name AS `index`,
                           s.count_read AS reads,
                           s.count_write AS writes
                FROM performance_schema.table_io_waits_summary_by_index_usage s
                {$unused_filter}
                ORDER BY s.object_name, s.index_name";
		} elseif ($platform instanceof PostgreSQLPlatform) {
			$unused_filter = $showAll ? '' : 'AND idx_scan = 0';
			$sql = "SELECT relname AS table,
                           indexrelname AS index,
                           idx_scan AS reads,
                           idx_tup_read AS tuples_read,
                           idx_tup_fetch AS tuples_fetched
                FROM pg_stat_user_indexes
                JOIN pg_index USING (indexrelid)
                WHERE indisunique IS FALSE
                {$unused_filter}
                ORDER BY relname, indexrelname";
		} else {
			$output->writeln('<comment>db:index-usage is not supported for SQLite and Oracle.</comment>');
			return Command::SUCCESS;
		}

		try {
			$rows = $this->connection->executeQuery($sql)->fetchAllAssociative();
		} catch (\Doctrine\DBAL\Exception $e) {
			$output->writeln('<error>Failed to query index usage statistics. The required performance tables may not be available on this database version.</error>');
			$output->writeln('<comment>Details: ' . $e->getMessage() . '</comment>');
			return Command::FAILURE;
		}

		if (empty($rows)) {
			$output->writeln('<info>No unused indexes found. Great!</info>');
			return Command::SUCCESS;
		}

		if ($asJson) {
			$output->writeln(json_encode($rows, JSON_PRETTY_PRINT));
			return Command::SUCCESS;
		}

		$table = new Table($output);

		if ($platform instanceof MySQLPlatform) {
			$table->setHeaders(['Table', 'Index', 'Reads', 'Writes']);
			foreach ($rows as $row) {
				$table->addRow([$row['table'], $row['index'], $row['reads'], $row['writes']]);
			}
		} else {
			$table->setHeaders(['Table', 'Index', 'Scans', 'Tuples Read', 'Tuples Fetched']);
			foreach ($rows as $row) {
				$table->addRow([$row['table'], $row['index'], $row['reads'], $row['tuples_read'], $row['tuples_fetched']]);
			}
		}

		$table->render();

		if (!$showAll) {
			$output->writeln(sprintf(
				'<comment>Found %d unused index(es). If those were not created by Nextcloud, consider removing them to improve write performance.</comment>',
				count($rows)
			));
		}

		return Command::SUCCESS;
	}
}
