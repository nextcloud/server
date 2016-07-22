<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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

		$schemaManager = new \OC\DB\MDB2SchemaManager(\OC::$server->getDatabaseConnection());

		try {
			$result = $schemaManager->updateDbFromStructure($file, true);
			$output->writeln($result);
		} catch (\Exception $e) {
			$output->writeln('Failed to update database structure ('.$e.')');
		}

	}
}
