<?php
/**
 * Copyright (c) 2014 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ResetPassword extends Command {
	protected function configure() {
		$this
			->setName('resetpassword')
			->setDescription('Resets the password of the named user')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'Username to reset password'
			);
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$username = $input->getArgument('user');
		if ($input->isInteractive()) {
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');
			$password = $dialog->askHiddenResponse(
				$output,
				'<question>Enter a new password: </question>',
				false
			);
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');
			$confirm = $dialog->askHiddenResponse(
				$output,
				'<question>Confirm the new password: </question>',
				false
			);

			if ($password === $confirm) {
				$success = \OC_User::setPassword($username, $password);
				if ($success) {
					$output->writeln("Successfully reset password for " . $username);
				} else {
					$output->writeln("There is no user called " . $username);
				}
			} else {
				$output->writeln("Passwords did not match!");
			}
		} else {
			$output->writeln("Interactive input is needed for entering a new password!");
		}
	}
}
