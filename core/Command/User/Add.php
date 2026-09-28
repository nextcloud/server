<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\User;

use OC\Files\Filesystem;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\ISecureRandom;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Add extends Command {
	public function __construct(
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
		protected IMailer $mailer,
		private IAppConfig $appConfig,
		private NewUserMailHelper $mailHelper,
		private IEventDispatcher $eventDispatcher,
		private ISecureRandom $secureRandom,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('user:add')
			->setDescription('adds an account')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'Account ID used to login (must only contain a-z, A-Z, 0-9, -, _ and @)'
			)
			->addOption(
				'password-from-env',
				null,
				InputOption::VALUE_NONE,
				'read password from environment variable NC_PASS/OC_PASS'
			)
			->addOption(
				'generate-password',
				null,
				InputOption::VALUE_NONE,
				'Generate a secure password. A welcome email with a reset link will be sent to the user via an email if --email option and newUser.sendEmail config are set'
			)
			->addOption(
				'display-name',
				null,
				InputOption::VALUE_OPTIONAL,
				'Login used in the web UI (can contain any characters)'
			)
			->addOption(
				'group',
				'g',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'groups the account should be added to (The group will be created if it does not exist)'
			)
			->addOption(
				'email',
				null,
				InputOption::VALUE_REQUIRED,
				'When set, users may register using the default email verification workflow'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		if ($this->userManager->userExists($uid)) {
			$output->writeln('<error>The account "' . $uid . '" already exists.</error>');
			return 1;
		}

		$password = '';

		// Setup password.
		if ($input->getOption('password-from-env')) {
			$password = getenv('NC_PASS') ?: getenv('OC_PASS');

			if (!$password) {
				$output->writeln('<error>--password-from-env given, but NC_PASS/OC_PASS is empty!</error>');
				return 1;
			}
		} elseif ($input->getOption('generate-password')) {
			$passwordEvent = new GenerateSecurePasswordEvent();
			$this->eventDispatcher->dispatchTyped($passwordEvent);
			$password = $passwordEvent->getPassword() ?? $this->secureRandom->generate(20);
		} elseif ($input->isInteractive()) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');

			$question = new Question('Enter password: ');
			$question->setHidden(true);
			$password = $helper->ask($input, $output, $question);

			$question = new Question('Confirm password: ');
			$question->setHidden(true);
			$confirm = $helper->ask($input, $output, $question);

			if ($password !== $confirm) {
				$output->writeln('<error>Passwords did not match!</error>');
				return 1;
			}
		} else {
			$output->writeln('<error>Interactive input or --password-from-env or --generate-password is needed for setting a password!</error>');
			return 1;
		}

		try {
			$user = $this->userManager->createUser(
				$input->getArgument('uid'),
				$password,
			);
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		if ($user instanceof IUser) {
			$output->writeln('<info>The account "' . $user->getUID() . '" was created successfully</info>');
		} else {
			$output->writeln('<error>An error occurred while creating the account</error>');
			return 1;
		}

		if ($input->getOption('display-name')) {
			$user->setDisplayName($input->getOption('display-name'));
			$output->writeln('Display name set to "' . $user->getDisplayName() . '"');
		}

		$groups = $input->getOption('group');

		if (!empty($groups)) {
			// Make sure we init the Filesystem for the user, in case we need to
			// init some group shares.
			Filesystem::init($user->getUID(), '');
		}

		foreach ($groups as $groupName) {
			$group = $this->groupManager->get($groupName);
			if (!$group) {
				$this->groupManager->createGroup($groupName);
				$group = $this->groupManager->get($groupName);
				if ($group instanceof IGroup) {
					$output->writeln('Created group "' . $group->getGID() . '"');
				}
			}
			if ($group instanceof IGroup) {
				$group->addUser($user);
				$output->writeln('Account "' . $user->getUID() . '" added to group "' . $group->getGID() . '"');
			}
		}

		$email = $input->getOption('email');
		if (!empty($email)) {
			if (!$this->mailer->validateMailAddress($email)) {
				$output->writeln(\sprintf(
					'<error>The given email address "%s" is invalid. Email not set for the user.</error>',
					$email,
				));

				return 1;
			}

			$user->setSystemEMailAddress($email);

			if ($this->appConfig->getValueString('core', 'newUser.sendEmail', 'yes') === 'yes') {
				try {
					$this->mailHelper->sendMail($user, $this->mailHelper->generateTemplate($user, true));
					$output->writeln('Welcome email sent to ' . $email);
				} catch (\Exception $e) {
					$output->writeln('Unable to send the welcome email to ' . $email);
				}
			}
		}

		return 0;
	}
}
