<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OC\Core\Command\Base;
use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\DB\SchemaWrapper;
use OC\Migration\NullOutput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExpectedSchema extends Base {
	public function __construct(
		protected Connection $connection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('db:schema:expected')
			->setDescription('Export the expected database schema for a fresh installation')
			->setHelp("Note that the expected schema might not exactly match the exported live schema as the expected schema doesn't take into account any database wide settings or defaults.")
			->addArgument('table', InputArgument::OPTIONAL, 'Only show the schema for the specified table')
			->addOption('sql', null, InputOption::VALUE_NONE, 'Dump the SQL statements for creating the expected schema');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$schema = new Schema();
		$onlyTable = $input->getArgument('table');

		$this->applyMigrations('core', $schema);

		$apps = \OC_App::getEnabledApps();
		foreach ($apps as $app) {
			$this->applyMigrations($app, $schema);
		}

		if ($onlyTable) {
			$tablesToDrop = [];
			foreach ($schema->getTables() as $table) {
				if ($table->getName() !== $onlyTable) {
					$tablesToDrop[] = $table->getName();
				}
			}
			foreach ($tablesToDrop as $table) {
				$schema->dropTable($table);
			}
		}

		$sql = $input->getOption('sql');
		if ($sql) {
			$output->writeln($schema->toSql($this->connection->getDatabasePlatform()));
		} else {
			$encoder = new SchemaEncoder();
			$this->writeArrayInOutputFormat($input, $output, $encoder->encodeSchema($schema, $this->connection->getDatabasePlatform()));
		}

		return 0;
	}

	private function applyMigrations(string $app, Schema $schema): void {
		$output = new NullOutput();
		$ms = new MigrationService($app, $this->connection, $output);
		foreach ($ms->getAvailableVersions() as $version) {
			$migration = $ms->createInstance($version);
			$migration->changeSchema($output, function () use (&$schema) {
				return new SchemaWrapper($this->connection, $schema);
			}, []);
		}
	}
}
