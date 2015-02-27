<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
