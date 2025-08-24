<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCalendarSyncCommand extends Command {

	public function __construct(
		private IUserManager $userManager,
		private CalDavBackend $calDavBackend,
	) {
		parent::__construct('dav:fix-missing-caldav-changes');
	}

	protected function configure(): void {
		$this->setDescription('Insert missing calendarchanges rows for existing events');
		$this->addArgument(
			'user',
			InputArgument::OPTIONAL,
			'User to fix calendar sync tokens for, if omitted all users will be fixed',
			null,
		);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userArg = $input->getArgument('user');
		if ($userArg !== null) {
			$user = $this->userManager->get($userArg);
			if ($user === null) {
				$output->writeln("<error>User $userArg does not exist</error>");
				return self::FAILURE;
			}

			$this->fixUserCalendars($user);
		} else {
			$progress = new ProgressBar($output);
			$this->userManager->callForSeenUsers(function (IUser $user) use ($progress): void {
				$this->fixUserCalendars($user, $progress);
			});
			$progress->finish();
		}
		$output->writeln('');
		return self::SUCCESS;
	}

	private function fixUserCalendars(IUser $user, ?ProgressBar $progress = null): void {
		$calendars = $this->calDavBackend->getCalendarsForUser('principals/users/' . $user->getUID());

		foreach ($calendars as $calendar) {
			$this->calDavBackend->restoreChanges($calendar['id']);
		}

		if ($progress !== null) {
			$progress->advance();
		}
	}

}
