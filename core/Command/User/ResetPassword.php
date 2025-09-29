<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ResetPassword extends Base {
	public function __construct(
		protected IUserManager $userManager,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:resetpassword')
			->setDescription('Resets the password of the named user')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'Login to reset password'
			)
			->addOption(
				'password-from-env',
				null,
				InputOption::VALUE_NONE,
				'read password from environment variable NC_PASS/OC_PASS'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$username = $input->getArgument('user');

		$user = $this->userManager->get($username);
		if (is_null($user)) {
			$output->writeln('<error>User does not exist</error>');
			return 1;
		}

		if ($input->getOption('password-from-env')) {
			$password = getenv('NC_PASS') ?: getenv('OC_PASS');
			if (!$password) {
				$output->writeln('<error>--password-from-env given, but NC_PASS/OC_PASS is empty!</error>');
				return 1;
			}
		} elseif ($input->isInteractive()) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');

			if ($this->appManager->isEnabledForUser('encryption', $user)) {
				$output->writeln(
					'<error>Warning: Resetting the password when using encryption will result in data loss!</error>'
				);

				$question = new ConfirmationQuestion('Do you want to continue?');
				if (!$helper->ask($input, $output, $question)) {
					return 1;
				}
			}

			$question = new Question('Enter a new password: ');
			$question->setHidden(true);
			$password = $helper->ask($input, $output, $question);

			if ($password === null) {
				$output->writeln('<error>Password cannot be empty!</error>');
				return 1;
			}

			$question = new Question('Confirm the new password: ');
			$question->setHidden(true);
			$confirm = $helper->ask($input, $output, $question);

			if ($password !== $confirm) {
				$output->writeln('<error>Passwords did not match!</error>');
				return 1;
			}
		} else {
			$output->writeln('<error>Interactive input or --password-from-env is needed for entering a new password!</error>');
			return 1;
		}


		try {
			$success = $user->setPassword($password);
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		if ($success) {
			$output->writeln('<info>Successfully reset password for ' . $username . '</info>');
		} else {
			$output->writeln('<error>Error while resetting password!</error>');
			return 1;
		}
		return 0;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'user') {
			return array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
