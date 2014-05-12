<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ResetAdminPass extends Command {
	protected function configure() {
		$this
			->setName('resetadminpass')
			->setDescription('Resets the password of the first user')
			->addArgument(
				'password',
				InputArgument::REQUIRED,
				'Password to reset to'
			);
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$password = $input->getArgument('password');
		$query = \OC_DB::prepare('SELECT `uid` FROM `*PREFIX*users` LIMIT 1');
		$username = $query->execute()->fetchOne();
		\OC_User::setPassword($username, $password);
		$output->writeln("Successfully reset password for " . $username . " to " . $password);
	}
}
