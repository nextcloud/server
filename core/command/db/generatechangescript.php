<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\Db;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateChangeScript extends Command {
	protected function configure() {
		$this
			->setName('db:generate-change-script')
			->setDescription('generates the change script from the current connected db to db_structure.xml')
			->addArgument(
				'schema-xml',
				InputArgument::OPTIONAL,
				'the schema xml to be used as target schema',
				\OC::$SERVERROOT . '/db_structure.xml'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$file = $input->getArgument('schema-xml');

		$schemaManager = new \OC\DB\MDB2SchemaManager(\OC_DB::getConnection());

		try {
			$result = $schemaManager->updateDbFromStructure($file, true);
			$output->writeln($result);
		} catch (\Exception $e) {
			$output->writeln('Failed to update database structure ('.$e.')');
		}

	}
}
