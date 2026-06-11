<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db;

use OC\DB\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

class DbSize extends Command {

	public function __construct(
		private readonly Connection $connection,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('db:size')
			->setDescription('Show disk usage of all Nextcloud database tables, ordered by size')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$platform = $this->connection->getDatabasePlatform();
		$asJson   = $input->getOption('json');

		if ($platform instanceof MySQLPlatform) {
			$sql = "
				SELECT table_name AS `table`,
					   ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_mb,
					   ROUND(data_length / 1024 / 1024, 2) AS data_mb,
					   ROUND(index_length / 1024 / 1024, 2) AS index_mb,
					   table_rows AS rows,
					   IF(table_rows > 0, ROUND((data_length + index_length) / table_rows, 0), 0) AS avg_row_bytes
				FROM information_schema.tables
				WHERE table_schema = DATABASE()
				ORDER BY (data_length + index_length) DESC
			";
			$headers = ['Table', 'Total (MB)', 'Data (MB)', 'Index (MB)', 'Rows', 'Avg Row (bytes)'];
			$cols	= ['table', 'total_mb', 'data_mb', 'index_mb', 'rows', 'avg_row_bytes'];
		} elseif ($platform instanceof PostgreSQLPlatform) {
			$sql = "
				SELECT relname AS table,
					   ROUND(pg_total_relation_size(c.oid) / 1024.0 / 1024.0, 2) AS total_mb,
					   ROUND(pg_relation_size(c.oid) / 1024.0 / 1024.0, 2) AS data_mb,
					   ROUND(pg_indexes_size(c.oid) / 1024.0 / 1024.0, 2) AS index_mb,
					   c.reltuples::bigint AS rows,
					   CASE WHEN c.reltuples > 0 THEN ROUND(pg_total_relation_size(c.oid) / c.reltuples) ELSE 0 END AS avg_row_bytes
				FROM pg_class c
				JOIN pg_namespace n ON n.oid = c.relnamespace
				WHERE c.relkind = 'r' AND n.nspname = 'public'
				ORDER BY pg_total_relation_size(c.oid) DESC
			";
			$headers = ['Table', 'Total (MB)', 'Data (MB)', 'Index (MB)', 'Rows (est.)', 'Avg Row (bytes)'];
			$cols	= ['table', 'total_mb', 'data_mb', 'index_mb', 'rows', 'avg_row_bytes'];
		} else {
			$output->writeln('<comment>db:size is not supported for SQLite and Oracle.</comment>');
			return Command::SUCCESS;
		}

		$rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

		if ($asJson) {
			$output->writeln(json_encode($rows, JSON_PRETTY_PRINT));
			return Command::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders($headers);

		foreach ($rows as $row) {
			$table->addRow(array_map(fn($col) => $row[$col], $cols));
		}

		$table->render();

		$totalMB = array_sum(array_column($rows, 'total_mb'));
		$output->writeln(sprintf('<info>Total database size: %.2f MB</info>', $totalMB));

		return Command::SUCCESS;
	}
}
