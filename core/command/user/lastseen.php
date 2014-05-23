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

class LastSeen extends Command {
	protected function configure() {
		$this
			->setName('user:lastseen')
			->setDescription('shows when the user was logged it last time')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the username'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$userManager = \OC::$server->getUserManager();
		$user = $userManager->get($input->getArgument('uid'));
		if(is_null($user)) {
			$output->writeln('User does not exist');
			return;
		}

		$lastLogin = $user->getLastLogin();
		if($lastLogin === 0) {
			$output->writeln('User ' . $user->getUID() .
				' has never logged in, yet.');
		} else {
			$date = new \DateTime();
			$date->setTimestamp($lastLogin);
			$output->writeln($user->getUID() .
				'`s last login: ' . $date->format('d.m.Y H:i'));
		}
	}
}
