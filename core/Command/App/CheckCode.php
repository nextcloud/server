<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCode extends Command {
	protected $checkers = [];

	protected function configure() {
		$this
			->setName('app:check-code')
			->setDescription('check code to be compliant')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'check the specified app'
			)
			->addOption(
				'checker',
				'c',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'enable the specified checker(s)',
				[ 'private', 'deprecation', 'strong-comparison' ]
			)
			->addOption(
				'--skip-checkers',
				null,
				InputOption::VALUE_NONE,
				'skips the the code checkers to only check info.xml, language and database schema'
			)
			->addOption(
				'--skip-validate-info',
				null,
				InputOption::VALUE_NONE,
				'skips the info.xml/version check'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln('<error>The app code checker doesn\t check anything and this command will be removed in Nextcloud 23</error>');

		return 0;
	}
}
