<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db;

use Doctrine\DBAL\Types\Type;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ConvertFilecacheBigInt extends Command {
	public function __construct(
		private Connection $connection,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:convert-filecache-bigint')
			->setDescription('Convert the ID columns of the filecache to BigInt');
	}

	/**
	 * @return array<string,string[]>
	 */
	public static function getColumnsByTable(): array {
		return [
			'activity' => ['activity_id', 'object_id'],
			'activity_mq' => ['mail_id'],
			'authtoken' => ['id'],
			'bruteforce_attempts' => ['id'],
			'federated_reshares' => ['share_id'],
			'filecache' => ['fileid', 'storage', 'parent', 'mimetype', 'mimepart', 'mtime', 'storage_mtime'],
			'filecache_extended' => ['fileid'],
			'files_trash' => ['auto_id'],
			'file_locks' => ['id'],
			'file_metadata' => ['id'],
			'jobs' => ['id'],
			'mimetypes' => ['id'],
			'mounts' => ['id', 'storage_id', 'root_id', 'mount_id'],
			'share_external' => ['id', 'parent'],
			'storages' => ['numeric_id'],
		];
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$schema = new SchemaWrapper($this->connection);
		$isSqlite = $this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE;
		$updates = [];

		$tables = static::getColumnsByTable();
		foreach ($tables as $tableName => $columns) {
			if (!$schema->hasTable($tableName)) {
				continue;
			}

			$table = $schema->getTable($tableName);

			foreach ($columns as $columnName) {
				$column = $table->getColumn($columnName);
				$isAutoIncrement = $column->getAutoincrement();
				$isAutoIncrementOnSqlite = $isSqlite && $isAutoIncrement;
				if ($column->getType()->getName() !== Types::BIGINT && !$isAutoIncrementOnSqlite) {
					$column->setType(Type::getType(Types::BIGINT));
					$column->setOptions(['length' => 20]);

					$updates[] = '* ' . $tableName . '.' . $columnName;
				}
			}
		}

		if (empty($updates)) {
			$output->writeln('<info>All tables already up to date!</info>');
			return 0;
		}

		$output->writeln('<comment>Following columns will be updated:</comment>');
		$output->writeln('');
		$output->writeln($updates);
		$output->writeln('');
		$output->writeln('<comment>This can take up to hours, depending on the number of files in your instance!</comment>');

		if ($input->isInteractive()) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion('Continue with the conversion (y/n)? [n] ', false);

			if (!$helper->ask($input, $output, $question)) {
				return 1;
			}
		}

		$this->connection->migrateToSchema($schema->getWrappedSchema());

		return 0;
	}
}
