<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
