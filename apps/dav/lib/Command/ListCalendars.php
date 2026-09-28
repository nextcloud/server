<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCalendars extends Command {
	public function __construct(
		protected IUserManager $userManager,
		private CalDavBackend $caldav,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:list-calendars')
			->setDescription('List all calendars of a user')
			->addArgument('uid',
				InputArgument::REQUIRED,
				'User for whom all calendars will be listed');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> is unknown.");
		}

		$calendars = $this->caldav->getCalendarsForUser("principals/users/$user");

		$calendarTableData = [];
		foreach ($calendars as $calendar) {
			// skip birthday calendar
			if ($calendar['uri'] === BirthdayService::BIRTHDAY_CALENDAR_URI) {
				continue;
			}

			$readOnly = false;
			$readOnlyIndex = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only';
			if (isset($calendar[$readOnlyIndex])) {
				$readOnly = $calendar[$readOnlyIndex];
			}

			$calendarTableData[] = [
				$calendar['uri'],
				$calendar['{DAV:}displayname'],
				$calendar['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal'],
				$calendar['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname'],
				$readOnly ? ' x ' : ' ✓ ',
			];
		}

		if (count($calendarTableData) > 0) {
			$table = new Table($output);
			$table->setHeaders(['URI', 'Displayname', 'Owner principal', 'Owner displayname', 'Writable'])
				->setRows($calendarTableData);

			$table->render();
		} else {
			$output->writeln("<info>User <$user> has no calendars</info>");
		}
		return self::SUCCESS;
	}
}
