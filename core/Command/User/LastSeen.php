<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LastSeen extends Base {
	public function __construct(
		protected IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('user:lastseen')
			->setDescription('shows when the user was logged in last time')
			->addArgument(
				'uid',
				InputArgument::OPTIONAL,
				'the username'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'shows a list of when all users were last logged in'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$singleUserId = $input->getArgument('uid');

		if ($singleUserId) {
			$user = $this->userManager->get($singleUserId);
			if (is_null($user)) {
				$output->writeln('<error>User does not exist</error>');
				return 1;
			}

			$lastLogin = $user->getLastLogin();
			if ($lastLogin === 0) {
				$output->writeln($user->getUID() . ' has never logged in.');
			} else {
				$date = new \DateTime();
				$date->setTimestamp($lastLogin);
				$output->writeln($user->getUID() . "'s last login: " . $date->format('Y-m-d H:i:s T'));
			}

			return 0;
		}

		if (!$input->getOption('all')) {
			$output->writeln('<error>Please specify a username, or "--all" to list all</error>');
			return 1;
		}

		$this->userManager->callForAllUsers(static function (IUser $user) use ($output): void {
			$lastLogin = $user->getLastLogin();
			if ($lastLogin === 0) {
				$output->writeln($user->getUID() . ' has never logged in.');
			} else {
				$date = new \DateTime();
				$date->setTimestamp($lastLogin);
				$output->writeln($user->getUID() . "'s last login: " . $date->format('Y-m-d H:i:s T'));
			}
		});
		return 0;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'uid') {
			return array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
