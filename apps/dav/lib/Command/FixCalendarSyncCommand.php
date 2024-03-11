<?php

declare(strict_types=1);

/*
 * @copyright 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

	public function __construct(private IUserManager $userManager,
		private CalDavBackend $calDavBackend) {
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
				return 1;
			}

			$this->fixUserCalendars($user);
		} else {
			$progress = new ProgressBar($output);
			$this->userManager->callForSeenUsers(function (IUser $user) use ($progress) {
				$this->fixUserCalendars($user, $progress);
			});
			$progress->finish();
		}
		return 0;
	}

	private function fixUserCalendars(IUser $user, ?ProgressBar $progress = null): void {
		$calendars = $this->calDavBackend->getCalendarsForUser("principals/users/" . $user->getUID());

		foreach ($calendars as $calendar) {
			$this->calDavBackend->restoreChanges($calendar['id']);
		}

		if ($progress !== null) {
			$progress->advance();
		}
	}

}
