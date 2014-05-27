<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * and Stephen Colebrook <scolebrook@mac.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MMode extends Command {

	protected function configure() {
		$this
			->setName('maintenance:mmode')
			->setDescription('set maintenance mode')
			->addOption(
				'on',
				null,
				InputOption::VALUE_NONE,
				'enable maintenance mode'
			)
			->addOption(
				'off',
				null,
				InputOption::VALUE_NONE,
				'disable maintenance mode'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('on')) {
			\OC_Config::setValue('maintenance', true);
			$output->writeln('Maintenance mode enabled');
		} elseif ($input->getOption('off')) {
			\OC_Config::setValue('maintenance', false);
			$output->writeln('Maintenance mode disabled');
		} else {
			if (\OC_Config::getValue('maintenance', false)) {
				$output->writeln('Maintenance mode is currently enabled');
			} else {
				$output->writeln('Maintenance mode is currently disabled');
			}
		}
	}
}
