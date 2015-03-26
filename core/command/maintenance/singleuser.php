<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SingleUser extends Command {

	protected function configure() {
		$this
			->setName('maintenance:singleuser')
			->setDescription('set single user mode')
			->addOption(
				'on',
				null,
				InputOption::VALUE_NONE,
				'enable single user mode'
			)
			->addOption(
				'off',
				null,
				InputOption::VALUE_NONE,
				'disable single user mode'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('on')) {
			\OC_Config::setValue('singleuser', true);
			$output->writeln('Single user mode enabled');
		} elseif ($input->getOption('off')) {
			\OC_Config::setValue('singleuser', false);
			$output->writeln('Single user mode disabled');
		} else {
			if (\OC_Config::getValue('singleuser', false)) {
				$output->writeln('Single user mode is currently enabled');
			} else {
				$output->writeln('Single user mode is currently disabled');
			}
		}
	}
}
