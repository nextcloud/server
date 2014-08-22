<?php
/**
 * Copyright (c) 2014 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ResetPassword extends Command {

	/** @var \OC\User\Manager */
	protected $userManager;

	public function __construct(\OC\User\Manager $userManager) {
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:resetpassword')
			->setDescription('Resets the password of the named user')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'Username to reset password'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$username = $input->getArgument('user');

		/** @var $user \OC\User\User */
		$user = $this->userManager->get($username);
		if (is_null($user)) {
			$output->writeln("<error>There is no user called " . $username . "</error>");
			return 1;
		}

		if ($input->isInteractive()) {
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');

			if (\OCP\App::isEnabled('files_encryption')) {
				$output->writeln(
					'<error>Warning: Resetting the password when using encryption will result in data loss!</error>'
				);
				if (!$dialog->askConfirmation($output, '<question>Do you want to continue?</question>', true)) {
					return 1;
				}
			}

			$password = $dialog->askHiddenResponse(
				$output,
				'<question>Enter a new password: </question>',
				false
			);
			$confirm = $dialog->askHiddenResponse(
				$output,
				'<question>Confirm the new password: </question>',
				false
			);

			if ($password === $confirm) {
				$success = $user->setPassword($password);
				if ($success) {
					$output->writeln("<info>Successfully reset password for " . $username . "</info>");
				} else {
					$output->writeln("<error>Error while resetting password!</error>");
					return 1;
				}
			} else {
				$output->writeln("<error>Passwords did not match!</error>");
				return 1;
			}
		} else {
			$output->writeln("<error>Interactive input is needed for entering a new password!</error>");
			return 1;
		}
	}
}
