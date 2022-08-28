<?php

declare(strict_types=1);
/**
 *
 * @copyright Copyright (c) 2021, Mattia Narducci (mattianarducci1@gmail.com)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCalendar extends Command {
	public function __construct(
		private CalDavBackend $calDav,
		private IConfig $config,
		private IL10N $l10n,
		private IUserManager $userManager,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:delete-calendar')
			->setDescription('Delete a dav calendar')
			->addArgument('uid',
				InputArgument::REQUIRED,
				'User who owns the calendar')
			->addArgument('name',
				InputArgument::OPTIONAL,
				'Name of the calendar to delete')
			->addOption('birthday',
				null,
				InputOption::VALUE_NONE,
				'Delete the birthday calendar')
			->addOption('force',
				'f',
				InputOption::VALUE_NONE,
				'Force delete skipping trashbin');
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		/** @var string $user **/
		$user = $input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException(
				'User <' . $user . '> is unknown.');
		}

		$birthday = $input->getOption('birthday');
		if ($birthday !== false) {
			$name = BirthdayService::BIRTHDAY_CALENDAR_URI;
		} else {
			/** @var string $name **/
			$name = $input->getArgument('name');
			if (!$name) {
				throw new \InvalidArgumentException(
					'Please specify a calendar name or --birthday');
			}
		}

		$calendarInfo = $this->calDav->getCalendarByUri(
			'principals/users/' . $user,
			$name);
		if ($calendarInfo === null) {
			throw new \InvalidArgumentException(
				'User <' . $user . '> has no calendar named <' . $name . '>. You can run occ dav:list-calendars to list calendars URIs for this user.');
		}

		$calendar = new Calendar(
			$this->calDav,
			$calendarInfo,
			$this->l10n,
			$this->config,
			$this->logger
		);

		$force = $input->getOption('force');
		if ($force) {
			$calendar->disableTrashbin();
		}

		$calendar->delete();

		return self::SUCCESS;
	}
}
