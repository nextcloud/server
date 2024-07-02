<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Db;

use OC\Core\Command\Base;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportSchema extends Base {
	public function __construct(
		protected IDBConnection $connection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('db:schema:export')
			->setDescription('Export the current database schema')
			->addOption('sql', null, InputOption::VALUE_NONE, 'Dump the SQL statements for creating a copy of the schema');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$schema = $this->connection->createSchema();
		$sql = $input->getOption('sql');
		if ($sql) {
			$output->writeln($schema->toSql($this->connection->getDatabasePlatform()));
		} else {
			$encoder = new SchemaEncoder();
			$this->writeArrayInOutputFormat($input, $output, $encoder->encodeSchema($schema, $this->connection->getDatabasePlatform()));
		}

		return 0;
	}
}
