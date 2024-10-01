<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserPluginManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ResetUser extends Command {
	public function __construct(
		protected DeletedUsersIndex $dui,
		private IUserManager $userManager,
		private UserPluginManager $pluginManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:reset-user')
			->setDescription('deletes an LDAP user independent of the user state')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the user id as used in Nextcloud'
			)
			->addOption(
				'yes',
				'y',
				InputOption::VALUE_NONE,
				'do not ask for confirmation'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$uid = $input->getArgument('uid');
			$user = $this->userManager->get($uid);
			if (!$user instanceof IUser) {
				throw new \Exception('User not found');
			}
			$backend = $user->getBackend();
			if (!$backend instanceof User_Proxy) {
				throw new \Exception('The given user is not a recognized LDAP user.');
			}
			if ($input->getOption('yes') === false) {
				/** @var QuestionHelper $helper */
				$helper = $this->getHelper('question');
				$q = new Question('Delete all local data of this user (y|N)? ');
				$input->setOption('yes', $helper->ask($input, $output, $q) === 'y');
			}
			if ($input->getOption('yes') !== true) {
				throw new \Exception('Reset cancelled by operator');
			}

			$this->dui->markUser($uid);
			$pluginManagerSuppressed = $this->pluginManager->setSuppressDeletion(true);
			if ($user->delete()) {
				$this->pluginManager->setSuppressDeletion($pluginManagerSuppressed);
				return self::SUCCESS;
			}
		} catch (\Throwable $e) {
			if (isset($pluginManagerSuppressed)) {
				$this->pluginManager->setSuppressDeletion($pluginManagerSuppressed);
			}
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
		$output->writeln('<error>Error while resetting user</error>');
		return self::INVALID;
	}
}
