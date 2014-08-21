<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Delete extends Command {
	protected function configure() {
		$this
			->setName('user:delete')
			->setDescription('deletes the specified user')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the username'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$wasSuccessful = \OC_User::deleteUser($input->getArgument('uid'));
		if($wasSuccessful === true) {
			$output->writeln('The specified user was deleted');
			return;
		}
		$output->writeln('<error>The specified could not be deleted. Please check the logs.</error>');
	}
}
