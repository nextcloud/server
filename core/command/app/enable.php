<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Enable extends Command {
	protected function configure() {
		$this
			->setName('app:enable')
			->setDescription('enable an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'enable the specified app'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$appId = $input->getArgument('app-id');
		if (\OC_App::isEnabled($appId)) {
			$output->writeln($appId . ' is already enabled');
		} else if (!\OC_App::getAppPath($appId)) {
			$output->writeln($appId . ' not found');
		} else {
			\OC_App::enable($appId);
			$output->writeln($appId . ' enabled');
		}
	}
}
