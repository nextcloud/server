<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\DB\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbInfo extends Command {

	public function __construct(
		private readonly Connection $connection,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('db:info')
			->setDescription('Show database server information and configuration health check')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$platform = $this->connection->getDatabasePlatform();
		$asJson = $input->getOption('json');

		if ($platform instanceof MySQLPlatform) {
			$rows = $this->getMySQLInfo();
		} elseif ($platform instanceof PostgreSQLPlatform) {
			$rows = $this->getPostgreSQLInfo();
		} elseif ($platform instanceof SqlitePlatform) {
			$rows = $this->getSQLiteInfo();
		} else {
			$output->writeln('<error>Unsupported database platform.</error>');
			return Command::FAILURE;
		}

		if ($asJson) {
			$output->writeln(json_encode($rows, JSON_PRETTY_PRINT));
			return Command::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders(['Setting', 'Value', 'Recommended', 'Status']);

		foreach ($rows as $row) {
			$status = isset($row['recommended'])
				? ($row['ok'] ? '<info>OK</info>' : '<comment>CHECK</comment>')
				: '';
			$table->addRow([
				$row['setting'],
				$row['value'],
				$row['recommended'] ?? '—',
				$status,
			]);
		}

		$table->render();
		return Command::SUCCESS;
	}

	private function getMySQLInfo(): array {
		$result = $this->connection->executeQuery(
			'SELECT VERSION() AS version, @@innodb_buffer_pool_size AS buffer_pool,
					@@max_connections AS max_conn, @@character_set_database AS charset,
					@@transaction_isolation AS tx_isolation'
		);
		$info = $result->fetchAssociative();

		$bufferPoolGB = round(($info['buffer_pool'] / 1024 / 1024 / 1024), 2);

		return [
			['setting' => 'Engine',                 'value' => 'MySQL/MariaDB'],
			['setting' => 'Version',                'value' => $info['version']],
			['setting' => 'Character Set',          'value' => $info['charset'],       'recommended' => 'utf8mb4', 'ok' => str_contains($info['charset'], 'utf8mb4')],
			['setting' => 'Max Connections',        'value' => $info['max_conn'],      'recommended' => '≥ 150',  'ok' => (int)$info['max_conn'] >= 150],
			['setting' => 'InnoDB Buffer Pool (GB)','value' => $bufferPoolGB,          'recommended' => '≥ 1 GB', 'ok' => $bufferPoolGB >= 1],
			['setting' => 'Transaction Isolation',  'value' => $info['tx_isolation'],  'recommended' => 'READ-COMMITTED', 'ok' => $info['tx_isolation'] === 'READ-COMMITTED'],
		];
	}

	private function getPostgreSQLInfo(): array {
		$result = $this->connection->executeQuery(
			"SELECT version(),
					current_setting('max_connections') AS max_conn,
					current_setting('shared_buffers') AS shared_buffers,
					current_setting('work_mem') AS work_mem"
		);
		$info = $result->fetchAssociative();

		return [
			['setting' => 'Engine',           'value' => 'PostgreSQL'],
			['setting' => 'Version',          'value' => $info['version']],
			['setting' => 'Max Connections',  'value' => $info['max_conn'],      'recommended' => '≥ 100',  'ok' => (int)$info['max_conn'] >= 100],
			['setting' => 'Shared Buffers',   'value' => $info['shared_buffers'],'recommended' => '128MB+', 'ok' => true],
			['setting' => 'Work Mem',         'value' => $info['work_mem'],      'recommended' => '4MB+',   'ok' => true],
		];
	}

	private function getSQLiteInfo(): array {
		$result = $this->connection->executeQuery('SELECT sqlite_version() AS version');
		$info = $result->fetchAssociative();
		return [
			['setting' => 'Engine',  'value' => 'SQLite'],
			['setting' => 'Version', 'value' => $info['version']],
		];
	}
}
