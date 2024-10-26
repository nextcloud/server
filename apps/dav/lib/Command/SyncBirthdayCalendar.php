<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncBirthdayCalendar extends Command {
	public function __construct(
		private IUserManager $userManager,
		private IConfig $config,
		private BirthdayService $birthdayService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:sync-birthday-calendar')
			->setDescription('Synchronizes the birthday calendar')
			->addArgument('user',
				InputArgument::OPTIONAL,
				'User for whom the birthday calendar will be synchronized');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->verifyEnabled();

		$user = $input->getArgument('user');
		if (!is_null($user)) {
			if (!$this->userManager->userExists($user)) {
				throw new \InvalidArgumentException("User <$user> in unknown.");
			}

			// re-enable the birthday calendar in case it's called directly with a user name
			$isEnabled = $this->config->getUserValue($user, 'dav', 'generateBirthdayCalendar', 'yes');
			if ($isEnabled !== 'yes') {
				$this->config->setUserValue($user, 'dav', 'generateBirthdayCalendar', 'yes');
				$output->writeln("Re-enabling birthday calendar for $user");
			}

			$output->writeln("Start birthday calendar sync for $user");
			$this->birthdayService->syncUser($user);
			return self::SUCCESS;
		}
		$output->writeln('Start birthday calendar sync for all users ...');
		$p = new ProgressBar($output);
		$p->start();
		$this->userManager->callForSeenUsers(function ($user) use ($p): void {
			$p->advance();

			$userId = $user->getUID();
			$isEnabled = $this->config->getUserValue($userId, 'dav', 'generateBirthdayCalendar', 'yes');
			if ($isEnabled !== 'yes') {
				return;
			}

			/** @var IUser $user */
			$this->birthdayService->syncUser($user->getUID());
		});

		$p->finish();
		$output->writeln('');
		return self::SUCCESS;
	}

	protected function verifyEnabled(): void {
		$isEnabled = $this->config->getAppValue('dav', 'generateBirthdayCalendar', 'yes');

		if ($isEnabled !== 'yes') {
			throw new \InvalidArgumentException('Birthday calendars are disabled');
		}
	}
}
